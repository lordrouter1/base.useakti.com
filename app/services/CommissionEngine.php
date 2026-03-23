<?php
namespace Akti\Services;

use Akti\Models\Commission;
use PDO;

/**
 * CommissionEngine — Motor de Regras de Comissão (Rule Engine)
 *
 * Responsabilidades:
 *   - Resolver a regra ativa por hierarquia (Usuário → Grupo → Produto → Padrão)
 *   - Calcular comissão usando Strategy Pattern (Percentual, Valor Fixo, Faixa)
 *   - Registrar o resultado no log (comissoes_registradas)
 *   - Simular comissão sem registrar (modo dry-run)
 *
 * Extensibilidade (Open/Closed Principle):
 *   - Novas estratégias podem ser registradas via registerStrategy()
 *   - Novos resolvers podem ser adicionados via registerResolver()
 *
 * @package Akti\Services
 */
class CommissionEngine
{
    private Commission $model;
    private PDO $db;

    /** @var array<string, callable> Estratégias de cálculo registradas */
    private array $strategies = [];

    /** @var array<string, callable> Resolvers de regra (em ordem de prioridade) */
    private array $resolvers = [];

    public function __construct(PDO $db, Commission $model)
    {
        $this->db = $db;
        $this->model = $model;

        // Registrar estratégias padrão
        $this->registerStrategy('percentual', [$this, 'calculatePercentual']);
        $this->registerStrategy('valor_fixo', [$this, 'calculateValorFixo']);
        $this->registerStrategy('faixa', [$this, 'calculateFaixa']);

        // Registrar resolvers na ordem de prioridade (primeiro match ganha)
        $this->registerResolver('usuario', [$this, 'resolveUsuario']);
        $this->registerResolver('grupo', [$this, 'resolveGrupo']);
        $this->registerResolver('produto', [$this, 'resolveProduto']);
        $this->registerResolver('padrao', [$this, 'resolvePadrao']);
    }

    // ═══════════════════════════════════════════════════
    // EXTENSIBILIDADE (Open/Closed)
    // ═══════════════════════════════════════════════════

    /**
     * Registra uma nova estratégia de cálculo.
     * @param string   $tipo     Identificador (ex: 'percentual', 'valor_fixo', 'faixa', 'equipe')
     * @param callable $callback fn(array $regra, array $context): array
     */
    public function registerStrategy(string $tipo, callable $callback): void
    {
        $this->strategies[$tipo] = $callback;
    }

    /**
     * Registra um novo resolver de regra.
     * @param string   $nome     Identificador (ex: 'usuario', 'grupo', 'equipe')
     * @param callable $callback fn(array $context): ?array  Retorna regra ou null
     */
    public function registerResolver(string $nome, callable $callback): void
    {
        $this->resolvers[$nome] = $callback;
    }

    // ═══════════════════════════════════════════════════
    // RESOLUÇÃO DE REGRA (Priority Flow)
    // ═══════════════════════════════════════════════════

    /**
     * Resolve qual regra aplicar para o contexto dado.
     *
     * @param array $context [
     *   'user_id'      => int,
     *   'order_id'     => int,
     *   'product_id'   => ?int,
     *   'category_id'  => ?int,
     *   'valor_venda'  => float,
     *   'margem_lucro' => ?float,
     * ]
     * @return array|null ['regra' => array, 'origem' => string]
     */
    public function resolveRegra(array $context): ?array
    {
        foreach ($this->resolvers as $nome => $resolver) {
            $regra = call_user_func($resolver, $context);
            if ($regra !== null) {
                return ['regra' => $regra, 'origem' => $nome];
            }
        }
        return null;
    }

    // ── Resolvers padrão ──

    protected function resolveUsuario(array $context): ?array
    {
        if (empty($context['user_id'])) return null;
        return $this->model->getRegraUsuario((int) $context['user_id']);
    }

    protected function resolveGrupo(array $context): ?array
    {
        if (empty($context['user_id'])) return null;
        return $this->model->getRegraGrupo((int) $context['user_id']);
    }

    protected function resolveProduto(array $context): ?array
    {
        // Regra por produto específico
        if (!empty($context['product_id'])) {
            $r = $this->model->getComissaoProduto((int) $context['product_id']);
            if ($r) {
                return [
                    'id'           => $r['id'],
                    'tipo_calculo' => $r['tipo_calculo'],
                    'base_calculo' => 'valor_venda',
                    'valor'        => $r['valor'],
                ];
            }
        }
        // Regra por categoria
        if (!empty($context['category_id'])) {
            $r = $this->model->getComissaoCategoria((int) $context['category_id']);
            if ($r) {
                return [
                    'id'           => $r['id'],
                    'tipo_calculo' => $r['tipo_calculo'],
                    'base_calculo' => 'valor_venda',
                    'valor'        => $r['valor'],
                ];
            }
        }
        return null;
    }

    protected function resolvePadrao(array $context): ?array
    {
        $pct = (float) $this->model->getConfigValue('comissao_padrao_percentual', 5);
        $base = $this->model->getConfigValue('base_calculo_padrao', 'valor_venda');
        return [
            'id'           => null,
            'tipo_calculo' => 'percentual',
            'base_calculo' => $base,
            'valor'        => $pct,
        ];
    }

    // ═══════════════════════════════════════════════════
    // CÁLCULO DE COMISSÃO
    // ═══════════════════════════════════════════════════

    /**
     * Calcula a comissão para um contexto (sem registrar).
     *
     * @param array $context Dados da venda
     * @return array Resultado: valor_comissao, percentual_aplicado, regra, origem, etc.
     */
    public function calcular(array $context): array
    {
        $resolved = $this->resolveRegra($context);

        if (!$resolved) {
            return [
                'valor_comissao'     => 0,
                'percentual_aplicado' => 0,
                'valor_base'         => 0,
                'tipo_calculo'       => 'nenhum',
                'base_calculo'       => 'nenhum',
                'origem_regra'       => 'nenhum',
                'forma_comissao_id'  => null,
                'error'              => 'Nenhuma regra encontrada para este contexto.',
            ];
        }

        $regra = $resolved['regra'];
        $origem = $resolved['origem'];
        $tipoCalculo = $regra['tipo_calculo'];
        $baseCalculo = $regra['base_calculo'] ?? 'valor_venda';

        // Determinar valor base
        $valorBase = $this->getValorBase($baseCalculo, $context);

        // Executar strategy
        if (!isset($this->strategies[$tipoCalculo])) {
            return [
                'valor_comissao'     => 0,
                'percentual_aplicado' => 0,
                'valor_base'         => $valorBase,
                'tipo_calculo'       => $tipoCalculo,
                'base_calculo'       => $baseCalculo,
                'origem_regra'       => $origem,
                'forma_comissao_id'  => $regra['id'] ?? null,
                'error'              => "Estratégia de cálculo não encontrada: {$tipoCalculo}",
            ];
        }

        $resultado = call_user_func($this->strategies[$tipoCalculo], $regra, [
            'valor_base'   => $valorBase,
            'base_calculo' => $baseCalculo,
            'context'      => $context,
        ]);

        return array_merge($resultado, [
            'valor_base'        => $valorBase,
            'tipo_calculo'      => $tipoCalculo,
            'base_calculo'      => $baseCalculo,
            'origem_regra'      => $origem,
            'forma_comissao_id' => $regra['id'] ?? null,
        ]);
    }

    /**
     * Calcula e registra a comissão (modo produção).
     */
    public function calcularERegistrar(array $context): array
    {
        $resultado = $this->calcular($context);

        if (!empty($resultado['error'])) {
            return $resultado;
        }

        // Verificar se já existe
        if ($this->model->existeComissao((int) $context['order_id'], (int) $context['user_id'])) {
            $resultado['warning'] = 'Comissão já registrada para este pedido e usuário.';
            return $resultado;
        }

        // Determinar status
        $autoApprove = (bool) $this->model->getConfigValue('aprovacao_automatica', 0);
        $status = $autoApprove ? 'aprovada' : 'calculada';

        $id = $this->model->registrarComissao([
            'order_id'            => $context['order_id'],
            'user_id'             => $context['user_id'],
            'forma_comissao_id'   => $resultado['forma_comissao_id'],
            'origem_regra'        => $resultado['origem_regra'],
            'tipo_calculo'        => $resultado['tipo_calculo'],
            'base_calculo'        => $resultado['base_calculo'],
            'valor_base'          => $resultado['valor_base'],
            'valor_comissao'      => $resultado['valor_comissao'],
            'percentual_aplicado' => $resultado['percentual_aplicado'],
            'status'              => $status,
            'observacao'          => $context['observacao'] ?? null,
        ]);

        $resultado['comissao_id'] = $id;
        $resultado['status'] = $status;

        return $resultado;
    }

    // ═══════════════════════════════════════════════════
    // STRATEGIES (Cálculos)
    // ═══════════════════════════════════════════════════

    /**
     * Strategy: Percentual
     * Calcula percentual sobre a base.
     */
    protected function calculatePercentual(array $regra, array $params): array
    {
        $valorBase = (float) $params['valor_base'];
        $percentual = (float) $regra['valor'];
        $comissao = round($valorBase * ($percentual / 100), 2);

        return [
            'valor_comissao'      => $comissao,
            'percentual_aplicado' => $percentual,
        ];
    }

    /**
     * Strategy: Valor Fixo
     * Valor fixo independente do montante.
     */
    protected function calculateValorFixo(array $regra, array $params): array
    {
        $valorFixo = (float) $regra['valor'];

        return [
            'valor_comissao'      => $valorFixo,
            'percentual_aplicado' => null,
        ];
    }

    /**
     * Strategy: Faixa / Escala Progressiva
     * Comissão varia conforme valor base (margem, valor total etc.).
     */
    protected function calculateFaixa(array $regra, array $params): array
    {
        $valorBase = (float) $params['valor_base'];
        $formaId = (int) $regra['id'];

        $faixas = $this->model->getFaixas($formaId);

        if (empty($faixas)) {
            return [
                'valor_comissao'      => 0,
                'percentual_aplicado' => 0,
            ];
        }

        // Encontrar a faixa correspondente
        $percentualAplicado = 0;
        foreach ($faixas as $f) {
            $min = (float) $f['faixa_min'];
            $max = $f['faixa_max'] !== null ? (float) $f['faixa_max'] : PHP_FLOAT_MAX;

            if ($valorBase >= $min && $valorBase <= $max) {
                $percentualAplicado = (float) $f['percentual'];
                break;
            }
        }

        $comissao = round($valorBase * ($percentualAplicado / 100), 2);

        return [
            'valor_comissao'      => $comissao,
            'percentual_aplicado' => $percentualAplicado,
        ];
    }

    // ═══════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════

    /**
     * Determina o valor base conforme a base_calculo.
     */
    private function getValorBase(string $baseCalculo, array $context): float
    {
        switch ($baseCalculo) {
            case 'valor_venda':
                return (float) ($context['valor_venda'] ?? 0);
            case 'margem_lucro':
                return (float) ($context['margem_lucro'] ?? 0);
            case 'valor_produto':
                return (float) ($context['valor_produto'] ?? $context['valor_venda'] ?? 0);
            default:
                return (float) ($context['valor_venda'] ?? 0);
        }
    }
}
