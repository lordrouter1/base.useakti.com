# Sugestões de Melhorias — Módulo NF-e / NFC-e

**Data:** 2026-03-26  
**Referência:** `docs/nfe/01_AUDITORIA_COMPLETA.md`  
**Objetivo:** Transformar o módulo fiscal em um componente robusto e profissional de ERP.

---

## Índice

1. [Prioridade Imediata (P0 — Bloqueadores)](#1-prioridade-imediata-p0--bloqueadores)
2. [Prioridade Alta (P1 — Essenciais)](#2-prioridade-alta-p1--essenciais)
3. [Prioridade Média (P2 — Importantes)](#3-prioridade-média-p2--importantes)
4. [Prioridade Baixa (P3 — Desejáveis)](#4-prioridade-baixa-p3--desejáveis)
5. [Roadmap Sugerido](#5-roadmap-sugerido)
6. [Detalhamento Técnico por Melhoria](#6-detalhamento-técnico-por-melhoria)

---

## 1. Prioridade Imediata (P0 — Bloqueadores)

Estes itens devem ser resolvidos **antes de qualquer emissão em produção**.

### P0.1 — Instalar dependências no composer.json

```json
{
    "require": {
        "nfephp-org/sped-nfe": "^5.0",
        "nfephp-org/sped-da": "^4.0"
    }
}
```

**Impacto:** Sem essas bibliotecas, nenhuma emissão funciona.

### P0.2 — Criar migrations SQL para tabelas NF-e

Criar arquivo `sql/update_YYYYMMDD_nfe_tables.sql` com:

```sql
-- Tabela: nfe_credentials
CREATE TABLE IF NOT EXISTS nfe_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cnpj VARCHAR(18),
    ie VARCHAR(20),
    razao_social VARCHAR(255),
    nome_fantasia VARCHAR(255),
    crt TINYINT DEFAULT 1 COMMENT '1=SN, 2=SN Exc, 3=Normal',
    uf VARCHAR(2),
    cod_municipio VARCHAR(10),
    municipio VARCHAR(100),
    logradouro VARCHAR(255),
    numero VARCHAR(20),
    bairro VARCHAR(100),
    cep VARCHAR(10),
    complemento VARCHAR(100),
    telefone VARCHAR(20),
    certificate_path VARCHAR(500),
    certificate_password TEXT,
    certificate_expiry DATE,
    environment ENUM('homologacao','producao') DEFAULT 'homologacao',
    serie_nfe INT DEFAULT 1,
    serie_nfce INT DEFAULT 1,
    proximo_numero INT DEFAULT 1,
    proximo_numero_nfce INT DEFAULT 1,
    csc_id VARCHAR(10),
    csc_token VARCHAR(100),
    tp_emis TINYINT DEFAULT 1 COMMENT '1=Normal, 5=Contingência FS-DA, 6=SVC-AN, 7=SVC-RS, 9=Contingência off-line NFC-e',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir registro padrão
INSERT INTO nfe_credentials (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

-- Tabela: nfe_documents
CREATE TABLE IF NOT EXISTS nfe_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    modelo TINYINT DEFAULT 55 COMMENT '55=NF-e, 65=NFC-e',
    numero INT NOT NULL,
    serie INT DEFAULT 1,
    status ENUM('rascunho','processando','autorizada','rejeitada','cancelada','denegada','corrigida','inutilizada') DEFAULT 'rascunho',
    natureza_op VARCHAR(100) DEFAULT 'VENDA DE MERCADORIA',
    valor_total DECIMAL(15,2) DEFAULT 0,
    valor_produtos DECIMAL(15,2) DEFAULT 0,
    valor_desconto DECIMAL(15,2) DEFAULT 0,
    valor_frete DECIMAL(15,2) DEFAULT 0,
    valor_icms DECIMAL(15,2) DEFAULT 0,
    valor_pis DECIMAL(15,2) DEFAULT 0,
    valor_cofins DECIMAL(15,2) DEFAULT 0,
    valor_ipi DECIMAL(15,2) DEFAULT 0,
    dest_cnpj_cpf VARCHAR(18),
    dest_nome VARCHAR(255),
    dest_ie VARCHAR(20),
    dest_uf VARCHAR(2),
    chave VARCHAR(44),
    protocolo VARCHAR(20),
    recibo VARCHAR(20),
    status_sefaz VARCHAR(10),
    motivo_sefaz VARCHAR(500),
    xml_envio LONGTEXT,
    xml_autorizado LONGTEXT,
    xml_cancelamento LONGTEXT,
    xml_correcao LONGTEXT,
    danfe_path VARCHAR(500),
    cancel_protocolo VARCHAR(20),
    cancel_motivo TEXT,
    cancel_date DATETIME,
    correcao_texto TEXT,
    correcao_seq INT DEFAULT 0,
    correcao_date DATETIME,
    emitted_at DATETIME,
    tp_emis TINYINT DEFAULT 1,
    contingencia_justificativa TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_chave (chave),
    INDEX idx_status (status),
    INDEX idx_numero_serie (numero, serie),
    INDEX idx_modelo (modelo),
    INDEX idx_emitted_at (emitted_at),
    CONSTRAINT fk_nfe_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: nfe_logs
CREATE TABLE IF NOT EXISTS nfe_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nfe_document_id INT,
    order_id INT,
    action VARCHAR(50) COMMENT 'init, status, emissao, cancelamento, correcao, inutilizacao, consulta',
    status VARCHAR(20) COMMENT 'success, error, info, warning',
    code_sefaz VARCHAR(10),
    message TEXT,
    xml_request LONGTEXT,
    xml_response LONGTEXT,
    user_id INT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nfe_doc (nfe_document_id),
    INDEX idx_order (order_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    CONSTRAINT fk_nfe_log_doc FOREIGN KEY (nfe_document_id) REFERENCES nfe_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: nfe_document_items (NOVA — para relatórios fiscais)
CREATE TABLE IF NOT EXISTS nfe_document_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nfe_document_id INT NOT NULL,
    item_seq INT NOT NULL COMMENT 'Sequência do item na NF-e',
    product_id INT,
    product_code VARCHAR(60),
    product_name VARCHAR(255),
    ncm VARCHAR(8),
    cfop VARCHAR(4),
    cest VARCHAR(7),
    unit VARCHAR(10) DEFAULT 'UN',
    quantity DECIMAL(15,4) DEFAULT 1,
    unit_price DECIMAL(15,10) DEFAULT 0,
    total_price DECIMAL(15,2) DEFAULT 0,
    discount DECIMAL(15,2) DEFAULT 0,
    icms_cst VARCHAR(3),
    icms_base DECIMAL(15,2) DEFAULT 0,
    icms_aliquota DECIMAL(5,2) DEFAULT 0,
    icms_valor DECIMAL(15,2) DEFAULT 0,
    pis_cst VARCHAR(2),
    pis_valor DECIMAL(15,2) DEFAULT 0,
    cofins_cst VARCHAR(2),
    cofins_valor DECIMAL(15,2) DEFAULT 0,
    ipi_cst VARCHAR(2),
    ipi_valor DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nfe_doc (nfe_document_id),
    INDEX idx_ncm (ncm),
    INDEX idx_cfop (cfop),
    CONSTRAINT fk_nfe_item_doc FOREIGN KEY (nfe_document_id) REFERENCES nfe_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: nfe_correction_history (NOVA — histórico de CC-e)
CREATE TABLE IF NOT EXISTS nfe_correction_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nfe_document_id INT NOT NULL,
    seq_evento INT NOT NULL,
    texto TEXT NOT NULL,
    protocolo VARCHAR(20),
    xml_response LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nfe_doc (nfe_document_id),
    CONSTRAINT fk_nfe_corr_doc FOREIGN KEY (nfe_document_id) REFERENCES nfe_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### P0.3 — Adicionar proteção CSRF

Em cada método POST dos controllers (`emit`, `cancel`, `correction`, `store`):

```php
// No início do método
\Akti\Middleware\CsrfMiddleware::validate();
```

E nos formulários/chamadas AJAX, incluir o token CSRF:

```javascript
// AJAX headers
headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }
```

### P0.4 — Adicionar verificação de permissão

Adicionar no construtor dos controllers:

```php
// Verificar permissão
if (!checkPermission('nfe_documents', 'view')) {
    http_response_code(403);
    // Redirecionar ou mostrar erro
    exit;
}
```

E nos métodos de escrita:

```php
if (!checkPermission('nfe_documents', 'edit')) { ... }
```

### P0.5 — Implementar retry na consulta de recibo

```php
// Substituir o sleep(3) fixo por um loop de retry com backoff
$maxRetries = 5;
$waitSeconds = [3, 5, 10, 15, 30];

for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
    sleep($waitSeconds[$attempt]);
    $respRecibo = $this->tools->sefazConsultaRecibo($recibo);
    $stRec = new Standardize($respRecibo);
    $stdRec = $stRec->toStd();
    
    $cStat = $stdRec->cStat ?? '';
    
    // 104 = Lote processado
    if ($cStat == '104') break;
    
    // 105 = Em processamento — retry
    if ($cStat == '105') continue;
    
    // Outro status — sair do loop
    break;
}
```

### P0.6 — Corrigir chave de criptografia

Usar variável de ambiente ou arquivo de configuração seguro:

```php
private static function getEncryptionKey(): string
{
    // Prioridade: variável de ambiente > arquivo .env > fallback
    $appKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? '');
    if (empty($appKey)) {
        $envFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . '.env';
        if (file_exists($envFile)) {
            // Parsear .env
        }
    }
    if (empty($appKey)) {
        // Fallback atual (temporário)
        $tenantDb = $_SESSION['tenant']['db_name'] ?? 'akti_default';
        $appKey = $tenantDb;
    }
    return hash('sha256', $appKey . 'akti_nfe_cert_v2', true);
}
```

---

## 2. Prioridade Alta (P1 — Essenciais)

### P1.1 — Implementar cálculo dinâmico de impostos

Criar um serviço `TaxCalculator`:

```
app/services/TaxCalculator.php
```

Responsabilidades:
- Determinar ICMS conforme CRT (1=SN, 2=SN Exc, 3=Normal)
- Calcular PIS/COFINS conforme regime (Cumulativo vs Não Cumulativo)
- Aplicar CST/CSOSN corretos por produto
- Calcular ST (Substituição Tributária) quando aplicável
- Calcular DIFAL para operações interestaduais
- Calcular IPI quando aplicável

Campos necessários no cadastro de produtos:
- NCM (obrigatório)
- CFOP padrão
- CST/CSOSN de ICMS
- CST de PIS
- CST de COFINS
- Alíquotas

### P1.2 — Implementar NFC-e (modelo 65)

**Alterações necessárias:**

1. **NfeService::initTools()** — Aceitar modelo como parâmetro:
```php
private function initTools(int $modelo = 55): bool
{
    // ...
    $this->tools->model((string) $modelo);
    // ...
}
```

2. **NfeXmlBuilder** — Adaptar para modelo 65:
- `mod = 65`
- `tpImp = 4` (DANFE NFC-e)
- `idDest = 1` (sempre interno)
- `indFinal = 1` (sempre consumidor final)
- `indPres = 1` (sempre presencial) ou `4` (NFC-e entrega em domicílio)
- Tag `dest` é opcional (se CPF informado)
- Gerar QR Code usando CSC

3. **Novo: NfceQrCodeGenerator** — Gerar QR Code para DANFE NFC-e

4. **NfePdfGenerator** — Adicionar geração de DANFE-NFC-e (formato cupom)

5. **NfeCredential** — Separar séries e numeração para NF-e e NFC-e

### P1.3 — Implementar inutilização de numeração

```php
// NfeService
public function inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55): array
{
    if (!$this->initTools($modelo)) { /* ... */ }
    
    $serie = ($modelo == 65) 
        ? (int) ($this->credentials['serie_nfce'] ?? 1)
        : (int) ($this->credentials['serie_nfe'] ?? 1);
    
    $cnpj = preg_replace('/\D/', '', $this->credentials['cnpj']);
    $ano = date('y');
    
    $response = $this->tools->sefazInutiliza($serie, $numInicial, $numFinal, $justificativa);
    // Processar resposta...
}
```

Adicionar rota e action no controller.

### P1.4 — Implementar emissão em contingência

Tipos de contingência:
- **SVC-AN / SVC-RS** — Serviço Virtual de Contingência
- **EPEC** — Evento Prévio de Emissão em Contingência
- **FS-DA** — Formulário de Segurança (Documento Auxiliar)
- **Contingência Off-line** (NFC-e)

```php
// NfeService
public function setContingency(int $tpEmis, string $justificativa): bool
{
    $this->tools->contingency->activate($tpEmis, $justificativa);
    $this->credModel->update([
        'tp_emis' => $tpEmis,
    ]);
    return true;
}

public function deactivateContingency(): bool
{
    $this->tools->contingency->deactivate();
    $this->credModel->update([
        'tp_emis' => 1,
    ]);
    return true;
}
```

### P1.5 — Validar XML contra schema XSD

```php
// Antes de enviar à SEFAZ
try {
    $xmlSigned = $this->tools->signNFe($xml);
    
    // Validar contra schema
    $validator = new \NFePHP\NFe\Common\ValidTxt();
    // ou usar DOMDocument::schemaValidate
    $dom = new \DOMDocument();
    $dom->loadXML($xmlSigned);
    $xsdPath = 'vendor/nfephp-org/sped-nfe/schemas/...';
    if (!$dom->schemaValidate($xsdPath)) {
        throw new \RuntimeException('XML inválido conforme schema XSD');
    }
    
} catch (\Exception $e) { /* ... */ }
```

### P1.6 — Tornar NCM obrigatório

No cadastro de produtos:
- Campo NCM obrigatório
- Validação com 8 dígitos
- Tabela de NCM para autocompletar (pode usar API externa)

No momento da emissão, rejeitar se algum item tiver NCM inválido.

### P1.7 — Calcular idDest dinamicamente

```php
$ufEmitente = $this->emitente['uf'] ?? 'RS';
$ufDestinatario = $this->orderData['customer_uf'] ?? $ufEmitente;

if ($ufDestinatario == $ufEmitente) {
    $ide->idDest = 1; // Operação interna
} elseif (in_array($ufDestinatario, ['EX'])) {
    $ide->idDest = 3; // Exportação
} else {
    $ide->idDest = 2; // Operação interestadual
}
```

### P1.8 — Verificar prazo de cancelamento

```php
// NfeService::cancel()
$emittedAt = new \DateTime($doc['emitted_at']);
$now = new \DateTime();
$diffHours = ($now->getTimestamp() - $emittedAt->getTimestamp()) / 3600;

$maxHours = 24; // Padrão, mas varia por UF
if ($diffHours > $maxHours) {
    return [
        'success' => false,
        'message' => sprintf(
            'O prazo para cancelamento expirou. A NF-e foi emitida há %.1f horas (máximo: %d horas).',
            $diffHours, $maxHours
        ),
    ];
}
```

---

## 3. Prioridade Média (P2 — Importantes)

### P2.1 — Salvar XMLs em disco

```php
// Após autorização, salvar em disco
$basePath = 'storage/nfe/' . date('Y/m') . '/';
if (!is_dir($basePath)) mkdir($basePath, 0755, true);

$xmlPath = $basePath . $chave . '-nfe.xml';
file_put_contents($xmlPath, $xmlAutorizado);

$danfePath = $basePath . $chave . '-danfe.pdf';
NfePdfGenerator::generate($xmlAutorizado, $danfePath);
```

Estrutura sugerida:
```
storage/nfe/
├── 2026/
│   ├── 01/
│   │   ├── 35260612345678000100550010000001011234567890-nfe.xml
│   │   ├── 35260612345678000100550010000001011234567890-danfe.pdf
│   │   └── 35260612345678000100550010000001011234567890-cancel.xml
│   ├── 02/
│   └── ...
```

### P2.2 — Geração automática de DANFE

No evento `model.nfe_document.authorized`:

```php
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) {
    $data = $event->getData();
    // Gerar e salvar DANFE automaticamente
    $doc = (new NfeDocument($db))->readOne($data['nfe_id']);
    if ($doc && $doc['xml_autorizado']) {
        $path = 'storage/nfe/' . date('Y/m') . '/' . $data['chave'] . '-danfe.pdf';
        NfePdfGenerator::generate($doc['xml_autorizado'], $path);
        (new NfeDocument($db))->update($data['nfe_id'], ['danfe_path' => $path]);
    }
});
```

### P2.3 — Envio de XML/DANFE por e-mail

```php
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) {
    // Enviar XML e DANFE ao destinatário por e-mail
    $order = /* carregar pedido */;
    $email = $order['customer_email'] ?? null;
    if ($email) {
        // Usar serviço de e-mail do sistema
        $mailer->send($email, 'NF-e Autorizada', $template, [
            'attachments' => [$xmlPath, $danfePath]
        ]);
    }
});
```

### P2.4 — Implementar tag cobr (fatura/duplicata)

```php
// No NfeXmlBuilder, após pag
if (!empty($this->orderData['parcelas'])) {
    $fat = new \stdClass();
    $fat->nFat = $this->numero;
    $fat->vOrig = number_format($totalProd, 2, '.', '');
    $fat->vDesc = number_format($this->orderData['discount'] ?? 0, 2, '.', '');
    $fat->vLiq = number_format($this->orderData['total_amount'] ?? $totalProd, 2, '.', '');
    $nfe->tagfat($fat);

    foreach ($this->orderData['parcelas'] as $i => $parc) {
        $dup = new \stdClass();
        $dup->nDup = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        $dup->dVenc = $parc['vencimento'];
        $dup->vDup = number_format($parc['valor'], 2, '.', '');
        $nfe->tagdup($dup);
    }
}
```

### P2.5 — Implementar tag infRespTec

```php
$resp = new \stdClass();
$resp->CNPJ = '00000000000100'; // CNPJ do desenvolvedor
$resp->xContato = 'Akti Sistemas';
$resp->email = 'suporte@akti.com.br';
$resp->fone = '5100000000';
$nfe->taginfRespTec($resp);
```

### P2.6 — Implementar vTotTrib (Lei 12741)

Integrar com tabela IBPTax para cálculo automático do valor aproximado de tributos por produto.

### P2.7 — Histórico de Cartas de Correção

Ao invés de sobrescrever, inserir na tabela `nfe_correction_history` e manter apenas o último no documento.

### P2.8 — Limite de 20 CC-e

```php
if (($doc['correcao_seq'] ?? 0) >= 20) {
    return [
        'success' => false,
        'message' => 'Limite de 20 cartas de correção atingido para esta NF-e.',
    ];
}
```

### P2.9 — Manifestação do Destinatário

Implementar ações:
- Ciência da Operação (210200)
- Confirmação da Operação (210200)
- Desconhecimento da Operação (210220)
- Operação Não Realizada (210240)

### P2.10 — Consulta DistDFe

Para receber NF-e emitidas contra o CNPJ do emitente.

### P2.11 — Dashboard Fiscal Avançado

- Gráfico de NF-e por mês
- Ranking de CFOP mais utilizados
- Distribuição por UF de destino
- Valores de impostos por período
- NF-e pendentes de cancelamento (prazo expirando)

---

## 4. Prioridade Baixa (P3 — Desejáveis)

### P3.1 — Personalização do DANFE
- Logo da empresa no DANFE
- Informações adicionais configuráveis

### P3.2 — Webhook para integrações
- Disparar webhook em eventos NF-e
- Configurável por tenant

### P3.3 — Relatórios fiscais (SPED)
- Gerar arquivos SPED Fiscal
- Gerar SPED Contribuições

### P3.4 — Preview de XML antes da emissão
- Tela de revisão com todos os dados
- Possibilidade de editar antes de enviar

### P3.5 — Emissão em lote
- Selecionar múltiplos pedidos
- Emitir NF-e para todos em fila

### P3.6 — Reenvio automático
- Se a SEFAZ estiver offline, colocar em fila
- Processar via cron/agendamento

### P3.7 — Auditoria de acessos
- Registrar quem acessou, editou, emitiu cada NF-e
- Trilha de auditoria completa

---

## 5. Roadmap Sugerido

### Fase 1 — Fundamentos (1-2 semanas)
- [x] Auditoria completa ← **ESTA ENTREGA**
- [ ] P0.1 — Instalar bibliotecas sped-nfe e sped-da
- [ ] P0.2 — Criar migrations SQL
- [ ] P0.3 — CSRF em todos os endpoints
- [ ] P0.4 — Permissões nos controllers
- [ ] P0.5 — Retry na consulta de recibo
- [ ] P0.6 — Chave de criptografia segura

### Fase 2 — Emissão Robusta (2-3 semanas)
- [ ] P1.1 — Cálculo dinâmico de impostos (TaxCalculator)
- [ ] P1.5 — Validação XSD
- [ ] P1.6 — NCM obrigatório
- [ ] P1.7 — idDest dinâmico
- [ ] P1.8 — Verificar prazo de cancelamento
- [ ] P2.1 — Salvar XMLs em disco
- [ ] P2.2 — Geração automática de DANFE
- [ ] P2.5 — Tag infRespTec
- [ ] P2.8 — Limite de CC-e

### Fase 3 — NFC-e e Contingência (2-3 semanas)
- [ ] P1.2 — Implementar NFC-e
- [ ] P1.3 — Inutilização de numeração
- [ ] P1.4 — Emissão em contingência
- [ ] P2.4 — Tag cobr (fatura/duplicata)

### Fase 4 — Integração e Polimento (2-3 semanas)
- [ ] P2.3 — Envio de XML/DANFE por e-mail
- [ ] P2.6 — vTotTrib (IBPTax)
- [ ] P2.7 — Histórico de CC-e
- [ ] P2.9 — Manifestação do destinatário
- [ ] P2.10 — Consulta DistDFe
- [ ] P2.11 — Dashboard fiscal avançado

### Fase 5 — Avançado (Contínuo)
- [ ] P3.1 a P3.7 — Funcionalidades avançadas

---

## 6. Detalhamento Técnico por Melhoria

### 6.1 — Arquitetura alvo do TaxCalculator

```
app/services/TaxCalculator.php
├── calculateICMS(product, operation, destUF)
│   ├── Se CRT 1 ou 2 → CSOSN (101, 102, 103, 201, 202, 203, 300, 400, 500, 900)
│   └── Se CRT 3 → CST (00, 10, 20, 30, 40, 41, 50, 51, 60, 70, 90)
├── calculatePIS(product, regime)
│   ├── Regime Cumulativo → alíquota 0.65%
│   └── Regime Não Cumulativo → alíquota 1.65%
├── calculateCOFINS(product, regime)
│   ├── Regime Cumulativo → alíquota 3.00%
│   └── Regime Não Cumulativo → alíquota 7.60%
├── calculateIPI(product, operation)
├── calculateDIFAL(product, ufOrigem, ufDestino)
└── calculateTotal(items)
```

### 6.2 — Tabelas de apoio fiscal necessárias

| Tabela | Descrição |
|--------|-----------|
| `tax_cfop` | Tabela de CFOPs com descrições |
| `tax_ncm` | Tabela de NCMs com descrições e alíquotas |
| `tax_cest` | Tabela de CESTs vinculados a NCMs |
| `tax_ibptax` | Tabela IBPTax (atualizada periodicamente) |
| `tax_icms_interstate` | Alíquotas interestaduais por UF |

### 6.3 — Campos necessários no cadastro de produto

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `ncm` | VARCHAR(8) | **Obrigatório** |
| `cest` | VARCHAR(7) | Opcional |
| `cfop_venda_interna` | VARCHAR(4) | Default: 5102 |
| `cfop_venda_interestadual` | VARCHAR(4) | Default: 6102 |
| `icms_cst` | VARCHAR(3) | CST ou CSOSN |
| `icms_aliquota` | DECIMAL(5,2) | Alíquota interna |
| `icms_reducao_bc` | DECIMAL(5,2) | % redução BC |
| `pis_cst` | VARCHAR(2) | CST do PIS |
| `cofins_cst` | VARCHAR(2) | CST da COFINS |
| `ipi_cst` | VARCHAR(2) | CST do IPI |
| `ipi_aliquota` | DECIMAL(5,2) | Alíquota IPI |
| `origem` | TINYINT | 0=Nacional, 1-8=Importado |

### 6.4 — Estrutura do DANFE NFC-e

```
┌────────────────────────────┐
│     DADOS DA EMPRESA       │
│  CNPJ | IE | Endereço      │
├────────────────────────────┤
│  DANFE NFC-e               │
│  Documento Auxiliar de     │
│  Nota Fiscal de Consumidor │
│  Eletrônica                │
├────────────────────────────┤
│ QTD | DESCRIÇÃO   | VALOR  │
│  1  | Produto A   | 10,00  │
│  2  | Produto B   | 25,00  │
├────────────────────────────┤
│ TOTAL:              35,00  │
│ Forma Pgto: PIX     35,00  │
├────────────────────────────┤
│     ┌─────────┐            │
│     │ QR Code │            │
│     └─────────┘            │
│ Chave: 3526061234...       │
│ Protocolo: 1234567890      │
└────────────────────────────┘
```

---

> **Nota:** Este documento deve ser revisado e atualizado conforme as melhorias forem implementadas. Cada implementação deve gerar o arquivo SQL de migração correspondente na pasta `/sql`.
