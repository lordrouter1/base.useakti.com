# Mapeamento de Funções — Módulo NF-e / NFC-e

**Data:** 2026-03-26  
**Referência:** `docs/nfe/01_AUDITORIA_COMPLETA.md`  
**Objetivo:** Documentar todas as funções, métodos e suas assinaturas para referência rápida.

---

## 1. NfeService (`app/services/NfeService.php`)

| Método | Visibilidade | Retorno | Descrição |
|--------|-------------|---------|-----------|
| `__construct(PDO $db)` | public | — | Inicializa models e carrega credenciais |
| `isLibraryAvailable()` | public | `bool` | Verifica se sped-nfe está instalada |
| `initTools()` | private | `bool` | Inicializa NFePHP\NFe\Tools com credenciais do tenant |
| `testConnection()` | public | `array` | Testa conexão com SEFAZ (sefazStatus) |
| `emit(int $orderId, array $orderData)` | public | `array` | Fluxo completo de emissão NF-e |
| `cancel(int $nfeId, string $motivo)` | public | `array` | Cancela NF-e autorizada |
| `correction(int $nfeId, string $texto)` | public | `array` | Envia Carta de Correção (CC-e) |
| `checkStatus(int $nfeId)` | public | `array` | Consulta NF-e pela chave na SEFAZ |
| `getCredentials()` | public | `array` | Retorna credenciais carregadas |

### Retornos padrão

**emit():**
```php
[
    'success' => bool,
    'message' => string,
    'nfe_id'  => int|null,
    'chave'   => string|null,
]
```

**cancel() / correction():**
```php
[
    'success' => bool,
    'message' => string,
]
```

**testConnection() / checkStatus():**
```php
[
    'success' => bool,
    'message' => string,
    'details' => [
        'cStat'   => string,
        'xMotivo' => string,
        'tMed'    => string, // apenas testConnection
    ],
]
```

### Fluxo interno de emit()

```
emit($orderId, $orderData)
│
├── credModel->validateForEmission()
│   └── Verifica campos obrigatórios
│
├── initTools()
│   ├── Lê certificado PFX
│   ├── Decodifica senha (AES-256-CBC)
│   ├── Monta config JSON
│   └── new \NFePHP\NFe\Tools($configJson, $certificate)
│
├── [TRANSACTION]
│   ├── credModel->getNextNumberForUpdate() → SELECT ... FOR UPDATE
│   ├── docModel->create() → INSERT nfe_documents
│   └── credModel->incrementNextNumber() → UPDATE proximo_numero
│
├── new NfeXmlBuilder($cred, $orderData, $numero, $serie)
│   └── ->build() → XML não assinado
│
├── tools->signNFe($xml) → XML assinado
│
├── tools->sefazEnviaLote([$xmlSigned], $idLote) → Envio
│   └── Verifica cStat == '103' (aceito)
│
├── sleep(3)
│
├── tools->sefazConsultaRecibo($recibo) → Consulta
│   └── Verifica protNFe->infProt->cStat == '100'
│
├── Complements::toAuthorize($xmlSigned, $respRecibo) → procNFe
│
└── docModel->markAuthorized($nfeId, $chave, $protocolo, $xmlAutorizado)
    ├── Atualiza nfe_documents
    ├── Atualiza orders
    └── Dispara evento 'model.nfe_document.authorized'
```

---

## 2. NfeXmlBuilder (`app/services/NfeXmlBuilder.php`)

| Método | Visibilidade | Retorno | Descrição |
|--------|-------------|---------|-----------|
| `__construct(array $emitente, array $orderData, int $numero, int $serie)` | public | — | Recebe dados para montagem |
| `build()` | public | `string` | Monta e retorna XML NF-e 4.00 |
| `mapPaymentMethod(string $method)` | private | `string` | Mapeia forma de pagamento para código NF-e |
| `getCodeUF(string $uf)` | private | `int` | Retorna código IBGE da UF |

### Mapeamento de formas de pagamento

| Entrada | Código NF-e | Descrição |
|---------|-------------|-----------|
| `dinheiro` | 01 | Dinheiro |
| `cheque` | 02 | Cheque |
| `cartao_credito` / `credit_card` | 03 | Cartão de Crédito |
| `cartao_debito` / `debit_card` | 04 | Cartão de Débito |
| `pix` | 17 | PIX |
| `boleto` | 15 | Boleto Bancário |
| `transferencia` | 18 | Transferência Bancária |
| `outros` / (default) | 99 | Outros |

### Tags XML construídas (em ordem)

1. `infNFe` (versão 4.00)
2. `ide` (identificação)
3. `emit` (emitente)
4. `enderEmit` (endereço emitente)
5. `dest` (destinatário)
6. `enderDest` (endereço destinatário)
7. `prod[]` (produtos/itens)
8. `imposto[]` (impostos por item)
   - `ICMSSN` (CSOSN 102)
   - `PIS` (CST 99, zerado)
   - `COFINS` (CST 99, zerado)
9. `ICMSTot` (totais de impostos)
10. `transp` (transporte, modFrete=9)
11. `pag` + `detPag` (pagamento)
12. `infAdic` (informações adicionais)

---

## 3. NfePdfGenerator (`app/services/NfePdfGenerator.php`)

| Método | Visibilidade | Retorno | Descrição |
|--------|-------------|---------|-----------|
| `generate(string $xml, string $outputPath)` | public static | `bool` | Gera DANFE e salva em arquivo |
| `renderToString(string $xml)` | public static | `string\|null` | Gera DANFE e retorna como binário |

---

## 4. NfeDocument (`app/models/NfeDocument.php`)

| Método | Visibilidade | Retorno | Descrição |
|--------|-------------|---------|-----------|
| `__construct($db)` | public | — | Recebe PDO |
| `create(array $data)` | public | `int` | Insere registro, retorna ID |
| `readOne(int $id)` | public | `array\|false` | Busca por ID |
| `readByOrder(int $orderId)` | public | `array\|false` | Última NF-e do pedido |
| `readAllByOrder(int $orderId)` | public | `array` | Todas NF-e do pedido |
| `readPaginated(array $filters, int $page, int $perPage)` | public | `array` | Listagem paginada |
| `update(int $id, array $data)` | public | `bool` | Atualiza campos permitidos |
| `markAuthorized(int $id, string $chave, string $protocolo, string $xml)` | public | `bool` | Marca autorizada + sync orders |
| `markCancelled(int $id, string $protocolo, string $motivo, string $xml)` | public | `bool` | Marca cancelada + sync orders |
| `countByStatus()` | public | `array` | Contagem agrupada |
| `countThisMonth()` | public | `int` | Quantidade do mês |
| `sumAuthorizedThisMonth()` | public | `float` | Soma de autorizadas do mês |

### Campos permitidos para update()

```php
$allowedFields = [
    'chave', 'protocolo', 'recibo',
    'status', 'status_sefaz', 'motivo_sefaz',
    'xml_envio', 'xml_autorizado', 'xml_cancelamento', 'xml_correcao',
    'danfe_path',
    'cancel_protocolo', 'cancel_motivo', 'cancel_date',
    'correcao_texto', 'correcao_seq', 'correcao_date',
    'emitted_at',
];
```

### Eventos disparados

| Método | Evento |
|--------|--------|
| `create()` | `model.nfe_document.created` |
| `update()` | `model.nfe_document.updated` |
| `markAuthorized()` | `model.nfe_document.authorized` |
| `markCancelled()` | `model.nfe_document.cancelled` |

### Filtros de readPaginated()

| Filtro | Campo | Tipo |
|--------|-------|------|
| `status` | `n.status = :status` | string |
| `month` | `MONTH(n.created_at) = :month` | int |
| `year` | `YEAR(n.created_at) = :year` | int |
| `search` | `n.numero LIKE / n.chave LIKE / n.dest_nome LIKE` | string |

---

## 5. NfeCredential (`app/models/NfeCredential.php`)

| Método | Visibilidade | Retorno | Descrição |
|--------|-------------|---------|-----------|
| `__construct($db)` | public | — | Recebe PDO |
| `get()` | public | `array\|false` | Retorna credenciais (id=1) |
| `update(array $data)` | public | `bool` | Atualiza credenciais |
| `getNextNumberForUpdate()` | public | `int` | Próximo número com FOR UPDATE |
| `incrementNextNumber()` | public | `bool` | Incrementa proximo_numero |
| `validateForEmission()` | public | `array` | Valida campos obrigatórios |
| `encryptPassword(string $pwd)` | public static | `string` | Criptografa senha do certificado |
| `decryptPassword(string $enc)` | public static | `string` | Descriptografa senha |
| `getEncryptionKey()` | private static | `string` | Retorna chave AES-256 |

### Campos obrigatórios para emissão

```php
$required = [
    'cnpj', 'ie', 'razao_social', 'uf', 'cod_municipio',
    'logradouro', 'numero', 'bairro', 'cep', 'certificate_path',
];
```

---

## 6. NfeLog (`app/models/NfeLog.php`)

| Método | Visibilidade | Retorno | Descrição |
|--------|-------------|---------|-----------|
| `__construct($db)` | public | — | Recebe PDO |
| `create(array $data)` | public | `int` | Cria log, retorna ID |
| `getByDocument(int $docId)` | public | `array` | Logs por documento |
| `getByOrder(int $orderId)` | public | `array` | Logs por pedido |
| `getRecent(int $limit = 50)` | public | `array` | Logs recentes |

### Campos do log

```php
[
    'nfe_document_id' => int|null,
    'order_id'        => int|null,
    'action'          => string, // init, status, emissao, cancelamento, correcao, consulta_recibo, consulta
    'status'          => string, // success, error, info, warning
    'code_sefaz'      => string|null,
    'message'         => string|null,
    'xml_request'     => string|null,
    'xml_response'    => string|null,
    'user_id'         => int|null, // auto-preenchido de $_SESSION
    'ip_address'      => string|null, // auto-preenchido de $_SERVER
]
```

---

## 7. NfeDocumentController (`app/controllers/NfeDocumentController.php`)

| Método | Tipo HTTP | Retorno | Descrição |
|--------|-----------|---------|-----------|
| `__construct()` | — | — | Verifica módulo NF-e ativo |
| `index()` | GET | HTML | Painel de NF-e com filtros |
| `emit()` | POST (AJAX) | JSON | Emite NF-e para pedido |
| `cancel()` | POST (AJAX) | JSON | Cancela NF-e |
| `correction()` | POST (AJAX) | JSON | Envia Carta de Correção |
| `download()` | GET | File | Download XML/DANFE |
| `checkStatus()` | GET/POST (AJAX) | JSON | Consulta status na SEFAZ |
| `detail()` | GET | HTML | Detalhe da NF-e |

### Parâmetros de entrada

**emit():**
- `order_id` (POST, int) — ID do pedido

**cancel():**
- `nfe_id` (POST, int) — ID da NF-e
- `motivo` (POST, string) — Justificativa (mín 15 chars)

**correction():**
- `nfe_id` (POST, int) — ID da NF-e
- `texto` (POST, string) — Texto da correção (mín 15 chars)

**download():**
- `id` (GET, int) — ID da NF-e
- `type` (GET, string) — 'xml' | 'danfe' | 'xml_cancel' | 'xml_correcao'

**checkStatus():**
- `id` (GET, int) ou `nfe_id` (POST, int)

**detail():**
- `id` (GET, int) — ID da NF-e

---

## 8. NfeCredentialController (`app/controllers/NfeCredentialController.php`)

| Método | Tipo HTTP | Retorno | Descrição |
|--------|-----------|---------|-----------|
| `__construct()` | — | — | Verifica módulo NF-e ativo |
| `index()` | GET | HTML | Formulário de credenciais |
| `store()` | POST | Redirect | Salva/atualiza credenciais |
| `update()` | POST | Redirect | Alias para store() |
| `testConnection()` | GET/POST (AJAX) | JSON | Testa conexão SEFAZ |

### Parâmetros de store()

```
cnpj, ie, razao_social, nome_fantasia, crt, uf, cod_municipio, municipio,
logradouro, numero, bairro, cep, complemento, telefone,
serie_nfe, proximo_numero, csc_id, csc_token, environment,
certificate (FILE), certificate_password
```

---

## 9. Eventos (app/bootstrap/events.php)

| Evento | Payload | Ação do Listener |
|--------|---------|-----------------|
| `model.nfe_document.authorized` | `nfe_id`, `order_id`, `chave` | error_log + storage/logs/nfe.log |
| `model.nfe_document.cancelled` | `nfe_id`, `order_id` | error_log + storage/logs/nfe.log |
| `model.nfe_document.error` | `nfe_id`, `order_id`, `code`, `message` | error_log + storage/logs/nfe.log |
| `model.nfe_document.created` | `id`, `order_id`, `numero` | (sem listener) |
| `model.nfe_document.updated` | `id`, `fields` | (sem listener) |
| `model.nfe_credential.updated` | `fields` (sem password) | (sem listener) |

---

## 10. Rotas (app/config/routes.php)

```php
'nfe_credentials' => [
    'controller'     => 'NfeCredentialController',
    'default_action' => 'index',
    'actions'        => [
        'store'          => 'store',
        'update'         => 'update',
        'testConnection' => 'testConnection',
    ],
],

'nfe_documents' => [
    'controller'     => 'NfeDocumentController',
    'default_action' => 'index',
    'actions'        => [
        'emit'        => 'emit',
        'cancel'      => 'cancel',
        'correction'  => 'correction',
        'download'    => 'download',
        'checkStatus' => 'checkStatus',
        'detail'      => 'detail',
    ],
],
```

---

## 11. Funções Ausentes (a implementar)

| Função | Prioridade | Descrição |
|--------|-----------|-----------|
| `NfeService::inutilizar()` | P1 | Inutilização de numeração |
| `NfeService::setContingency()` | P1 | Ativar emissão em contingência |
| `NfeService::manifestar()` | P2 | Manifestação do destinatário |
| `NfeService::consultaDistDFe()` | P2 | Consulta documentos fiscais |
| `NfeService::emitNfce()` | P1 | Emissão de NFC-e (modelo 65) |
| `TaxCalculator::calculate()` | P1 | Cálculo dinâmico de impostos |
| `NfceQrCodeGenerator::generate()` | P1 | QR Code para NFC-e |
| `NfeDocumentController::inutilizar()` | P1 | Tela de inutilização |
| `NfeDocumentController::emitBatch()` | P3 | Emissão em lote |
