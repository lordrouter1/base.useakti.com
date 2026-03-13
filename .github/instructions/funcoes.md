# 📘 Guia de Funções em PHP: Criação, Estrutura e Comentários

> **Versão:** PHP 8.x | **Autor:** Guia de Boas Práticas

---

# Funções Globais e Helpers

---

## Sumário
- [Visão Geral](#visão-geral)
- [Helpers de Formulário](#helpers-de-formulário)
- [Helpers de Escape](#helpers-de-escape)
- [Helpers de Validação](#helpers-de-validação)

---

## Visão Geral
O sistema utiliza funções globais para facilitar a manipulação de formulários, validação, escape de saída e sanitização de dados.

---

## Helpers de Formulário
- `csrf_field()`: insere campo oculto com token CSRF.
- `csrf_meta()`: insere meta tag com token CSRF para AJAX.
- `csrf_token()`: retorna o token CSRF atual.

---

## Helpers de Escape
- `e()`: escape para HTML.
- `eAttr()`: escape para atributos HTML.
- `eJs()`: escape para JavaScript inline.
- `eNum()`: escape para números formatados.
- `eUrl()`: escape para URLs.

---

## Helpers de Validação
- `Validator`: validação encadeável de campos.
- `Sanitizer`: sanitização de valores.
- `Input`: acesso seguro a dados de requisição.

---

## 1. Nomenclatura

### Convenção: `camelCase` para funções e métodos

```php
// ✅ Correto
function calcularTotalPedido(array $itens): float { }
function buscarUsuarioPorId(int $id): ?array { }
function enviarEmailConfirmacao(string $email): bool { }

// ❌ Errado
function CalcularTotal() { }    // PascalCase é para classes
function calcular_total() { }   // snake_case não é padrão PSR-1
function ct() { }               // Abreviações sem sentido
```

### Regras de Nomenclatura

| Tipo         | Convenção    | Exemplo                        |
|--------------|--------------|--------------------------------|
| Função livre | `camelCase`  | `formatarMoeda()`              |
| Método       | `camelCase`  | `$obj->calcularDesconto()`     |
| Classe       | `PascalCase` | `class GerenciadorPedidos`     |
| Constante    | `UPPER_CASE` | `const TAXA_JUROS = 0.05`      |
| Variável     | `camelCase`  | `$valorTotal`                  |

### Verbos Recomendados para Nomear Funções

- **`get`** / **`buscar`** — retorna um valor: `getUsuario()`, `buscarProduto()`
- **`set`** / **`definir`** — define um valor: `setNome()`, `definirStatus()`
- **`is`** / **`has`** / **`pode`** — retorna booleano: `isAtivo()`, `hasPagamento()`, `podeEditar()`
- **`criar`** / **`gerar`** / **`processar`** — ações de transformação: `criarFatura()`, `processarPagamento()`
- **`validar`** / **`verificar`** — verificações: `validarEmail()`, `verificarSaldo()`
- **`salvar`** / **`atualizar`** / **`deletar`** — persistência: `salvarCliente()`, `deletarRegistro()`

---

## 2. Estrutura de uma Função

### Anatomia Completa

```php
<?php

/**
 * Breve descrição do que a função faz (uma linha).
 *
 * Descrição mais detalhada, se necessário. Explica o comportamento
 * em casos especiais, regras de negócio relevantes, etc.
 *
 * @param  tipo   $nomeParam  Descrição do parâmetro
 * @param  tipo   $outroParam Descrição do outro parâmetro
 *
 * @return tipo Descrição do que é retornado
 *
 * @throws NomeException Quando e por que lança essa exceção
 */
function nomeDaFuncao(tipo $nomeParam, tipo $outroParam): tipoRetorno
{
    // 1. Validações e pré-condições
    // 2. Lógica principal
    // 3. Retorno
}
```

### Estrutura Interna Recomendada

```php
function processarPedido(int $pedidoId, string $status): bool
{
    // --- 1. Validação de entrada ---
    if ($pedidoId <= 0) {
        throw new InvalidArgumentException("ID do pedido deve ser positivo.");
    }

    $statusPermitidos = ['pendente', 'aprovado', 'cancelado'];
    if (!in_array($status, $statusPermitidos, true)) {
        throw new InvalidArgumentException("Status '{$status}' não é válido.");
    }

    // --- 2. Lógica principal ---
    $pedido = buscarPedidoPorId($pedidoId);

    if ($pedido === null) {
        return false;
    }

    $pedido['status'] = $status;

    // --- 3. Persistência / efeitos colaterais ---
    salvarPedido($pedido);
    dispararEventoStatusAtualizado($pedidoId, $status);

    // --- 4. Retorno ---
    return true;
}
```

---

## 3. Parâmetros

### Quantidade Ideal

> **Regra:** no máximo **3 parâmetros** por função. Se precisar de mais, agrupe em array ou objeto.

```php
// ❌ Muitos parâmetros — difícil de ler e manter
function criarUsuario(string $nome, string $email, string $senha, int $idade, string $perfil, bool $ativo): void { }

// ✅ Use um array associativo ou DTO
function criarUsuario(array $dados): void
{
    // $dados['nome'], $dados['email'], etc.
}

// ✅ Ou melhor ainda, um objeto tipado (PHP 8.x)
function criarUsuario(DadosUsuario $dados): void { }
```

### Valores Padrão

```php
// ✅ Parâmetros opcionais sempre APÓS os obrigatórios
function buscarProdutos(string $categoria, int $limite = 10, int $pagina = 1): array
{
    // ...
}

// Chamada
$produtos = buscarProdutos('eletronicos');        // limite=10, pagina=1
$produtos = buscarProdutos('eletronicos', 20);    // limite=20, pagina=1
$produtos = buscarProdutos('eletronicos', 20, 2); // limite=20, pagina=2
```

### Argumentos Nomeados (PHP 8.0+)

```php
// ✅ Melhora a legibilidade em chamadas complexas
$resultado = enviarEmail(
    destinatario: 'joao@email.com',
    assunto: 'Confirmação de Pedido',
    corpo: $htmlEmail,
    enviarCopia: true
);
```

### Parâmetros Variádicos

```php
// ✅ Quando o número de argumentos é realmente variável
function somarTudo(float ...$numeros): float
{
    return array_sum($numeros);
}

$total = somarTudo(1.5, 2.0, 3.75, 10.0); // 17.25
```

---

## 4. Tipagem

> **Princípio:** sempre declare tipos de entrada e saída. Use `declare(strict_types=1)` no topo de cada arquivo.

```php
<?php

declare(strict_types=1); // Ativa modo estrito — obrigatório em projetos sérios
```

### Tipos Escalares e Compostos

```php
// Tipos escalares: int, float, string, bool
function calcularDesconto(float $preco, float $percentual): float
{
    return $preco * ($percentual / 100);
}

// Tipos compostos: array, callable, iterable
function processarLista(array $itens, callable $transformacao): array
{
    return array_map($transformacao, $itens);
}

// Tipos de classe
function salvarCliente(Cliente $cliente, Repositorio $repo): void
{
    $repo->salvar($cliente);
}
```

### Tipos Anuláveis

```php
// ✅ Use ? quando o valor pode ser null
function buscarPorEmail(string $email): ?Usuario
{
    // Retorna Usuario ou null se não encontrado
    return $this->repositorio->findOne(['email' => $email]);
}

// Ao usar: verifique null antes
$usuario = buscarPorEmail('teste@email.com');
if ($usuario !== null) {
    echo $usuario->getNome();
}
```

### Union Types (PHP 8.0+)

```php
// ✅ Quando pode retornar mais de um tipo
function parsearId(string|int $id): int
{
    return (int) $id;
}
```

### Tipo `never` (PHP 8.1+)

```php
// ✅ Para funções que nunca retornam (sempre lançam exceção ou terminam a execução)
function lancarErroFatal(string $mensagem): never
{
    throw new RuntimeException($mensagem);
}
```

### Tipo `void`

```php
// ✅ Para funções sem retorno explícito
function registrarLog(string $mensagem, string $nivel = 'info'): void
{
    // Grava no log, mas não retorna nada
    file_put_contents('app.log', "[{$nivel}] {$mensagem}\n", FILE_APPEND);
}
```

---

## 5. Retorno de Valores

### Regras Essenciais

```php
// ✅ Retorne cedo para evitar else desnecessário (Early Return)
function calcularFrete(float $peso, string $regiao): float
{
    if ($peso <= 0) {
        throw new InvalidArgumentException("Peso inválido.");
    }

    if ($regiao === 'sul') {
        return $peso * 1.5;
    }

    if ($regiao === 'norte') {
        return $peso * 2.0;
    }

    return $peso * 1.8; // padrão
}

// ❌ Evite: else desnecessário após return
function calcularFrete(float $peso, string $regiao): float
{
    if ($regiao === 'sul') {
        return $peso * 1.5;
    } else {
        if ($regiao === 'norte') {
            return $peso * 2.0;
        } else {
            return $peso * 1.8;
        }
    }
}
```

### Consistência de Retorno

```php
// ✅ Sempre retorne o mesmo tipo
function buscarNomeUsuario(int $id): string
{
    $usuario = $this->db->find($id);

    if ($usuario === null) {
        return ''; // string vazia, não null
    }

    return $usuario['nome'];
}

// ✅ Se null for válido, declare ?tipo
function buscarUsuario(int $id): ?array
{
    return $this->db->find($id); // array ou null
}
```

---

## 6. Comentários e Documentação (PHPDoc)

### Comentário de Uma Linha

```php
// Use para explicar o "porquê", não o "o quê"

// ❌ Óbvio — não agrega valor
$total = $preco * $quantidade; // multiplica preço pela quantidade

// ✅ Explica a decisão ou regra de negócio
$total = $preco * $quantidade; // IVA será aplicado na etapa de faturação
```

### Comentário de Bloco

```php
/*
 * Use para comentários intermediários mais longos
 * dentro do corpo de uma função, quando necessário.
 */
```

### PHPDoc — Estrutura Completa

```php
/**
 * Calcula o valor final de um pedido aplicando descontos e impostos.
 *
 * Aplica desconto percentual sobre o subtotal e, em seguida, adiciona
 * o imposto calculado sobre o valor já com desconto. Para clientes
 * isentos (tipo 'gov'), o imposto é zero.
 *
 * @param  float   $subtotal     Valor bruto dos itens sem desconto (>= 0)
 * @param  float   $desconto     Percentual de desconto entre 0 e 100
 * @param  string  $tipoCliente  Tipo do cliente: 'pf', 'pj' ou 'gov'
 *
 * @return float Valor final já com desconto e impostos aplicados
 *
 * @throws InvalidArgumentException Se $subtotal for negativo
 * @throws InvalidArgumentException Se $desconto estiver fora do intervalo [0, 100]
 *
 * @example
 *   $valor = calcularValorFinal(100.0, 10.0, 'pj');
 *   // Subtotal com desconto: 90.00
 *   // Imposto PJ (15%): 13.50
 *   // Retorno: 103.50
 */
function calcularValorFinal(float $subtotal, float $desconto, string $tipoCliente): float
{
    // Validações
    if ($subtotal < 0) {
        throw new InvalidArgumentException("Subtotal não pode ser negativo.");
    }

    if ($desconto < 0 || $desconto > 100) {
        throw new InvalidArgumentException("Desconto deve estar entre 0 e 100.");
    }

    // Aplica desconto
    $valorComDesconto = $subtotal * (1 - $desconto / 100);

    // Define alíquota conforme tipo de cliente
    $aliquota = match ($tipoCliente) {
        'pf'  => 0.10,
        'pj'  => 0.15,
        'gov' => 0.00,
        default => throw new InvalidArgumentException("Tipo de cliente '{$tipoCliente}' inválido."),
    };

    $imposto = $valorComDesconto * $aliquota;

    return round($valorComDesconto + $imposto, 2);
}
```

### Tags PHPDoc Mais Utilizadas

| Tag            | Uso                                                      |
|----------------|----------------------------------------------------------|
| `@param`       | Descreve um parâmetro de entrada                         |
| `@return`      | Descreve o valor de retorno                              |
| `@throws`      | Documenta exceções que podem ser lançadas                |
| `@var`         | Tipo de uma propriedade ou variável                      |
| `@example`     | Exemplo de uso da função                                 |
| `@deprecated`  | Marca função como obsoleta; indica alternativa           |
| `@see`         | Referência a outra função/classe/URL relacionada         |
| `@since`       | Versão em que a função foi adicionada                    |
| `@todo`        | Indica algo a ser implementado ou melhorado              |
| `@internal`    | Indica que não deve ser usada fora do pacote/módulo      |

---

## 7. Boas Práticas Gerais

### Princípio da Responsabilidade Única (SRP)

> Uma função deve fazer **uma única coisa** e fazê-la bem.

```php
// ❌ Faz muita coisa — difícil de testar e reutilizar
function processarPedidoCompleto(int $pedidoId): void
{
    $pedido = $db->find($pedidoId);
    $total = 0;
    foreach ($pedido['itens'] as $item) {
        $total += $item['preco'] * $item['quantidade'];
    }
    $pedido['total'] = $total;
    $db->save($pedido);
    mail($pedido['email'], 'Confirmação', 'Seu pedido foi processado.');
    file_put_contents('log.txt', "Pedido {$pedidoId} processado.\n", FILE_APPEND);
}

// ✅ Responsabilidades separadas
function calcularTotalPedido(array $itens): float { /* ... */ }
function salvarPedido(array $pedido): void { /* ... */ }
function notificarClientePedidoProcessado(string $email, int $pedidoId): void { /* ... */ }
function registrarLogPedido(int $pedidoId, string $acao): void { /* ... */ }
```

### Imutabilidade — Evite Modificar Argumentos

```php
// ❌ Modifica o argumento original (efeito colateral inesperado)
function aplicarDesconto(array &$pedido, float $desconto): void
{
    $pedido['total'] *= (1 - $desconto / 100);
}

// ✅ Retorna novo valor sem alterar o original
function aplicarDesconto(array $pedido, float $desconto): array
{
    $pedido['total'] = round($pedido['total'] * (1 - $desconto / 100), 2);
    return $pedido;
}
```

### Evite Flags Booleanas como Parâmetros

```php
// ❌ O que significa `true` aqui? Obscuro.
function buscarUsuarios(bool $incluirInativos): array { }
buscarUsuarios(true);

// ✅ Use funções separadas ou enums
function buscarUsuariosAtivos(): array { }
function buscarTodosUsuarios(): array { }
```

### Tamanho da Função

> Uma função não deve ultrapassar **20–30 linhas**. Se ultrapassar, provavelmente está fazendo coisas demais.

### Use Exceções para Erros, não `false` ou `null`

```php
// ❌ Retornar false para indicar erro é ambíguo
function dividir(float $a, float $b): float|false
{
    if ($b == 0) return false;
    return $a / $b;
}

// ✅ Lance uma exceção descritiva
function dividir(float $a, float $b): float
{
    if ($b == 0.0) {
        throw new DivisionByZeroError("Divisor não pode ser zero.");
    }
    return $a / $b;
}
```

---

## 8. Exemplos Completos

### Exemplo 1 — Função de Validação

```php
<?php

declare(strict_types=1);

/**
 * Valida se um CPF brasileiro é válido.
 *
 * Verifica o formato e os dígitos verificadores do CPF.
 * Remove pontos e traços antes da validação.
 *
 * @param  string $cpf CPF no formato "000.000.000-00" ou "00000000000"
 *
 * @return bool true se o CPF é válido, false caso contrário
 *
 * @example
 *   validarCpf('529.982.247-25'); // true
 *   validarCpf('111.111.111-11'); // false (sequência repetida)
 */
function validarCpf(string $cpf): bool
{
    // Remove caracteres não numéricos
    $cpf = preg_replace('/\D/', '', $cpf);

    // Deve ter exatamente 11 dígitos
    if (strlen($cpf) !== 11) {
        return false;
    }

    // Rejeita sequências repetidas (ex: 111.111.111-11)
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Valida primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += (int) $cpf[$i] * (10 - $i);
    }

    $resto = $soma % 11;
    $digito1 = $resto < 2 ? 0 : 11 - $resto;

    if ((int) $cpf[9] !== $digito1) {
        return false;
    }

    // Valida segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += (int) $cpf[$i] * (11 - $i);
    }

    $resto = $soma % 11;
    $digito2 = $resto < 2 ? 0 : 11 - $resto;

    return (int) $cpf[10] === $digito2;
}
```

### Exemplo 2 — Função com Tratamento de Erros

```php
<?php

declare(strict_types=1);

/**
 * Lê e decodifica um arquivo JSON do sistema de arquivos.
 *
 * @param  string $caminho  Caminho absoluto ou relativo do arquivo
 *
 * @return array Dados decodificados do JSON
 *
 * @throws RuntimeException      Se o arquivo não existir ou não puder ser lido
 * @throws UnexpectedValueException Se o conteúdo não for um JSON válido
 *
 * @since 2.0.0
 */
function lerArquivoJson(string $caminho): array
{
    if (!file_exists($caminho)) {
        throw new RuntimeException("Arquivo não encontrado: {$caminho}");
    }

    if (!is_readable($caminho)) {
        throw new RuntimeException("Sem permissão de leitura: {$caminho}");
    }

    $conteudo = file_get_contents($caminho);

    if ($conteudo === false) {
        throw new RuntimeException("Falha ao ler o arquivo: {$caminho}");
    }

    $dados = json_decode($conteudo, associative: true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new UnexpectedValueException(
            "JSON inválido em '{$caminho}': " . json_last_error_msg()
        );
    }

    return $dados;
}
```

### Exemplo 3 — Função Recursiva Documentada

```php
<?php

declare(strict_types=1);

/**
 * Achata um array multidimensional em um único nível.
 *
 * Percorre recursivamente todos os níveis do array.
 * Chaves são ignoradas — o resultado sempre tem índices numéricos.
 *
 * @param  array $array  Array possivelmente multidimensional
 * @param  int   $profundidade Número máximo de níveis a achatar (-1 = ilimitado)
 *
 * @return array Array achatado em um único nível
 *
 * @example
 *   achatarArray([1, [2, 3], [4, [5, 6]]]);
 *   // Retorna: [1, 2, 3, 4, 5, 6]
 *
 *   achatarArray([1, [2, [3]]], profundidade: 1);
 *   // Retorna: [1, 2, [3]]
 */
function achatarArray(array $array, int $profundidade = -1): array
{
    $resultado = [];

    foreach ($array as $elemento) {
        $ehArrayAninhado = is_array($elemento);
        $podeAprofundar  = $profundidade === -1 || $profundidade > 0;

        if ($ehArrayAninhado && $podeAprofundar) {
            // Chamada recursiva reduzindo a profundidade permitida
            $novaProf  = $profundidade === -1 ? -1 : $profundidade - 1;
            $resultado = array_merge($resultado, achatarArray($elemento, $novaProf));
        } else {
            $resultado[] = $elemento;
        }
    }

    return $resultado;
}
```

---

## 9. Antipadrões: O Que Evitar

```php
// ❌ Usar variáveis globais dentro de funções
function calcularTotal(): float
{
    global $itens; // Dependência oculta, dificulta testes
    return array_sum(array_column($itens, 'preco'));
}

// ✅ Receba dependências como parâmetro
function calcularTotal(array $itens): float
{
    return array_sum(array_column($itens, 'preco'));
}

// ❌ Suprimir erros com @
$resultado = @file_get_contents($url);

// ✅ Trate o erro explicitamente
$resultado = file_get_contents($url);
if ($resultado === false) {
    throw new RuntimeException("Falha ao acessar: {$url}");
}

// ❌ Função com comportamento diferente pelo mesmo tipo de retorno
function buscar(int $id): array|string
{
    if (/* erro */) return "Erro ao buscar";
    return $registro;
}

// ✅ Lance exceção para erros
function buscar(int $id): array
{
    if (/* erro */) throw new RuntimeException("Erro ao buscar ID {$id}");
    return $registro;
}

// ❌ Comentário que apenas repete o código
$total = $a + $b; // soma a com b e guarda em total

// ✅ Comentário que explica o contexto ou decisão
$total = $valorBruto + $frete; // frete já inclui o seguro obrigatório
```

---

*Referências: [PSR-1](https://www.php-fig.org/psr/psr-1/) · [PSR-12](https://www.php-fig.org/psr/psr-12/) · [PHPDoc](https://docs.phpdoc.org/) · [PHP 8.x Docs](https://www.php.net/manual/pt_BR/)*