# Auditoria Completa do Módulo NF-e — v2

**Data:** 26/03/2026  
**Versão:** 2.0  
**Escopo:** Revisão completa de todos os arquivos do módulo NF-e — Models, Services, Controllers, Views, Rotas e Banco de Dados.

---

## Sumário

1. [Inventário de Arquivos](#1-inventário-de-arquivos)
2. [Bugs Críticos Encontrados](#2-bugs-críticos-encontrados)
3. [Campos/Métodos Faltantes no Controller](#3-camposmétodos-faltantes-no-controller)
4. [Inconsistências Model ↔ Banco de Dados](#4-inconsistências-model--banco-de-dados)
5. [Campos Faltantes no allowedFields](#5-campos-faltantes-no-allowedfields)
6. [Parâmetros Não Utilizados](#6-parâmetros-não-utilizados)
7. [Problemas nas Views](#7-problemas-nas-views)
8. [Funcionalidades Parcialmente Implementadas](#8-funcionalidades-parcialmente-implementadas)
9. [Funcionalidades NÃO Implementadas](#9-funcionalidades-não-implementadas)
10. [Inconsistências de Nomenclatura (DB × PHP)](#10-inconsistências-de-nomenclatura-db--php)
11. [Segurança e Boas Práticas](#11-segurança-e-boas-práticas)
12. [Relatórios e Dashboard](#12-relatórios-e-dashboard)
13. [Recomendações de Correção (Prioridade)](#13-recomendações-de-correção-prioridade)
14. [Checklist de Conformidade Fiscal](#14-checklist-de-conformidade-fiscal)

---

## 1. Inventário de Arquivos

### Models (8 arquivos)
| Arquivo | Tabela | Status |
|---------|--------|--------|
| `NfeDocument.php` | `nfe_documents` | ⚠️ Campos faltantes no `allowedFields` |
| `NfeCredential.php` | `nfe_credentials` | ⚠️ Campo `ultimo_nsu` faltante no `allowedFields` |
| `NfeQueue.php` | `nfe_queue` | ⚠️ `$batchId` não utilizado |
| `NfeLog.php` | `nfe_logs` | ✅ OK |
| `NfeAuditLog.php` | `nfe_audit_log` | ✅ OK |
| `NfeWebhook.php` | `nfe_webhooks` + `nfe_webhook_logs` | ✅ OK |
| `NfeReceivedDocument.php` | `nfe_received_documents` | ✅ OK |
| `NfeReportModel.php` | Multi-tabela (relatórios) | ⚠️ Referência a colunas possivelmente inexistentes |
| `IbptaxModel.php` | `tax_ibptax` | ✅ OK |

### Services (11 arquivos)
| Arquivo | Função | Status |
|---------|--------|--------|
| `NfeService.php` | Comunicação SEFAZ (emit, cancel, correction, inutilizar) | ⚠️ `saveFiscalTotals` falha silenciosamente |
| `NfeXmlBuilder.php` | Montagem XML NF-e 4.00 | ⚠️ Nomenclatura de itens inconsistente |
| `NfeXmlValidator.php` | Validação XML contra XSD | ✅ OK |
| `NfeQueueService.php` | Fila assíncrona de emissão | ✅ OK |
| `NfeWebhookService.php` | Disparo de webhooks | ✅ OK |
| `NfeAuditService.php` | Trilha de auditoria | ✅ OK |
| `NfeDistDFeService.php` | DistDFe / Documentos Recebidos | ⚠️ `update('ultimo_nsu')` falha silenciosamente |
| `NfeManifestationService.php` | Manifestação do Destinatário | ✅ OK |
| `NfeDanfeCustomizer.php` | Personalização DANFE | ✅ OK |
| `NfePdfGenerator.php` | Geração de DANFE PDF | ✅ OK |
| `NfeStorageService.php` | Armazenamento XML/PDF em disco | ✅ OK |
| `TaxCalculator.php` | Cálculo dinâmico de impostos | ✅ OK |

### Controllers (2 arquivos)
| Arquivo | Função | Status |
|---------|--------|--------|
| `NfeDocumentController.php` | CRUD principal, emissão, dashboard | 🔴 5 métodos de rota AUSENTES |
| `NfeCredentialController.php` | Credenciais SEFAZ | ✅ OK |

### Views (10 arquivos + 2 partials)
| Arquivo | Função | Status |
|---------|--------|--------|
| `index.php` | Listagem de NF-e | ✅ OK |
| `detail.php` | Detalhe da NF-e | ⚠️ Query com coluna inexistente |
| `credentials.php` | Formulário credenciais | ✅ OK |
| `dashboard.php` | Dashboard fiscal | ✅ OK (Chart.js fix aplicado) |
| `queue.php` | Fila de emissão | ✅ OK (fix anterior aplicado) |
| `received.php` | Documentos recebidos | ✅ OK |
| `audit.php` | Auditoria de acessos | ✅ OK |
| `webhooks.php` | Configuração webhooks | ✅ OK |
| `danfe_settings.php` | Personalização DANFE | ✅ OK |
| `partials/toast_notifications.php` | Notificações toast | ✅ OK |
| `partials/alerts_banner.php` | Banner de alertas | ✅ OK |

### Rotas (`app/config/routes.php`)
| Rota | Método no Controller | Status |
|------|---------------------|--------|
| `emit` | `emit()` | 🔴 MÉTODO NÃO EXISTE |
| `cancel` | `cancel()` | 🔴 MÉTODO NÃO EXISTE |
| `correction` | `correction()` | 🔴 MÉTODO NÃO EXISTE |
| `download` | `download()` | 🔴 MÉTODO NÃO EXISTE |
| `checkStatus` | `checkStatus()` | 🔴 MÉTODO NÃO EXISTE |
| `batchEmit` | `batchEmit()` | ✅ Existe |
| `queue` | `queue()` | ✅ Existe |
| `processQueue` | `processQueue()` | ✅ Existe |
| `cancelQueue` | `cancelQueue()` | ✅ Existe |
| `received` | `received()` | ✅ Existe |
| `queryDistDFe` | `queryDistDFe()` | ✅ Existe |
| `queryDistDFeByChave` | `queryDistDFeByChave()` | ✅ Existe |
| `manifest` | `manifest()` | ✅ Existe |
| `audit` | `audit()` | ✅ Existe |
| `webhooks` | `webhooks()` | ✅ Existe |
| `saveWebhook` | `saveWebhook()` | ✅ Existe |
| `deleteWebhook` | `deleteWebhook()` | ✅ Existe |
| `testWebhook` | `testWebhook()` | ✅ Existe |
| `webhookLogs` | `webhookLogs()` | ✅ Existe |
| `danfeSettings` | `danfeSettings()` | ✅ Existe |
| `saveDanfeSettings` | `saveDanfeSettings()` | ✅ Existe |
| `inutilizar` | `inutilizar()` | ✅ Existe |

---

## 2. Bugs Críticos Encontrados

### 🔴 BUG-01: 5 Métodos Essenciais Ausentes no Controller

**Arquivo:** `app/controllers/NfeDocumentController.php`  
**Severidade:** CRÍTICA — Impede emissão, cancelamento, correção, download e consulta de NF-e.

As rotas abaixo estão registradas em `app/config/routes.php` mas os métodos **não existem** no controller:

| Action | Método Esperado | Descrição |
|--------|----------------|-----------|
| `emit` | `emit()` | Emissão individual de NF-e para um pedido |
| `cancel` | `cancel()` | Cancelamento de NF-e autorizada |
| `correction` | `correction()` | Envio de Carta de Correção (CC-e) |
| `download` | `download()` | Download de XML autorizado / DANFE PDF |
| `checkStatus` | `checkStatus()` | Consulta de status na SEFAZ |

**Consequência:** Ao acessar `?page=nfe_documents&action=emit`, o sistema lança erro fatal (method not found) ou retorna ao `index()` sem ação.

**Correção necessária:** Implementar os 5 métodos no `NfeDocumentController`. Os services correspondentes já existem e estão corretos (`NfeService::emit()`, `NfeService::cancel()`, `NfeService::correction()`, `NfeService::checkStatus()`, `NfePdfGenerator`).

---

### 🔴 BUG-02: `saveFiscalTotals()` Falha Silenciosamente

**Arquivo:** `app/services/NfeService.php` (linha ~897) + `app/models/NfeDocument.php` (linha ~199)  
**Severidade:** ALTA — Totais fiscais nunca são salvos no documento.

O método `NfeService::saveFiscalTotals()` tenta atualizar campos:
- `total_vbc`
- `total_icms`
- `total_pis`
- `total_cofins`
- `total_ipi`
- `total_tributos`

Mas **nenhum desses campos** está na lista `allowedFields` de `NfeDocument::update()`:
```php
$allowedFields = [
    'chave', 'protocolo', 'recibo',
    'status', 'status_sefaz', 'motivo_sefaz',
    'modelo', 'tp_emis', 'contingencia_justificativa',
    'xml_envio', 'xml_autorizado', 'xml_cancelamento', 'xml_correcao',
    'xml_path', 'danfe_path',
    'cancel_protocolo', 'cancel_motivo', 'cancel_date',
    'correcao_texto', 'correcao_seq', 'correcao_date',
    'emitted_at',
];
```

Além disso, os nomes dos campos no `saveFiscalTotals` (`total_vbc`, `total_icms`, etc.) **não correspondem** aos nomes das colunas do banco de dados (`valor_icms`, `valor_pis`, `valor_cofins`, `valor_ipi`, `valor_tributos_aprox`).

**Dupla falha:**
1. Nomes das colunas divergem entre `saveFiscalTotals()` e o DDL.
2. Nenhum dos nomes está no `allowedFields`.

**Correção necessária:**
- Adicionar ao `allowedFields`: `'valor_icms', 'valor_pis', 'valor_cofins', 'valor_ipi', 'valor_tributos_aprox'`
- Corrigir `saveFiscalTotals()` para usar nomes de colunas corretos: `valor_icms` em vez de `total_icms`, etc.

---

### 🔴 BUG-03: Nomenclatura Inconsistente em `saveDocumentItems()`

**Arquivo:** `app/services/NfeService.php` (linhas ~821-867)  
**Severidade:** ALTA — Itens fiscais possivelmente não são salvos na tabela.

O INSERT usa nomes de coluna em `snake_case`:
```sql
INSERT INTO nfe_document_items
(nfe_document_id, n_item, c_prod, x_prod, ... u_com, q_com, v_un_com, v_prod, v_desc, ... v_tot_trib)
```

Mas o DDL da tabela usa `camelCase` (estilo XML da NF-e):
```sql
`nItem`, `cProd`, `xProd`, `uCom`, `qCom`, `vUnCom`, `vProd`, `vDesc`, `vTotTrib`
```

**Colunas afetadas (13 colunas):**

| PHP (INSERT) | DDL (Tabela) | Match? |
|-------------|-------------|--------|
| `n_item` | `nItem` | ❌ |
| `c_prod` | `cProd` | ❌ |
| `x_prod` | `xProd` | ❌ |
| `u_com` | `uCom` | ❌ |
| `q_com` | `qCom` | ❌ |
| `v_un_com` | `vUnCom` | ❌ |
| `v_prod` | `vProd` | ❌ |
| `v_desc` | `vDesc` | ❌ |
| `v_tot_trib` | `vTotTrib` | ❌ |
| `ncm` | `ncm` | ✅ |
| `cest` | `cest` | ✅ |
| `cfop` | `cfop` | ✅ |
| `origem` | `origem` | ✅ |
| `icms_*` | `icms_*` | ✅ |
| `pis_*` | `pis_*` | ✅ |
| `cofins_*` | `cofins_*` | ✅ |
| `ipi_*` | `ipi_*` | ✅ |

**Correção necessária:** Alinhar nomes — ou renomear colunas no DDL para `snake_case`, ou alterar o PHP para usar `camelCase`. Recomendação: renomear o DDL para `snake_case` para manter consistência com as demais colunas do sistema (icms_cst, pis_valor etc. já são snake_case).

---

### 🔴 BUG-04: Query com Coluna Inexistente no Controller `detail()`

**Arquivo:** `app/controllers/NfeDocumentController.php` (linha ~229)  
**Severidade:** ALTA — Pode causar erro SQL ao visualizar detalhes da NF-e.

```php
$stmtItems = $this->db->prepare(
    "SELECT ncm, valor_total, origem FROM nfe_document_items WHERE nfe_document_id = :nfe_id"
);
```

A coluna `valor_total` **não existe** na tabela `nfe_document_items`. O nome correto é `vProd` (ou `v_prod` se corrigido).

**Correção:** Alterar para `vProd` (ou o nome que for padronizado).

---

### 🔴 BUG-05: `ultimo_nsu` Não Salva (NfeCredential)

**Arquivo:** `app/services/NfeDistDFeService.php` (linha ~195) + `app/models/NfeCredential.php` (linha ~62)  
**Severidade:** MÉDIA — Último NSU nunca é persistido; DistDFe re-consulta tudo a cada execução.

`NfeDistDFeService::queryByNSU()` chama:
```php
$this->credModel->update(['ultimo_nsu' => $lastNsu]);
```

Mas `ultimo_nsu` **não está** no `allowedFields` de `NfeCredential::update()`:
```php
$allowedFields = [
    'cnpj', 'ie', 'razao_social', ... 'contingencia_ativada_em',
    // 'ultimo_nsu' NÃO ESTÁ AQUI
];
```

**Correção:** Adicionar `'ultimo_nsu'` ao array `$allowedFields`.

---

### 🟡 BUG-06: `NfeQueue::enqueueBatch()` Ignora `$batchId`

**Arquivo:** `app/models/NfeQueue.php` (linha ~54)  
**Severidade:** BAIXA — O batch ID é gerado pelo service mas nunca persistido.

O método recebe `string $batchId` como parâmetro, mas:
1. Não inclui `batch_id` na query INSERT.
2. A tabela `nfe_queue` não tem coluna `batch_id`.
3. O ID de lote não pode ser rastreado.

**Correção necessária:**
- Adicionar coluna `batch_id VARCHAR(50) DEFAULT NULL` na tabela `nfe_queue`.
- Incluir o campo no INSERT do `enqueueBatch()`.

---

### 🟡 BUG-07: `NfeReportModel::getTotalTaxes12Months()` Referencia Colunas Possivelmente Ausentes

**Arquivo:** `app/models/NfeReportModel.php` (linhas ~681-685)  
**Severidade:** MÉDIA — Dashboard fiscal pode falhar com erro SQL.

Referencia colunas: `valor_icms`, `valor_pis`, `valor_cofins`, `valor_ipi`, `valor_tributos_aprox` em `nfe_documents`.

Essas colunas existem no DDL (adicionadas via ALTER TABLE), mas como `saveFiscalTotals()` falha silenciosamente (BUG-02), esses campos estarão sempre zerados.

---

### 🟡 BUG-08: `NfeService::inutilizar()` Usa Campos Inexistentes no `create()`

**Arquivo:** `app/services/NfeService.php` (linhas ~920-930)  
**Severidade:** MÉDIA — Inutilização pode falhar.

O método passa `'natureza_operacao'` e `'justificativa'` para `NfeDocument::create()`:
```php
$this->docModel->create([
    'natureza_operacao' => 'Inutilização de Numeração',  // NÃO existe no create()
    'justificativa' => $justificativa,                     // NÃO existe no create()
]);
```

Mas `NfeDocument::create()` aceita `'natureza_op'` (não `natureza_operacao`) e não tem campo `justificativa`.

**Correção:** Usar `'natureza_op'` em vez de `'natureza_operacao'`. Para `justificativa`, usar o campo `motivo_sefaz` ou adicionar o campo ao create.

---

## 3. Campos/Métodos Faltantes no Controller

### `NfeDocumentController` — Métodos que devem ser implementados:

#### 3.1 `emit()` — Emissão Individual
```
POST ?page=nfe_documents&action=emit
Parâmetros: order_id (int)
Fluxo:
  1. Validar permissão de escrita
  2. Carregar pedido + itens + cliente + parcelas
  3. Montar $orderData
  4. Chamar NfeService::emit($orderId, $orderData)
  5. Registrar auditoria (NfeAuditService::logEmit)
  6. Disparar webhook (nfe.authorized ou nfe.rejected)
  7. Retornar JSON com resultado
```

#### 3.2 `cancel()` — Cancelamento
```
POST ?page=nfe_documents&action=cancel
Parâmetros: nfe_id (int), motivo (string, mín 15 chars)
Fluxo:
  1. Validar permissão de escrita
  2. Chamar NfeService::cancel($nfeId, $motivo)
  3. Registrar auditoria (NfeAuditService::logCancel)
  4. Disparar webhook (nfe.cancelled)
  5. Retornar JSON
```

#### 3.3 `correction()` — Carta de Correção
```
POST ?page=nfe_documents&action=correction
Parâmetros: nfe_id (int), texto (string, mín 15 chars)
Fluxo:
  1. Validar permissão de escrita
  2. Chamar NfeService::correction($nfeId, $texto)
  3. Registrar auditoria (NfeAuditService::logCorrection)
  4. Disparar webhook (nfe.corrected)
  5. Retornar JSON
```

#### 3.4 `download()` — Download XML/DANFE
```
GET ?page=nfe_documents&action=download&id=X&type=xml|danfe
Parâmetros: id (int), type (string: xml, danfe, cancel_xml, cce_xml)
Fluxo:
  1. Carregar NfeDocument::readOne($id)
  2. Se type=xml: headers Content-Type application/xml, retornar xml_autorizado
  3. Se type=danfe: gerar DANFE via NfeDanfeCustomizer::generate() ou NfePdfGenerator
  4. Se type=cancel_xml: retornar xml_cancelamento
  5. Se type=cce_xml: retornar xml_correcao
  6. Registrar auditoria (logDownloadXml / logDownloadDanfe)
```

#### 3.5 `checkStatus()` — Consulta Status na SEFAZ
```
GET/POST ?page=nfe_documents&action=checkStatus&id=X
Parâmetros: nfe_id (int)
Fluxo:
  1. Chamar NfeService::checkStatus($nfeId)
  2. Retornar JSON com resultado
```

---

## 4. Inconsistências Model ↔ Banco de Dados

### 4.1 `NfeDocument::update()` — allowedFields incompleto

**Campos que existem no banco mas não estão em allowedFields:**

| Coluna no Banco | Usado por | Status |
|-----------------|----------|--------|
| `valor_icms` | `saveFiscalTotals()`, `NfeReportModel` | ❌ Faltante |
| `valor_pis` | `saveFiscalTotals()`, `NfeReportModel` | ❌ Faltante |
| `valor_cofins` | `saveFiscalTotals()`, `NfeReportModel` | ❌ Faltante |
| `valor_ipi` | `saveFiscalTotals()`, `NfeReportModel` | ❌ Faltante |
| `valor_tributos_aprox` | `NfeReportModel`, Controller `detail()` | ❌ Faltante |
| `natureza_op` | `create()` usa, mas `update()` não permite | ❌ Faltante |
| `valor_total` | Usado em vários relatórios | ❌ Faltante |
| `valor_produtos` | Pode precisar atualizar | ❌ Faltante |
| `valor_desconto` | Pode precisar atualizar | ❌ Faltante |
| `valor_frete` | Pode precisar atualizar | ❌ Faltante |
| `dest_cnpj_cpf` | Pode precisar atualizar | ❌ Faltante |
| `dest_nome` | Pode precisar atualizar | ❌ Faltante |
| `dest_ie` | Pode precisar atualizar | ❌ Faltante |
| `dest_uf` | Pode precisar atualizar | ❌ Faltante |
| `cancel_xml_path` | Storage do XML de cancelamento | ❌ Faltante |

### 4.2 `NfeCredential::update()` — allowedFields incompleto

| Coluna no Banco | Usado por | Status |
|-----------------|----------|--------|
| `ultimo_nsu` | `NfeDistDFeService::queryByNSU()` | ❌ Faltante |
| `filial_id` | Multi-filial (Fase 5.9) | ❌ Faltante |
| `is_active` | Multi-filial | ❌ Faltante |

---

## 5. Campos Faltantes no allowedFields

### Correção para `NfeDocument::update()`:
Adicionar ao array `$allowedFields`:
```php
'valor_total', 'valor_produtos', 'valor_desconto', 'valor_frete',
'valor_icms', 'valor_pis', 'valor_cofins', 'valor_ipi', 'valor_tributos_aprox',
'natureza_op',
'dest_cnpj_cpf', 'dest_nome', 'dest_ie', 'dest_uf',
'cancel_xml_path',
```

### Correção para `NfeCredential::update()`:
Adicionar ao array `$allowedFields`:
```php
'ultimo_nsu', 'filial_id', 'is_active',
```

---

## 6. Parâmetros Não Utilizados

| Arquivo | Método | Parâmetro | Descrição |
|---------|--------|-----------|-----------|
| `NfeQueue.php` | `enqueueBatch()` | `$batchId` | Recebido mas nunca inserido no banco |
| `NfeQueueService.php` | `enqueueBatch()` | `$batchId` gerado | Passado ao model, mas `batch_id` não existe na tabela |

---

## 7. Problemas nas Views

### 7.1 `detail.php` — Query Inválida
- Linha 229 do controller: `SELECT ncm, valor_total, origem FROM nfe_document_items` — coluna `valor_total` não existe, deveria ser `vProd`.

### 7.2 Verificações Gerais
- Todas as views usam o wrapper `DOMContentLoaded` corretamente (fix anterior aplicado).
- `dashboard.php` carrega Chart.js dinamicamente (fix anterior aplicado).
- `queue.php` usa `completed_at` corretamente (fix anterior aplicado).

---

## 8. Funcionalidades Parcialmente Implementadas

### 8.1 Emissão em Contingência (tp_emis)
- **Banco:** Coluna `tp_emis` e `contingencia_justificativa` existem em `nfe_documents` e `nfe_credentials`.
- **Service:** `NfeXmlBuilder` usa `tp_emis` na tag `ide`.
- **Controller:** Não há ação para ativar/desativar contingência.
- **Faltante:** Endpoint no controller para ativar contingência, lógica de fallback automático, sincronização posterior.

### 8.2 NFC-e (Modelo 65)
- **Banco:** Colunas `serie_nfce` e `proximo_numero_nfce` existem em `nfe_credentials`.
- **Service:** `NfeXmlBuilder` está hardcoded para `ide->mod = 55`.
- **Faltante:** Toda a lógica de emissão NFC-e (CSC, QR Code, dados simplificados).

### 8.3 Multi-Filial (Fase 5.9)
- **Banco:** Colunas `filial_id` e `is_active` existem em `nfe_credentials`.
- **Model:** `NfeCredential::get()` sempre busca `WHERE id = 1` — ignora multi-filial.
- **Faltante:** Seletor de filial no controller, alteração do `get()` para buscar pela filial ativa.

### 8.4 Batch ID na Fila
- **Service:** `NfeQueueService::enqueueBatch()` gera `$batchId` (`BATCH_YmdHis_uniqid`).
- **Model:** `NfeQueue::enqueueBatch()` recebe mas ignora.
- **Banco:** Sem coluna `batch_id`.
- **View:** `queue.php` não exibe informação de lote.
- **Faltante:** Coluna no banco, insert no model, filtro por batch na view.

### 8.5 Inutilização de Numeração
- **Service:** `NfeService::inutilizar()` tem um `TODO` — integração com API SEFAZ real marcada como pendente.
- **Controller:** Método `inutilizar()` existe e funciona.
- **Faltante:** Comunicação real com SEFAZ (atualmente registra apenas localmente).

---

## 9. Funcionalidades NÃO Implementadas

| Funcionalidade | Prioridade | Observação |
|---------------|------------|------------|
| Método `emit()` no controller | 🔴 CRÍTICA | Service existe, falta o controller |
| Método `cancel()` no controller | 🔴 CRÍTICA | Service existe, falta o controller |
| Método `correction()` no controller | 🔴 CRÍTICA | Service existe, falta o controller |
| Método `download()` no controller | 🔴 CRÍTICA | PDF Generator existe, falta o controller |
| Método `checkStatus()` no controller | 🔴 ALTA | Service existe, falta o controller |
| Emissão NFC-e completa | 🟡 MÉDIA | Dados parciais no banco, sem lógica NFC-e |
| Contingência automática | 🟡 MÉDIA | Campos existem, sem lógica de ativação |
| Multi-filial selector | 🟡 MÉDIA | Colunas existem, sem UI/lógica |
| Tracking de lote (batch_id) | 🟢 BAIXA | Sem coluna nem persistência |
| Inutilização real SEFAZ | 🟢 BAIXA | Funciona local, API SEFAZ TODO |
| Exportação SPED Fiscal (EFD) | 🟢 BAIXA | Não implementado |
| Relatório SINTEGRA | 🟢 BAIXA | Não implementado |
| Devolução de NF-e (finNFe=4) | 🟡 MÉDIA | XML Builder não suporta NF-e de devolução |
| Complementar de NF-e (finNFe=2) | 🟡 MÉDIA | XML Builder não suporta NF-e complementar |
| Ajuste de NF-e (finNFe=3) | 🟡 MÉDIA | XML Builder não suporta NF-e de ajuste |
| Download XML em lote (ZIP) | 🟢 BAIXA | Não implementado |
| Reenvio de NF-e rejeitada | 🟡 MÉDIA | Sem rota nem método de retry |

---

## 10. Inconsistências de Nomenclatura (DB × PHP)

### Tabela `nfe_document_items`

A tabela usa nomes no estilo `camelCase` do XML da NF-e para colunas de dados do produto, mas `snake_case` para colunas de impostos. O PHP INSERT em `NfeService::saveDocumentItems()` usa `snake_case` uniforme.

**Colunas com Mismatch:**

| DDL (camelCase) | PHP INSERT (snake_case) | NfeReportModel | Recomendação |
|----------------|------------------------|----------------|--------------|
| `nItem` | `n_item` | Não usado | Renomear DDL → `n_item` |
| `cProd` | `c_prod` | Não usado | Renomear DDL → `c_prod` |
| `xProd` | `x_prod` | Não usado | Renomear DDL → `x_prod` |
| `uCom` | `u_com` | Não usado | Renomear DDL → `u_com` |
| `qCom` | `q_com` | Não usado | Renomear DDL → `q_com` |
| `vUnCom` | `v_un_com` | Não usado | Renomear DDL → `v_un_com` |
| `vProd` | `v_prod` | ✅ Usa `ni.vProd` | ⚠️ Se renomear, atualizar Report |
| `vDesc` | `v_desc` | Não usado | Renomear DDL → `v_desc` |
| `vFrete` | Não inserido | Não usado | — |
| `vTotTrib` | `v_tot_trib` | Não usado | Renomear DDL → `v_tot_trib` |

**Decisão necessária:** Padronizar em `snake_case` (recomendado para consistência com o resto do sistema) e gerar SQL de migração.

**IMPORTANTE:** O `NfeReportModel` usa `ni.vProd` e `ni.icms_valor` em queries. Se renomear `vProd` → `v_prod`, todas as queries do `NfeReportModel` devem ser atualizadas.

---

## 11. Segurança e Boas Práticas

### ✅ Implementado Corretamente
- Criptografia de senha do certificado (`NfeCredential::encryptPassword/decryptPassword`)
- Chave derivada com fallback + warning em log
- Verificação de permissões em cada action do controller
- Proteção `.htaccess` no diretório de armazenamento de XMLs
- Sanitização de input via `Input::get/post()`
- Certificado armazenado fora do webroot (`storage/certificates/`)
- CSRF implícito via POST (verificar se há token CSRF nas views)
- Auditoria completa de todas as ações (quando habilitada)

### ⚠️ Melhorias Sugeridas
- **Rate limiting** nas ações de emissão (evitar burst de emissões simultâneas)
- **Token CSRF** explícito nos formulários POST de emissão/cancelamento
- **Log de acesso** ao certificado digital (quem baixou/visualizou)
- **Validação mais rigorosa** do CPF/CNPJ do destinatário antes de emitir
- **Backup automático** dos XMLs para storage externo (S3, etc.)
- **Separação de permissões**: view vs. edit (atualmente é booleano por página)

---

## 12. Relatórios e Dashboard

### Dashboard Fiscal (`dashboard()`)
- **KPIs:** Total emitidas, autorizadas, canceladas, rejeitadas, valor autorizado, ticket médio ✅
- **Gráficos:** NF-e por mês (12 meses), distribuição por status ✅
- **Top CFOPs:** ✅
- **Top Clientes:** ✅
- **Alertas:** Certificado, NF-e travadas, gaps de numeração, taxa de rejeição ✅
- **Totais de impostos (12 meses):** ⚠️ Depende de BUG-02 ser corrigido (valores zerados)

### Relatórios (`NfeReportModel`)
- **NF-e por período:** ✅
- **Resumo de impostos (NCM × CFOP):** ✅
- **NF-e por cliente:** ✅
- **Resumo CFOP:** ✅
- **NF-e canceladas:** ✅
- **Inutilizações:** ✅
- **Logs SEFAZ:** ✅
- **KPIs fiscais:** ✅
- **Labels/descrições:** ✅

### Relatórios Faltantes
- **Livro de Registro de Saídas:** Não implementado
- **Livro de Registro de Entradas:** Não implementado (depende de DistDFe/NF-e recebidas)
- **Resumo DIFAL:** Dados calculados mas não persistidos/reportados
- **Relatório de CC-e (Cartas de Correção):** Tabela existe mas sem relatório dedicado
- **Exportação PDF/Excel** dos relatórios: Não implementado para relatórios NF-e

---

## 13. Recomendações de Correção (Prioridade)

### 🔴 Prioridade 1 — Crítica (Bloqueia funcionalidades essenciais)

| # | Ação | Arquivos Afetados |
|---|------|-------------------|
| 1 | Implementar `emit()` no NfeDocumentController | `NfeDocumentController.php` |
| 2 | Implementar `cancel()` no NfeDocumentController | `NfeDocumentController.php` |
| 3 | Implementar `correction()` no NfeDocumentController | `NfeDocumentController.php` |
| 4 | Implementar `download()` no NfeDocumentController | `NfeDocumentController.php` |
| 5 | Implementar `checkStatus()` no NfeDocumentController | `NfeDocumentController.php` |

### 🟠 Prioridade 2 — Alta (Dados incorretos ou perdidos)

| # | Ação | Arquivos Afetados |
|---|------|-------------------|
| 6 | Adicionar campos fiscais ao `allowedFields` de `NfeDocument::update()` | `NfeDocument.php` |
| 7 | Corrigir `saveFiscalTotals()` para usar nomes de colunas corretos | `NfeService.php` |
| 8 | Adicionar `ultimo_nsu` ao `allowedFields` de `NfeCredential::update()` | `NfeCredential.php` |
| 9 | Padronizar nomes de colunas em `nfe_document_items` (DDL + PHP + Report) | SQL migration, `NfeService.php`, `NfeReportModel.php` |
| 10 | Corrigir query de `valor_total` → `vProd` (ou nome padronizado) no controller `detail()` | `NfeDocumentController.php` |
| 11 | Corrigir `inutilizar()` para usar `natureza_op` em vez de `natureza_operacao` | `NfeService.php` |

### 🟡 Prioridade 3 — Média (Funcionalidades parciais)

| # | Ação | Arquivos Afetados |
|---|------|-------------------|
| 12 | Adicionar coluna `batch_id` à tabela `nfe_queue` e usar no model | SQL migration, `NfeQueue.php` |
| 13 | Implementar tracking de lote na view `queue.php` | `queue.php` |
| 14 | Adicionar `filial_id`, `is_active` ao `NfeCredential::update()` | `NfeCredential.php` |
| 15 | Suporte a `finNFe` dinâmico no XmlBuilder (devolução, complementar) | `NfeXmlBuilder.php` |

### 🟢 Prioridade 4 — Baixa (Melhorias futuras)

| # | Ação |
|---|------|
| 16 | Emissão NFC-e completa (CSC, QR Code) |
| 17 | Contingência automática com fallback |
| 18 | Download XML em lote (ZIP) |
| 19 | Exportação SPED Fiscal |
| 20 | Rate limiting na emissão |
| 21 | Reenvio de NF-e rejeitada |
| 22 | Integração real com SEFAZ para inutilização |
| 23 | Livros de Registro (Saídas/Entradas) |

---

## 14. Checklist de Conformidade Fiscal

### Estrutura do XML NF-e 4.00

| Grupo | Tag | Status | Observação |
|-------|-----|--------|------------|
| Identificação | `ide` | ✅ | cUF, cNF, natOp, mod, serie, nNF, dhEmi, dhSaiEnt, tpNF, idDest, cMunFG, tpImp, tpEmis, tpAmb, finNFe, indFinal, indPres, procEmi, verProc |
| Emitente | `emit` + `enderEmit` | ✅ | xNome, xFant, IE, CRT, CNPJ + endereço completo |
| Destinatário | `dest` + `enderDest` | ✅ | CPF/CNPJ, xNome, indIEDest, IE + endereço |
| Produtos | `det/prod` | ✅ | cProd, cEAN, xProd, NCM, CEST, CFOP, uCom, qCom, vUnCom, vProd, cEANTrib, uTrib, qTrib, vUnTrib, indTot, vDesc |
| ICMS | `imposto/ICMS` | ✅ | ICMS Normal (CST) e Simples (CSOSN) |
| PIS | `imposto/PIS` | ✅ | CST, vBC, pPIS, vPIS |
| COFINS | `imposto/COFINS` | ✅ | CST, vBC, pCOFINS, vCOFINS |
| IPI | `imposto/IPI` | ✅ | CST, vBC, pIPI, vIPI (condicional) |
| ICMSTot | `total/ICMSTot` | ✅ | Todos os campos obrigatórios presentes |
| Transporte | `transp` | ✅ | modFrete dinâmico |
| Cobrança | `cobr` | ✅ | fat + dup (parcelas) |
| Pagamento | `pag/detPag` | ✅ | tPag mapeado, vPag, vTroco |
| Info. Adicional | `infAdic` | ✅ | infCpl com Lei 12.741 (IBPTax) |
| Resp. Técnico | `infRespTec` | ✅ | Condicional (se CNPJ configurado) |
| Lei 12.741 (IBPTax) | `vTotTrib` | ✅ | Integrado com IbptaxModel |
| **ICMS-ST** | `ICMSST` | ⚠️ Parcial | CST 10/201/202 — lógica simplificada |
| **DIFAL** | `ICMSUFDest` | ⚠️ Calculado | Método `calculateDIFAL()` existe, mas **não** é inserido no XML |
| **FCP** | `vFCPUFDest` | ❌ | Não implementado no XML |
| **II (Importação)** | `II` | ❌ | Não implementado |

### Operações SEFAZ

| Operação | Status | Observação |
|----------|--------|------------|
| `sefazStatus` (Status do Serviço) | ✅ | Implementado no `testConnection()` |
| `sefazEnviaLote` (Envio) | ✅ | Com retry e backoff |
| `sefazConsultaRecibo` | ✅ | Com retry (5 tentativas) |
| `sefazCancela` (Cancelamento) | ✅ | Com verificação de prazo (24h) |
| `sefazCCe` (Carta de Correção) | ✅ | Com limite de 20 CC-e, histórico em `nfe_correction_history` |
| `sefazConsultaChave` (Consulta) | ✅ | Implementado no service |
| `sefazDistDFe` (DistDFe) | ✅ | Por NSU e por chave |
| `sefazManifesta` (Manifestação) | ✅ | 4 tipos implementados |
| `sefazInutiliza` (Inutilização) | ⚠️ | Registra localmente, sem comunicação SEFAZ real |

---

## Conclusão

O módulo NF-e possui uma arquitetura sólida e abrangente, com a maioria das funcionalidades avançadas implementadas nos services. A auditoria identificou e **corrigiu** os seguintes problemas críticos:

### ✅ Correções Aplicadas

| # | Problema | Arquivo Corrigido | Status |
|---|----------|-------------------|--------|
| 1 | 5 métodos críticos do controller ausentes (`emit`, `cancel`, `correction`, `download`, `checkStatus`) | `NfeDocumentController.php` | ✅ Implementados |
| 2 | `saveFiscalTotals()` falhava — nomes de coluna errados | `NfeService.php` | ✅ Corrigido |
| 3 | `allowedFields` do `NfeDocument::update()` incompleto | `NfeDocument.php` | ✅ Corrigido |
| 4 | `allowedFields` do `NfeCredential::update()` sem `ultimo_nsu`, `filial_id`, `is_active` | `NfeCredential.php` | ✅ Corrigido |
| 5 | Colunas camelCase em `nfe_document_items` vs snake_case no PHP | Migration SQL criada | ✅ Pronto p/ aplicar |
| 6 | `enqueueBatch()` recebia mas ignorava `$batchId` | `NfeQueue.php` | ✅ Corrigido |
| 7 | `inutilizar()` usava `natureza_operacao` (inexistente) | `NfeService.php` | ✅ Corrigido |
| 8 | Query de IBPTax no `detail()` usava `vProd` (camelCase) | `NfeDocumentController.php` | ✅ Corrigido |
| 9 | `NfeReportModel::getTopCfops()` usava `ni.vProd` (camelCase) | `NfeReportModel.php` | ✅ Corrigido |

### 📄 Artefatos Gerados

| Tipo | Arquivo | Descrição |
|------|---------|-----------|
| SQL Migration | `sql/update_202603262000_auditoria_nfe_fixes.sql` | Renomear colunas + adicionar `batch_id` |
| Relatório | `docs/nfe/AUDITORIA_NFE_v2.md` | Este documento |
| Testes | `tests/Unit/NfeDocumentTest.php` | 12 testes unitários / 51 asserções |
| Rotas de teste | `tests/routes_test.php` | 6 novas rotas NF-e adicionadas |

### ⚠️ Ações Pendentes (manuais)

1. **Executar a migration SQL** no banco de dados de cada tenant
2. **Testar a interface** de emissão, cancelamento, correção e download na UI
3. Considerar implementações futuras: NFC-e (mod. 65), contingência offline, multi-filial, FCP, II (importação), DIFAL no XML

---

*Documento gerado automaticamente pela auditoria do sistema Akti — Março 2026*
