# Auditoria Completa — Módulo NF-e / NFC-e

**Data:** 2026-03-26  
**Versão do Sistema:** Akti - Gestão em Produção  
**Autor:** Auditoria automatizada  
**Escopo:** Análise completa do fluxo de emissão fiscal eletrônica (NF-e e NFC-e), incluindo arquitetura, configurações, eventos, segurança, banco de dados, e conformidade com padrões profissionais de ERP.

---

## Índice

1. [Visão Geral da Arquitetura](#1-visão-geral-da-arquitetura)
2. [Mapeamento de Arquivos](#2-mapeamento-de-arquivos)
3. [Fluxo de Emissão (NF-e)](#3-fluxo-de-emissão-nf-e)
4. [Fluxo de Cancelamento](#4-fluxo-de-cancelamento)
5. [Fluxo de Carta de Correção (CC-e)](#5-fluxo-de-carta-de-correção-cc-e)
6. [Gerenciamento de Credenciais SEFAZ](#6-gerenciamento-de-credenciais-sefaz)
7. [Construção do XML (NfeXmlBuilder)](#7-construção-do-xml-nfexmlbuilder)
8. [Geração de DANFE (NfePdfGenerator)](#8-geração-de-danfe-nfepdfgenerator)
9. [Modelo de Dados (NfeDocument)](#9-modelo-de-dados-nfedocument)
10. [Sistema de Logs (NfeLog)](#10-sistema-de-logs-nfelog)
11. [Sistema de Eventos](#11-sistema-de-eventos)
12. [Rotas e Menu](#12-rotas-e-menu)
13. [Views (Interface)](#13-views-interface)
14. [Dependências Externas](#14-dependências-externas)
15. [Situação da NFC-e](#15-situação-da-nfc-e)
16. [Análise de Segurança](#16-análise-de-segurança)
17. [Análise do Banco de Dados](#17-análise-do-banco-de-dados)
18. [Resumo de Problemas Encontrados](#18-resumo-de-problemas-encontrados)

---

## 1. Visão Geral da Arquitetura

O módulo NF-e segue a arquitetura MVC do sistema Akti:

```
┌────────────────────────────────┐
│        Controller Layer         │
│  NfeDocumentController.php     │
│  NfeCredentialController.php   │
└──────────┬─────────────────────┘
           │
┌──────────▼─────────────────────┐
│        Service Layer            │
│  NfeService.php (SEFAZ)        │
│  NfeXmlBuilder.php (XML)       │
│  NfePdfGenerator.php (DANFE)   │
└──────────┬─────────────────────┘
           │
┌──────────▼─────────────────────┐
│         Model Layer             │
│  NfeDocument.php (docs)        │
│  NfeCredential.php (creds)     │
│  NfeLog.php (logs)             │
└──────────┬─────────────────────┘
           │
┌──────────▼─────────────────────┐
│     Biblioteca Externa          │
│  NFePHP\NFe\Tools (sped-nfe)   │
│  NFePHP\DA\Danfe (sped-da)     │
└────────────────────────────────┘
```

**Pontos positivos:**
- Separação MVC respeitada
- Camada de serviço intermediária (NfeService) isola lógica de comunicação
- Uso de eventos para notificação
- Multi-tenant (via ModuleBootloader)

**Pontos de atenção:**
- A biblioteca `nfephp-org/sped-nfe` **NÃO está no `composer.json`** como dependência
- A biblioteca `nfephp-org/sped-da` (DANFE) **NÃO está no `composer.json`**
- Não há verificação de CSRF nos endpoints POST (emit, cancel, correction)
- Não há verificação de permissão nos controllers NF-e

---

## 2. Mapeamento de Arquivos

| Arquivo | Função | Linhas |
|---------|--------|--------|
| `app/controllers/NfeDocumentController.php` | Controller de documentos NF-e | 324 |
| `app/controllers/NfeCredentialController.php` | Controller de credenciais SEFAZ | 191 |
| `app/services/NfeService.php` | Serviço de comunicação SEFAZ | 608 |
| `app/services/NfeXmlBuilder.php` | Construtor de XML NF-e 4.00 | 268 |
| `app/services/NfePdfGenerator.php` | Gerador de DANFE (PDF) | 67 |
| `app/models/NfeDocument.php` | Model CRUD de documentos | 355 |
| `app/models/NfeCredential.php` | Model CRUD de credenciais | 198 |
| `app/models/NfeLog.php` | Model de logs de comunicação | 97 |
| `app/views/nfe/index.php` | Painel de notas fiscais | 430 |
| `app/views/nfe/detail.php` | Detalhe de uma NF-e | — |
| `app/views/nfe/credentials.php` | Formulário de credenciais | — |
| `app/bootstrap/events.php` | Listeners de eventos NF-e | (parcial) |
| `app/config/routes.php` | Rotas NF-e (linhas 572-595) | — |
| `app/config/menu.php` | Menu NF-e (linha 154) | — |

---

## 3. Fluxo de Emissão (NF-e)

### 3.1 Sequência Completa

```
[Controller: emit()]
    ├── Valida order_id via POST
    ├── Carrega pedido (Order::readOne)
    ├── Carrega itens (Order::getItems)
    ├── Monta array $orderData
    └── Chama NfeService::emit()

[Service: emit()]
    ├── 1. Valida credenciais (NfeCredential::validateForEmission)
    ├── 2. Inicializa Tools do sped-nfe (initTools)
    ├── 3. BEGIN TRANSACTION
    │   ├── Obtém próximo número com FOR UPDATE (lock)
    │   ├── Cria registro nfe_documents (status: 'processando')
    │   ├── Incrementa proximo_numero
    │   └── COMMIT
    ├── 4. Monta XML (NfeXmlBuilder::build)
    ├── 5. Salva xml_envio no banco
    ├── 6. Assina XML (Tools::signNFe)
    ├── 7. Envia lote (Tools::sefazEnviaLote)
    ├── 8. Loga resposta
    ├── 9. Verifica cStat == '103' (lote aceito)
    │   ├── Se não: marca 'rejeitada', dispara evento error
    │   └── Se sim: continua
    ├── 10. Salva recibo
    ├── 11. sleep(3) — aguarda processamento
    ├── 12. Consulta recibo (Tools::sefazConsultaRecibo)
    ├── 13. Verifica protNFe->infProt->cStat == '100'
    │   ├── Se '100': 
    │   │   ├── Monta procNFe (Complements::toAuthorize)
    │   │   ├── markAuthorized (atualiza doc + orders)
    │   │   ├── Dispara evento 'authorized'
    │   │   └── Retorna sucesso
    │   └── Se outro: marca 'rejeitada', dispara evento error
    └── 14. Retorna resultado JSON

[Model: markAuthorized()]
    ├── BEGIN TRANSACTION
    ├── Atualiza nfe_documents (chave, protocolo, xml_autorizado, status)
    ├── Atualiza orders (nfe_id, nfe_status, nf_number, nf_access_key, etc)
    ├── COMMIT
    └── Dispara evento 'model.nfe_document.authorized'
```

### 3.2 Status possíveis do documento

| Status | Descrição |
|--------|-----------|
| `rascunho` | Criado mas não enviado (não usado atualmente) |
| `processando` | Enviado à SEFAZ, aguardando retorno |
| `autorizada` | Autorizada pela SEFAZ (cStat 100) |
| `rejeitada` | Rejeitada pela SEFAZ |
| `cancelada` | Cancelada após autorização |
| `denegada` | Denegada pela SEFAZ (não implementado tratamento) |
| `corrigida` | Possui Carta de Correção |

### 3.3 Problemas Identificados na Emissão

| # | Problema | Severidade | Detalhes |
|---|----------|------------|----------|
| E1 | **sleep(3) fixo** | 🔴 CRÍTICO | Bloqueia a thread PHP por 3 segundos. Se a SEFAZ demorar, não há retry. Se for rápida, desperdiça tempo. |
| E2 | **Sem retry na consulta de recibo** | 🔴 CRÍTICO | Se a SEFAZ retornar "em processamento" (cStat 105), o sistema marca como resposta inesperada e para. Deveria fazer retry com backoff. |
| E3 | **Sem emissão em contingência** | 🔴 CRÍTICO | Se a SEFAZ estiver offline, não há fallback (SVC-AN, SVC-RS, EPEC, FS-DA). |
| E4 | **Modelo fixo em 55 (NF-e)** | 🟡 ALTO | O `initTools()` fixa `$this->tools->model('55')`. Não há suporte a NFC-e (modelo 65) apesar da UI de settings ter seleção. |
| E5 | **Dados do destinatário incompletos** | 🟡 ALTO | O mapeamento `customer_cpf` / `customer_cnpj` depende de campos específicos do pedido que podem não existir. Sem validação prévia. |
| E6 | **Sem validação de XML pré-envio** | 🟡 ALTO | O XML deveria ser validado contra o schema (XSD) antes de enviar à SEFAZ. |
| E7 | **Transação dupla** | 🟡 MÉDIO | `NfeService::emit()` faz `beginTransaction` para o número, e `markAuthorized()` faz outro `beginTransaction` internamente. Se o PDO não suportar savepoints, pode haver conflito. |
| E8 | **Sem inutilização de numeração** | 🟡 ALTO | Se um número é reservado mas a NF-e é rejeitada, o número fica "perdido". Não há funcionalidade de inutilização (sefazInutiliza). |
| E9 | **Sem geração automática de DANFE** | 🟡 MÉDIO | O DANFE só é gerado sob demanda (download). Deveria ser gerado e salvo automaticamente após autorização. |
| E10 | **Sem salvamento de XML em disco** | 🟡 MÉDIO | Os XMLs são salvos apenas no banco de dados. A legislação exige guarda dos XMLs por 5 anos; salvar também em disco é boa prática. |

---

## 4. Fluxo de Cancelamento

### 4.1 Sequência

```
[Controller: cancel()]
    ├── Valida nfe_id e motivo via POST
    └── Chama NfeService::cancel()

[Service: cancel()]
    ├── 1. Busca documento (readOne)
    ├── 2. Valida status == 'autorizada'
    ├── 3. Valida motivo >= 15 caracteres
    ├── 4. Inicializa Tools
    ├── 5. Chama Tools::sefazCancela(chave, motivo, protocolo)
    ├── 6. Verifica cStat == '135' ou '155'
    │   ├── Se sim: markCancelled (atualiza doc + orders)
    │   └── Se não: retorna erro
    └── 7. Loga resposta

[Model: markCancelled()]
    ├── BEGIN TRANSACTION
    ├── Atualiza nfe_documents (cancel_protocolo, cancel_motivo, etc)
    ├── Atualiza orders (nfe_status = 'cancelada')
    ├── COMMIT
    └── Dispara evento 'model.nfe_document.cancelled'
```

### 4.2 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| C1 | **Sem verificação de prazo** | 🔴 CRÍTICO | Cancelamento só é permitido em até 24h (na maioria das UFs). Sem essa verificação, o usuário tenta cancelar e recebe erro genérico da SEFAZ. |
| C2 | **Sem proteção CSRF** | 🔴 CRÍTICO | O endpoint POST de cancelamento não valida token CSRF. |
| C3 | **Sem registro do CNPJ no cancelamento** | 🟡 MÉDIO | O `sefazCancela` recebe chave, motivo e protocolo, mas a API da sped-nfe pode precisar do CNPJ como parâmetro adicional. |

---

## 5. Fluxo de Carta de Correção (CC-e)

### 5.1 Sequência

```
[Controller: correction()]
    ├── Valida nfe_id e texto via POST
    └── Chama NfeService::correction()

[Service: correction()]
    ├── 1. Busca documento
    ├── 2. Valida status in ('autorizada', 'corrigida')
    ├── 3. Valida texto >= 15 caracteres
    ├── 4. Calcula seqEvento (correcao_seq + 1)
    ├── 5. Chama Tools::sefazCCe(chave, texto, seqEvento)
    ├── 6. Verifica cStat == '135' ou '155'
    │   ├── Se sim: atualiza doc (status='corrigida', texto, seq, xml)
    │   └── Se não: retorna erro
    └── 7. Loga resposta
```

### 5.2 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| CC1 | **Sem limite de 20 CC-e** | 🟡 ALTO | A SEFAZ permite no máximo 20 cartas de correção por NF-e. Não há validação. |
| CC2 | **Sobrescrita de XML anterior** | 🟡 MÉDIO | Ao enviar nova CC-e, o `xml_correcao` anterior é sobrescrito. Deveria manter histórico. |
| CC3 | **Sem proteção CSRF** | 🔴 CRÍTICO | O endpoint POST não valida token CSRF. |

---

## 6. Gerenciamento de Credenciais SEFAZ

### 6.1 Campos armazenados (tabela `nfe_credentials`)

| Campo | Descrição | Criptografado |
|-------|-----------|---------------|
| `cnpj` | CNPJ do emitente | Não |
| `ie` | Inscrição Estadual | Não |
| `razao_social` | Razão Social | Não |
| `nome_fantasia` | Nome Fantasia | Não |
| `crt` | Código de Regime Tributário (1-3) | Não |
| `uf` | UF do emitente | Não |
| `cod_municipio` | Código IBGE do município | Não |
| `municipio` | Nome do município | Não |
| `logradouro` | Endereço | Não |
| `numero` | Número do endereço | Não |
| `bairro` | Bairro | Não |
| `cep` | CEP | Não |
| `complemento` | Complemento | Não |
| `telefone` | Telefone | Não |
| `certificate_path` | Caminho do certificado .pfx | Não |
| `certificate_password` | Senha do certificado | **Sim (AES-256-CBC)** |
| `certificate_expiry` | Validade do certificado | Não |
| `environment` | 'homologacao' ou 'producao' | Não |
| `serie_nfe` | Série da NF-e | Não |
| `proximo_numero` | Próximo número da NF-e | Não |
| `csc_id` | CSC ID (para NFC-e) | Não |
| `csc_token` | CSC Token (para NFC-e) | Não |

### 6.2 Validação para Emissão

O método `validateForEmission()` verifica campos obrigatórios:
- CNPJ, IE, Razão Social, UF, Código Município
- Logradouro, Número, Bairro, CEP
- Certificado Digital (existência do arquivo)

### 6.3 Criptografia da Senha

- Algoritmo: AES-256-CBC
- Chave derivada: `sha256(db_name + salt)`
- Salt fixo: `'akti_nfe_cert_v1'`
- IV: 16 bytes aleatórios
- Formato: `base64(iv::encrypted)`

### 6.4 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| CR1 | **Chave de criptografia fraca** | 🔴 CRÍTICO | A chave é derivada do nome do banco + salt fixo. Qualquer desenvolvedor com acesso ao código pode descriptografar. Deveria usar uma chave de ambiente (`APP_KEY`). |
| CR2 | **Sem validação do certificado ao upload** | 🟡 ALTO | Ao fazer upload do .pfx, se a senha não for informada no mesmo momento, não valida se o certificado é legível. |
| CR3 | **Sem alerta automático de expiração** | 🟡 MÉDIO | A expiração é calculada, mas só exibida na view. Deveria haver um cron/alerta para notificar X dias antes. |
| CR4 | **Registro único (id=1)** | 🟡 MÉDIO | Só suporta um set de credenciais por tenant. Se o tenant tiver múltiplas filiais/CNPJs emitindo NF-e, não suporta. |
| CR5 | **Certificado salvo em path absoluto** | 🟡 MÉDIO | `certificate_path` é salvo como caminho absoluto do servidor. Se mudar servidor, quebra. |
| CR6 | **Sem proteção CSRF no formulário store** | 🔴 CRÍTICO | O POST de credenciais não valida token CSRF. |

---

## 7. Construção do XML (NfeXmlBuilder)

### 7.1 Tags Implementadas

| Tag XML | Status | Observações |
|---------|--------|-------------|
| `infNFe` | ✅ Implementado | Versão 4.00 |
| `ide` | ✅ Implementado | `cNF` gerado com `rand()` — não seguro |
| `emit` | ✅ Implementado | Dados vêm das credenciais |
| `enderEmit` | ✅ Implementado | — |
| `dest` | ✅ Implementado | CPF/CNPJ auto-detectado pelo tamanho |
| `enderDest` | ✅ Implementado | — |
| `prod` (itens) | ✅ Implementado | NCM e CFOP com fallbacks |
| `imposto` | ⚠️ Parcial | **Apenas Simples Nacional (CSOSN 102)** |
| `ICMS` | ⚠️ Parcial | Apenas ICMS-SN. Sem ICMS normal, ST, DIFAL |
| `PIS` | ⚠️ Parcial | CST 99 fixo, valores zerados |
| `COFINS` | ⚠️ Parcial | CST 99 fixo, valores zerados |
| `IPI` | ❌ Não implementado | — |
| `II` | ❌ Não implementado | — |
| `ICMSTot` | ✅ Implementado | Valores de impostos fixos em zero |
| `transp` | ⚠️ Parcial | `modFrete=9` fixo (sem frete) |
| `vol` | ❌ Não implementado | Volumes de transporte |
| `cobr` | ❌ Não implementado | Fatura/Duplicatas |
| `pag` | ✅ Implementado | Mapeamento de formas de pagamento |
| `infAdic` | ✅ Implementado | Campo observação |
| `infRespTec` | ❌ Não implementado | Responsável técnico |
| `autXML` | ❌ Não implementado | Autorização de download XML por terceiros |

### 7.2 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| X1 | **Impostos fixos para Simples Nacional** | 🔴 CRÍTICO | O sistema só suporta CSOSN 102 (Simples Nacional sem permissão de crédito). Empresas do Lucro Presumido, Lucro Real ou mesmo outros CSOSNs do Simples não são suportadas. |
| X2 | **PIS/COFINS zerados** | 🔴 CRÍTICO | CST 99 com valores zerados. Deveria calcular conforme regime tributário e tipo de operação. |
| X3 | **NCM fallback '00000000'** | 🔴 CRÍTICO | NCM inválido causa rejeição na SEFAZ. Deveria ser obrigatório no cadastro de produtos. |
| X4 | **CFOP fallback '5102'** | 🟡 ALTO | CFOP fixo para venda de mercadoria. Não suporta devoluções, remessas, transferências, operações interestaduais (6xxx), etc. |
| X5 | **`cNF` gerado com `rand()`** | 🟡 ALTO | O código numérico deveria ser gerado de forma mais segura (ex: `random_int()`). |
| X6 | **`idDest` fixo em 1** | 🟡 ALTO | Fixo em "operação interna". Deveria variar conforme UF do destinatário vs. UF do emitente. |
| X7 | **`indPres` fixo em 1** | 🟡 MÉDIO | Fixo em "presencial". Deveria suportar "não presencial — internet" (2), "telemarketing" (4), etc. |
| X8 | **`modFrete` fixo em 9** | 🟡 MÉDIO | Sempre "sem frete". Deveria ser configurável conforme pedido. |
| X9 | **Sem tag `cobr` (fatura/duplicata)** | 🟡 MÉDIO | Vendas a prazo deveriam informar faturas e duplicatas. |
| X10 | **Sem tag `infRespTec`** | 🟡 BAIXO | Campo recomendado pela SEFAZ para identificar o desenvolvedor do software. |
| X11 | **Sem tag `CEST`** | 🟡 ALTO | Código Especificador da Substituição Tributária é obrigatório para diversos produtos. |
| X12 | **Sem tag `vTotTrib` (Lei 12741)** | 🟡 ALTO | Valor aproximado de tributos (IBPTax) é obrigatório na NF-e. |

---

## 8. Geração de DANFE (NfePdfGenerator)

### 8.1 Implementação Atual

- Usa `NFePHP\DA\NFe\Danfe` para gerar o PDF
- Dois métodos: `generate()` (salva em arquivo) e `renderToString()` (retorna binário)
- Fallback silencioso se a biblioteca não estiver instalada

### 8.2 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| D1 | **Biblioteca `sped-da` não está no `composer.json`** | 🔴 CRÍTICO | A dependência não está declarada. |
| D2 | **Sem DANFE-NFC-e (cupom)** | 🟡 ALTO | Não há geração de DANFE para modelo 65 (NFC-e) com QR Code. |
| D3 | **Sem personalização do DANFE** | 🟡 BAIXO | Logo, cores e dados adicionais da empresa não são configuráveis. |
| D4 | **`creditsIntegr498('')` pode não existir** | 🟡 BAIXO | Chamada de método que pode não existir em todas as versões da lib. |

---

## 9. Modelo de Dados (NfeDocument)

### 9.1 Campos da tabela `nfe_documents`

| Campo | Tipo (inferido) | Descrição |
|-------|-----------------|-----------|
| `id` | INT AUTO_INCREMENT | PK |
| `order_id` | INT | FK para orders |
| `numero` | INT | Número da NF-e |
| `serie` | INT | Série |
| `status` | VARCHAR | Status do documento |
| `natureza_op` | VARCHAR | Natureza da operação |
| `valor_total` | DECIMAL | Valor total da NF-e |
| `valor_produtos` | DECIMAL | Valor dos produtos |
| `valor_desconto` | DECIMAL | Valor de desconto |
| `valor_frete` | DECIMAL | Valor do frete |
| `dest_cnpj_cpf` | VARCHAR | CPF/CNPJ do destinatário |
| `dest_nome` | VARCHAR | Nome do destinatário |
| `dest_ie` | VARCHAR | IE do destinatário |
| `dest_uf` | VARCHAR(2) | UF do destinatário |
| `chave` | VARCHAR(44) | Chave de acesso |
| `protocolo` | VARCHAR | Protocolo de autorização |
| `recibo` | VARCHAR | Número do recibo |
| `status_sefaz` | VARCHAR | Código de status SEFAZ |
| `motivo_sefaz` | VARCHAR | Motivo retornado pela SEFAZ |
| `xml_envio` | TEXT/LONGTEXT | XML de envio |
| `xml_autorizado` | TEXT/LONGTEXT | XML autorizado (procNFe) |
| `xml_cancelamento` | TEXT/LONGTEXT | XML de cancelamento |
| `xml_correcao` | TEXT/LONGTEXT | XML de carta de correção |
| `danfe_path` | VARCHAR | Caminho do DANFE em disco |
| `cancel_protocolo` | VARCHAR | Protocolo de cancelamento |
| `cancel_motivo` | TEXT | Motivo de cancelamento |
| `cancel_date` | DATETIME | Data de cancelamento |
| `correcao_texto` | TEXT | Texto da CC-e |
| `correcao_seq` | INT | Sequência da CC-e |
| `correcao_date` | DATETIME | Data da CC-e |
| `emitted_at` | DATETIME | Data de emissão |
| `created_at` | DATETIME | Data de criação |

### 9.2 Métodos Disponíveis

| Método | Descrição |
|--------|-----------|
| `create(data)` | Insere novo registro + dispara evento |
| `readOne(id)` | Busca por ID |
| `readByOrder(orderId)` | Última NF-e do pedido |
| `readAllByOrder(orderId)` | Todas NF-e do pedido |
| `readPaginated(filters, page, perPage)` | Listagem paginada com filtros |
| `update(id, data)` | Atualiza campos permitidos |
| `markAuthorized(id, chave, protocolo, xml)` | Marca autorizada + sincroniza com orders |
| `markCancelled(id, protocolo, motivo, xml)` | Marca cancelada + sincroniza com orders |
| `countByStatus()` | Contagem agrupada por status |
| `countThisMonth()` | Quantidade emitida no mês |
| `sumAuthorizedThisMonth()` | Soma de autorizadas no mês |

### 9.3 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| M1 | **Sem SQL de criação da tabela no `/sql`** | 🔴 CRÍTICO | Não existe arquivo de migração SQL para as tabelas `nfe_documents`, `nfe_credentials` e `nfe_logs`. Viola a regra do projeto. |
| M2 | **Sem índices documentados** | 🟡 ALTO | Sem evidência de índices em `order_id`, `chave`, `status`, `numero`. Performance pode degradar. |
| M3 | **XMLs armazenados em TEXT** | 🟡 MÉDIO | XMLs podem ser grandes. LONGTEXT ou armazenamento em disco seria mais adequado. |
| M4 | **Sem soft delete** | 🟡 BAIXO | Registros de NF-e nunca devem ser excluídos (obrigação fiscal). Não há `deleted_at`. |
| M5 | **Transação aninhada em markAuthorized/markCancelled** | 🟡 MÉDIO | Ambos fazem `beginTransaction()` internamente, mas podem ser chamados de dentro de outra transação no Service. |
| M6 | **Sem campo `modelo` (55 ou 65)** | 🟡 ALTO | A tabela não diferencia NF-e de NFC-e. |

---

## 10. Sistema de Logs (NfeLog)

### 10.1 Campos da tabela `nfe_logs`

| Campo | Tipo (inferido) | Descrição |
|-------|-----------------|-----------|
| `id` | INT AUTO_INCREMENT | PK |
| `nfe_document_id` | INT | FK para nfe_documents |
| `order_id` | INT | FK para orders |
| `action` | VARCHAR | Tipo: init, status, emissao, cancelamento, etc |
| `status` | VARCHAR | success, error, info, warning |
| `code_sefaz` | VARCHAR | Código retornado pela SEFAZ |
| `message` | TEXT | Mensagem descritiva |
| `xml_request` | TEXT/LONGTEXT | XML enviado |
| `xml_response` | TEXT/LONGTEXT | XML de resposta |
| `user_id` | INT | Usuário que executou |
| `ip_address` | VARCHAR | IP de origem |
| `created_at` | DATETIME | Data do log |

### 10.2 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| L1 | **Sem paginação nos logs** | 🟡 MÉDIO | `getRecent()` tem limite de 50, mas `getByDocument()` e `getByOrder()` não têm limite. |
| L2 | **Sem rotação/arquivamento** | 🟡 MÉDIO | Logs crescem indefinidamente. Deveria haver uma política de arquivamento. |
| L3 | **Sem índice em `action`** | 🟡 BAIXO | Buscas filtradas por action podem ser lentas. |

---

## 11. Sistema de Eventos

### 11.1 Eventos Registrados

| Evento | Disparado em | Listener |
|--------|-------------|----------|
| `model.nfe_document.created` | `NfeDocument::create()` | (sem listener) |
| `model.nfe_document.updated` | `NfeDocument::update()` | (sem listener) |
| `model.nfe_document.authorized` | `NfeDocument::markAuthorized()` | `events.php` → log em arquivo |
| `model.nfe_document.cancelled` | `NfeDocument::markCancelled()` | `events.php` → log em arquivo |
| `model.nfe_document.error` | `NfeService::emit()` | `events.php` → log em arquivo |
| `model.nfe_credential.updated` | `NfeCredential::update()` | (sem listener) |

### 11.2 Ações dos Listeners (em `app/bootstrap/events.php`)

Todos os 3 listeners (authorized, cancelled, error) fazem:
1. `error_log()` — log no error_log do PHP
2. Escrita em `storage/logs/nfe.log` — log dedicado

### 11.3 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| EV1 | **Eventos `created` e `updated` sem listeners** | 🟡 BAIXO | Não são utilizados. |
| EV2 | **Sem notificação ao usuário** | 🟡 ALTO | Não há envio de e-mail, push ou notificação interna quando a NF-e é autorizada/cancelada. |
| EV3 | **Sem envio de XML ao cliente** | 🟡 ALTO | Em um ERP profissional, o XML autorizado deveria ser enviado por e-mail ao destinatário automaticamente. |
| EV4 | **Sem webhook/integração** | 🟡 MÉDIO | Não há disparo de webhook para sistemas externos. |

---

## 12. Rotas e Menu

### 12.1 Rotas de Credenciais (`?page=nfe_credentials`)

| Action | Método | Descrição |
|--------|--------|-----------|
| `index` (default) | GET | Formulário de credenciais |
| `store` | POST | Salvar credenciais |
| `update` | POST | Alias para store |
| `testConnection` | GET/POST | Testar conexão SEFAZ |

### 12.2 Rotas de Documentos (`?page=nfe_documents`)

| Action | Método | Descrição |
|--------|--------|-----------|
| `index` (default) | GET | Listagem de NF-e |
| `emit` | POST | Emitir NF-e (AJAX/JSON) |
| `cancel` | POST | Cancelar NF-e (AJAX/JSON) |
| `correction` | POST | Carta de Correção (AJAX/JSON) |
| `download` | GET | Download XML/DANFE |
| `checkStatus` | GET/POST | Consultar status SEFAZ (AJAX/JSON) |
| `detail` | GET | Detalhe da NF-e |

### 12.3 Menu

- Localizado no grupo "Financeiro" do menu lateral
- Label: "Notas Fiscais (NF-e)"
- Ícone: `fas fa-file-invoice`
- Módulo: `nfe` (condicionado ao ModuleBootloader)

### 12.4 Problemas Identificados

| # | Problema | Severidade |
|---|----------|------------|
| R1 | **Sem rota de inutilização** | 🟡 ALTO | Funcionalidade obrigatória não existe. |
| R2 | **Sem rota de manifestação do destinatário** | 🟡 MÉDIO | Operação importante para emitentes que recebem NF-e. |
| R3 | **Sem rota de consulta DistDFe** | 🟡 MÉDIO | Distribuição de Documentos Fiscais. |
| R4 | **`nfe_credentials` não está no menu** | 🟡 BAIXO | Acessível apenas via botão na view de documentos. |

---

## 13. Views (Interface)

### 13.1 `index.php` (Painel de NF-e)

**Implementado:**
- Cards de resumo (total, autorizada, cancelada, rejeitada, valor do mês)
- Tabela paginada com filtros (status, mês, ano, busca)
- Ações inline (detalhe, download XML, download DANFE)
- Alertas de credenciais incompletas
- Labels de status com cores e ícones

### 13.2 `detail.php` (Detalhe da NF-e)

**Implementado:**
- Timeline de eventos/logs
- Dados da NF-e (número, série, chave, protocolo)
- Dados do destinatário
- Valores (total, produtos, desconto, frete)
- Visualização de XML
- Botões de ação (cancelar, CC-e, download)

### 13.3 `credentials.php` (Credenciais SEFAZ)

**Implementado:**
- Formulário de dados do emitente
- Upload de certificado digital
- Seleção de ambiente (homologação/produção)
- Teste de conexão SEFAZ
- Alertas de expiração do certificado

### 13.4 Problemas nas Views

| # | Problema | Severidade |
|---|----------|------------|
| V1 | **Sem modal de emissão** | 🟡 ALTO | Não há um formulário de emissão na própria view de NF-e. A emissão parece ser feita via AJAX direto do pedido. |
| V2 | **Sem preview de XML** | 🟡 MÉDIO | Antes de enviar, o usuário deveria poder visualizar/revisar o XML gerado. |
| V3 | **Sem dashboard fiscal** | 🟡 MÉDIO | Cards de resumo são básicos. Deveria haver gráficos, ranking de CFOP, distribuição por UF, etc. |

---

## 14. Dependências Externas

### 14.1 Status das Dependências

| Pacote | Uso | No composer.json | Status |
|--------|-----|-------------------|--------|
| `nfephp-org/sped-nfe` | Comunicação SEFAZ, XML, assinatura | ❌ **NÃO** | ⚠️ Falta instalar |
| `nfephp-org/sped-da` | Geração de DANFE (PDF) | ❌ **NÃO** | ⚠️ Falta instalar |
| `nfephp-org/sped-common` | Classes comuns (Certificate, etc) | ❌ **NÃO** (dependência da sped-nfe) | ⚠️ Transitiva |

### 14.2 Impacto

O sistema faz fallback silencioso quando as bibliotecas não estão disponíveis:
- `isLibraryAvailable()` retorna `false`
- `NfePdfGenerator` retorna `null`/`false`

Isso significa que **o módulo NF-e está funcional apenas na interface**, mas **não emite NF-e de verdade** sem as bibliotecas instaladas.

---

## 15. Situação da NFC-e

### 15.1 Estado Atual

A NFC-e (modelo 65) **NÃO está implementada**, apesar de haver:
- Seleção de modelo (55/65) na view de Settings
- Campos `csc_id` e `csc_token` nas credenciais (necessários para NFC-e)
- Menção a "NF-e e NFC-e" na documentação

### 15.2 O que falta para NFC-e

| Item | Status |
|------|--------|
| Selecionar modelo 65 no Tools | ❌ |
| Gerar QR Code no XML | ❌ |
| Tag `dest` opcional (CPF do consumidor) | ❌ |
| DANFE-NFC-e (cupom) | ❌ |
| CSC no cálculo do QR Code | ❌ |
| `indPres = 1` obrigatório | ⚠️ Fixo |
| `finNFe = 1` obrigatório | ✅ |
| Sem tag `transp` detalhada | ✅ (adequado) |
| Sem tag `cobr` | ✅ (adequado para NFC-e) |

---

## 16. Análise de Segurança

| # | Vulnerabilidade | Severidade | Detalhes |
|---|----------------|------------|----------|
| S1 | **Sem CSRF** | 🔴 CRÍTICO | Nenhum dos endpoints POST (emit, cancel, correction, store credentials) valida token CSRF. |
| S2 | **Sem verificação de permissão** | 🔴 CRÍTICO | Os controllers NF-e não verificam permissões de grupo/usuário. Qualquer usuário logado pode emitir, cancelar, etc. |
| S3 | **Sem verificação de login** | 🟡 ALTO | Os controllers não fazem `checkAdmin()` ou equivalente. Dependem do Router para isso. |
| S4 | **Chave de criptografia previsível** | 🔴 CRÍTICO | A chave para AES-256-CBC é derivada de `sha256(db_name + salt)`. Qualquer pessoa com acesso ao código pode descriptografar a senha do certificado. |
| S5 | **Certificado acessível via path** | 🟡 MÉDIO | O certificado .pfx é salvo em `storage/certificates/`, que precisa estar protegido contra acesso web direto. |
| S6 | **XML de envio pode conter dados sensíveis** | 🟡 BAIXO | XMLs são armazenados no banco sem criptografia. |

---

## 17. Análise do Banco de Dados

### 17.1 Tabelas NF-e (inferidas do código)

```
nfe_credentials (registro único por tenant)
nfe_documents   (documentos fiscais emitidos)  
nfe_logs        (logs de comunicação SEFAZ)
```

### 17.2 Problemas

| # | Problema | Severidade |
|---|----------|------------|
| DB1 | **Sem arquivo de migração SQL** | 🔴 CRÍTICO | Nenhuma das 3 tabelas tem script de criação em `/sql`. Viola a regra fundamental do projeto. |
| DB2 | **Sem FK constraints documentadas** | 🟡 ALTO | `nfe_documents.order_id` → `orders.id` deveria ter FK. |
| DB3 | **Sem índices otimizados** | 🟡 ALTO | Campos frequentemente consultados (chave, status, order_id) precisam de índices. |
| DB4 | **Sem campo `modelo`** | 🟡 ALTO | Para suportar NF-e e NFC-e, precisa diferenciar. |
| DB5 | **Sem campo `tenant_id`** | 🟡 ALTO | Em ambiente multi-tenant com banco compartilhado, pode ser necessário. |
| DB6 | **Sem tabela de itens da NF-e** | 🟡 MÉDIO | Os itens não são armazenados separadamente — apenas no XML. Dificulta relatórios fiscais. |

---

## 18. Resumo de Problemas Encontrados

### Por Severidade

**🔴 CRÍTICOS (13):**
1. Biblioteca sped-nfe não está no composer.json
2. Biblioteca sped-da não está no composer.json  
3. Sem arquivo de migração SQL para tabelas NF-e
4. sleep(3) fixo na consulta de recibo
5. Sem retry quando SEFAZ retorna "em processamento"
6. Sem emissão em contingência
7. Impostos fixos apenas para Simples Nacional (CSOSN 102)
8. PIS/COFINS zerados
9. NCM com fallback inválido ('00000000')
10. Sem proteção CSRF nos endpoints POST
11. Sem verificação de permissão nos controllers
12. Chave de criptografia previsível para senha do certificado
13. Sem verificação de prazo no cancelamento (24h)

**🟡 ALTOS (16):**
1. Modelo fixo em 55 (sem NFC-e)
2. Dados do destinatário podem estar incompletos
3. Sem validação de XML pré-envio (schema XSD)
4. Sem inutilização de numeração
5. CFOP fixo em 5102
6. `cNF` gerado com `rand()`
7. `idDest` fixo em 1 (operação interna)
8. Sem tag CEST
9. Sem tag vTotTrib (Lei 12741)
10. Sem DANFE-NFC-e
11. Sem índices otimizados no banco
12. Sem campo `modelo` na tabela
13. Sem notificação ao usuário (e-mail, push)
14. Sem envio de XML ao cliente
15. Sem modal/formulário de emissão na view
16. Sem FK constraints

**🟡 MÉDIOS (14):**
- Transação dupla (emit + markAuthorized)
- Sem geração automática de DANFE
- Sem salvamento de XML em disco
- `modFrete` fixo
- Sem tag `cobr` (fatura/duplicata)
- Sem limite de 20 CC-e
- Sobrescrita de XML de CC-e anterior
- Sem paginação nos logs
- Sem rotação de logs
- Certificado em path absoluto
- Credencial única por tenant
- Sem dashboard fiscal avançado
- Sem webhook/integração
- XMLs em TEXT no banco

**🟡 BAIXOS (5):**
- Sem tag `infRespTec`
- Personalização do DANFE
- Eventos `created`/`updated` sem listeners
- Sem rota de credenciais no menu direto
- XML sem criptografia no banco

---

> **Nota:** Este documento é parte de uma série de documentos de auditoria. Consulte os outros documentos na pasta `docs/nfe/` para ver as recomendações de melhoria detalhadas.
