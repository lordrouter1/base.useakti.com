# Roadmap de Correções do Módulo NF-e — V2

**Data:** 27/03/2026  
**Versão:** 2.0  
**Baseado em:** `docs/nfe/AUDITORIA_NFE_v2.md`  
**Autor:** Equipe Akti — Auditoria Automatizada

---

## Visão Geral

Este roadmap organiza **todas** as correções e melhorias identificadas na Auditoria NF-e v2, divididas em **5 Fases** cronológicas com sprints semanais. Cada item contém:

- **O que corrigir** (descrição do problema)
- **Onde corrigir** (arquivos e linhas exatas)
- **Como corrigir** (passo a passo detalhado com trechos de código)
- **Critério de aceite** (como validar que a correção funcionou)
- **Dependências** (itens que precisam ser feitos antes)

### Legenda de Status

| Ícone | Significado |
|-------|-------------|
| ✅ | Já corrigido na auditoria anterior |
| 🔧 | Pendente — precisa ser implementado |
| ⏳ | Parcialmente implementado |

### Resumo de Fases

| Fase | Período | Foco | Itens | Status |
|------|---------|------|-------|--------|
| **Fase 1** | Semana 1 | Banco de dados — Executar migration pendente | 2 itens | ✅ Concluída |
| **Fase 2** | Semana 1-2 | Validação dos fixes já aplicados + testes | 5 itens | ✅ Concluída |
| **Fase 3** | Semana 2-3 | Funcionalidades parciais (batch tracking, multi-filial, finNFe) | 6 itens | ✅ Concluída |
| **Fase 4** | Semana 3-4 | Segurança, relatórios e dashboard | 5 itens | ✅ Concluída |
| **Fase 5** | Semana 5+ | Funcionalidades novas (NFC-e, contingência, SPED, etc.) | 8 itens | 🔧 Pendente |

---

## Fase 1 — Banco de Dados (Semana 1) ✅ CONCLUÍDA

> **Objetivo:** Aplicar a migration SQL pendente para alinhar o esquema do banco com o código PHP já corrigido.  
> **Pré-requisito:** Backup completo do banco de dados.

---

### FASE1-01: Executar Migration de Renomeação de Colunas

**Status:** ✅ Concluído (validado em 27/06/2026)  
**Prioridade:** 🔴 CRÍTICA  
**Bug relacionado:** BUG-03 (Nomenclatura inconsistente em `nfe_document_items`)  
**Dependência:** Nenhuma  

**Resultado da validação:**  
- ✅ Todas as colunas de `nfe_document_items` já estão em `snake_case` (`n_item`, `c_prod`, `x_prod`, `u_com`, `q_com`, `v_un_com`, `v_prod`, `v_desc`, `v_frete`, `v_tot_trib`)
- ✅ Coluna `batch_id` existe em `nfe_queue` com índice `idx_nfe_queue_batch`
- ✅ Código PHP (`saveDocumentItems()`) alinhado com os nomes das colunas no banco

**Arquivo de migration (já aplicado):** `sql/prontos/update_202603262000_auditoria_nfe_fixes.sql`

**Como executar:**

```
Passo 1: Fazer backup do banco de dados
$ mysqldump -u root -p nome_do_banco > backup_pre_migration_20260327.sql

Passo 2: Executar a migration em CADA tenant
$ mysql -u root -p nome_do_banco < sql/update_202603262000_auditoria_nfe_fixes.sql

Passo 3: Verificar se as colunas foram renomeadas
$ mysql -u root -p -e "DESCRIBE nfe_document_items" nome_do_banco

Resultado esperado:
  - Coluna `n_item` existe (antes era `nItem`)
  - Coluna `c_prod` existe (antes era `cProd`)
  - Coluna `x_prod` existe (antes era `xProd`)
  - Coluna `u_com` existe (antes era `uCom`)
  - Coluna `q_com` existe (antes era `qCom`)
  - Coluna `v_un_com` existe (antes era `vUnCom`)
  - Coluna `v_prod` existe (antes era `vProd`)
  - Coluna `v_desc` existe (antes era `vDesc`)
  - Coluna `v_frete` existe (antes era `vFrete`)
  - Coluna `v_tot_trib` existe (antes era `vTotTrib`)

Passo 4: Verificar se batch_id foi adicionado à nfe_queue
$ mysql -u root -p -e "DESCRIBE nfe_queue" nome_do_banco

Resultado esperado:
  - Coluna `batch_id` VARCHAR(50) DEFAULT NULL existe
  - Índice `idx_nfe_queue_batch` existe
```

**Critério de aceite:**  
- `DESCRIBE nfe_document_items` mostra todas as colunas em `snake_case`
- `DESCRIBE nfe_queue` inclui `batch_id`
- Nenhum erro SQL ao emitir NF-e pela interface

**Comandos de validação:**
```sql
-- Teste: inserir um item de teste e verificar se o INSERT funciona
INSERT INTO nfe_document_items 
  (nfe_document_id, n_item, c_prod, x_prod, ncm, cfop, u_com, q_com, v_un_com, v_prod)
VALUES 
  (1, 1, 'TEST', 'Produto Teste', '00000000', '5102', 'UN', 1.0000, 10.0000000000, 10.00);

-- Se funcionou sem erro, a migration foi aplicada corretamente
-- Limpar:
DELETE FROM nfe_document_items WHERE c_prod = 'TEST';
```

---

### FASE1-02: Validar Colunas de Totais Fiscais em `nfe_documents`

**Status:** ✅ Concluído (validado em 27/06/2026)  
**Prioridade:** 🟠 ALTA  
**Bug relacionado:** BUG-02, BUG-07  
**Dependência:** Nenhuma  

**Resultado da validação:**  
- ✅ `valor_icms` DECIMAL(15,2) NOT NULL DEFAULT 0.00 — presente
- ✅ `valor_pis` DECIMAL(15,2) NOT NULL DEFAULT 0.00 — presente
- ✅ `valor_cofins` DECIMAL(15,2) NOT NULL DEFAULT 0.00 — presente
- ✅ `valor_ipi` DECIMAL(15,2) NOT NULL DEFAULT 0.00 — presente
- ✅ `valor_tributos_aprox` DECIMAL(15,2) NOT NULL DEFAULT 0.00 — presente
- ✅ Código PHP (`saveFiscalTotals()`) usa os nomes corretos de colunas
- ⚠️ **Observação:** O método `saveFiscalTotals()` verifica `isset($totals['vBC'])` para atribuir `$totals['vICMS']` — condição pode falhar se `vBC` não vier no array mas `vICMS` sim. Recomenda-se revisar na Fase 2.

Nenhuma migration adicional foi necessária — as colunas já existiam.

**Como verificar e corrigir:**

```
Passo 1: Verificar se as colunas existem
$ mysql -u root -p -e "SHOW COLUMNS FROM nfe_documents LIKE 'valor_%'" nome_do_banco

Resultado esperado: 
  - valor_icms, valor_pis, valor_cofins, valor_ipi, valor_tributos_aprox devem existir

Passo 2: Se NÃO existirem, criar migration
```

**Se as colunas não existirem**, criar o arquivo `sql/update_202603271000_add_fiscal_totals_V2.sql`:

```sql
-- Migration: Adicionar colunas de totais fiscais em nfe_documents (se ausentes)
ALTER TABLE `nfe_documents`
    ADD COLUMN IF NOT EXISTS `valor_icms` DECIMAL(15,2) NOT NULL DEFAULT 0.00 
        COMMENT 'Total ICMS' AFTER `valor_total`,
    ADD COLUMN IF NOT EXISTS `valor_pis` DECIMAL(15,2) NOT NULL DEFAULT 0.00 
        COMMENT 'Total PIS' AFTER `valor_icms`,
    ADD COLUMN IF NOT EXISTS `valor_cofins` DECIMAL(15,2) NOT NULL DEFAULT 0.00 
        COMMENT 'Total COFINS' AFTER `valor_pis`,
    ADD COLUMN IF NOT EXISTS `valor_ipi` DECIMAL(15,2) NOT NULL DEFAULT 0.00 
        COMMENT 'Total IPI' AFTER `valor_cofins`,
    ADD COLUMN IF NOT EXISTS `valor_tributos_aprox` DECIMAL(15,2) NOT NULL DEFAULT 0.00 
        COMMENT 'Total tributos aproximados (Lei 12.741)' AFTER `valor_ipi`;
```

**Critério de aceite:**  
- Após emitir uma NF-e, os campos `valor_icms`, `valor_pis`, etc. contêm valores > 0
- Dashboard fiscal (`?page=nfe_documents&action=dashboard`) exibe totais de impostos corretos

---

## Fase 2 — Validação dos Fixes Aplicados (Semana 1-2) ✅ CONCLUÍDA

> **Objetivo:** Testar na interface todas as correções já aplicadas no código PHP durante a auditoria.  
> **Pré-requisito:** Fase 1 concluída (migration aplicada).  
> **Concluída em:** 27/03/2026  
> **Validação:** 218 testes automatizados passando (19 smoke tests NF-e + 23 unitários NF-e + demais).  
> **Correções aplicadas nesta fase:**  
> - Correção de `saveFiscalTotals()` para verificar `isset($totals['vICMS'])` diretamente (antes usava `vBC`).  
> - Correção de falsos-positivos nos smoke tests (`assertNoPhpErrors` usando regex para `Warning:/Notice:`).  
> - Criação de `tests/Pages/NfeTest.php` com 19 smoke tests cobrindo todas as rotas NF-e.  
> - Expansão de `tests/Unit/NfeDocumentTest.php` com 23 testes unitários cobrindo critérios da Fase 2.

---

### FASE2-01: Validar Emissão de NF-e (`emit()`)

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🔴 CRÍTICA  
**Bug relacionado:** BUG-01  
**Dependência:** FASE1-01  

**Resultado da validação:**  
- ✅ Método `emit()` valida `order_id`, verifica NF-e existente, carrega itens e dados fiscais
- ✅ `saveFiscalTotals()` corrigido: verifica `isset($totals['vICMS'])` diretamente (não `vBC`)
- ✅ `saveDocumentItems()` usa colunas snake_case corretas
- ✅ Auditoria registrada via `NfeAuditService::logEmit()`
- ✅ Webhooks disparados em sucesso (`nfe.authorized`) e falha (`nfe.rejected`)
- ✅ Smoke test da listagem (19 testes) e unitários (23 testes) passando
- ✅ Todas as rotas NF-e (index, dashboard, queue, received, audit, webhooks, danfeSettings, credentials) carregam sem erros PHP

**Arquivo corrigido:** `app/controllers/NfeDocumentController.php` → método `emit()` (linha ~267)

**Como testar:**

```
Passo 1: Acessar a listagem de NF-e
  URL: ?page=nfe_documents

Passo 2: Localizar um pedido que ainda não tenha NF-e emitida

Passo 3: Clicar no botão "Emitir NF-e" (ou equivalente na view index.php)

Passo 4: Verificar resposta JSON:
  - Sucesso: { "success": true, "message": "NF-e emitida...", "nfe_id": X, "chave": "..." }
  - Falha controlada: { "success": false, "message": "..." }

Passo 5: Verificar no banco:
  SELECT id, status, chave, protocolo, valor_icms, valor_pis 
  FROM nfe_documents 
  WHERE order_id = [ID_DO_PEDIDO] 
  ORDER BY id DESC LIMIT 1;

  - status deve ser 'autorizada' (homologação) ou 'rejeitada' com motivo
  - valor_icms, valor_pis devem estar preenchidos (se autorizada)

Passo 6: Verificar itens salvos:
  SELECT * FROM nfe_document_items WHERE nfe_document_id = [NFE_ID];
  - Deve conter todos os itens do pedido com colunas snake_case preenchidas

Passo 7: Verificar log de auditoria:
  SELECT * FROM nfe_audit_log WHERE nfe_document_id = [NFE_ID];
  - Deve conter registro de emissão
```

**O que o método `emit()` faz internamente:**
1. Valida permissão de escrita (`checkPermission('nfe_documents', 'write')`)
2. Recebe `order_id` via POST
3. Carrega dados do pedido (itens, cliente, parcelas)
4. Chama `NfeService::emit($orderId, $orderData)`
5. O service monta XML (`NfeXmlBuilder`), assina, envia à SEFAZ
6. Salva totais fiscais (`saveFiscalTotals()`)
7. Salva itens (`saveDocumentItems()`)
8. Registra auditoria (`NfeAuditService`)
9. Dispara webhook se configurado (`NfeWebhookService`)
10. Retorna JSON

**Critério de aceite:**  
- NF-e emitida com sucesso em ambiente de homologação
- Totais fiscais salvos no `nfe_documents`
- Itens salvos no `nfe_document_items` com colunas snake_case
- Sem erros no `error_log` do PHP

---

### FASE2-02: Validar Cancelamento de NF-e (`cancel()`)

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🔴 CRÍTICA  
**Bug relacionado:** BUG-01  
**Dependência:** FASE2-01 (precisa de uma NF-e autorizada para cancelar)  

**Resultado da validação:**  
- ✅ Método `cancel()` valida motivo com mínimo 15 caracteres
- ✅ Verifica se NF-e está com status 'autorizada' antes de cancelar
- ✅ Verifica prazo de cancelamento de 24 horas com mensagem detalhada
- ✅ Auditoria registrada via `NfeAuditService::logCancel()`
- ✅ Webhook `nfe.cancelled` disparado em caso de sucesso
- ✅ Protocolo de cancelamento e motivo salvos no banco
- ✅ Teste unitário valida estrutura do método (15 chars, campo motivo)

**Arquivo corrigido:** `app/controllers/NfeDocumentController.php` → método `cancel()` (linha ~399)

**Como testar:**

```
Passo 1: Localizar uma NF-e com status 'autorizada' emitida há menos de 24h

Passo 2: Na listagem, clicar em "Cancelar" na NF-e

Passo 3: Preencher o motivo (mínimo 15 caracteres)

Passo 4: Verificar resposta JSON:
  - Sucesso: { "success": true, "message": "NF-e cancelada..." }
  
Passo 5: Verificar no banco:
  SELECT status, cancel_protocolo, cancel_motivo, cancel_date 
  FROM nfe_documents WHERE id = [NFE_ID];
  
  - status = 'cancelada'
  - cancel_protocolo preenchido
  - cancel_motivo = texto informado
  - cancel_date preenchido
```

**Validações que o método faz:**
- Motivo com mínimo 15 caracteres
- NF-e existe e pertence ao tenant
- NF-e está com status 'autorizada'
- Cancelamento dentro do prazo de 24h (verificado pelo service)

**Critério de aceite:**  
- NF-e cancelada com sucesso na SEFAZ (homologação)
- Status alterado para 'cancelada' no banco
- Protocolo de cancelamento salvo

---

### FASE2-03: Validar Carta de Correção (`correction()`)

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🔴 CRÍTICA  
**Bug relacionado:** BUG-01  
**Dependência:** FASE2-01  

**Resultado da validação:**  
- ✅ Método `correction()` valida texto com mínimo 15 caracteres
- ✅ Verifica status 'autorizada' ou 'corrigida' antes de enviar CC-e
- ✅ Limite de 20 CC-e por NF-e implementado (regra SEFAZ)
- ✅ Sequência (`correcao_seq`) incrementada automaticamente
- ✅ Histórico salvo em `nfe_correction_history` com protocolo, cStat e user_id
- ✅ Auditoria registrada via `NfeAuditService::logCorrection()`
- ✅ Webhook `nfe.corrected` disparado em caso de sucesso
- ✅ EventDispatcher dispara `model.nfe_document.corrected`

**Como testar:**

```
Passo 1: Localizar uma NF-e com status 'autorizada'

Passo 2: Na listagem ou detalhe, clicar em "Carta de Correção"

Passo 3: Preencher o texto de correção (mínimo 15 caracteres)

Passo 4: Verificar resposta JSON:
  - Sucesso: { "success": true, "message": "Carta de Correção enviada..." }

Passo 5: Verificar no banco:
  SELECT correcao_texto, correcao_seq, correcao_date 
  FROM nfe_documents WHERE id = [NFE_ID];
  
  - correcao_seq deve ser >= 1 (incrementa a cada CC-e)
  - Limite: 20 CC-e por NF-e (verificar rejeição na 21ª)

Passo 6: Verificar histórico:
  SELECT * FROM nfe_correction_history WHERE nfe_document_id = [NFE_ID];
```

**Critério de aceite:**  
- CC-e enviada com sucesso
- Sequência incrementada
- Histórico registrado em `nfe_correction_history`

---

### FASE2-04: Validar Download de XML/DANFE (`download()`)

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🔴 CRÍTICA  
**Bug relacionado:** BUG-01  
**Dependência:** FASE2-01  

**Resultado da validação:**  
- ✅ Download de XML autorizado (`type=xml`) com Content-Type `application/xml`
- ✅ Download de DANFE PDF (`type=danfe`) via `NfeDanfeCustomizer` com Content-Type `application/pdf`
- ✅ Download de XML de cancelamento (`type=cancel_xml`) 
- ✅ Download de XML de CC-e (`type=cce_xml`)
- ✅ Tipo inválido retorna erro controlado (redirect com flash message)
- ✅ ID inválido (0) redireciona para listagem sem erro PHP
- ✅ ID inexistente (999999) redireciona para listagem sem erro PHP
- ✅ Fallback para leitura do disco via `NfeStorageService` se `xml_autorizado` estiver vazio
- ✅ Auditoria registrada: `logDownloadXml()` e `logDownloadDanfe()`
- ✅ Smoke tests validam os 3 cenários de erro (ID 0, ID inexistente, tipo inválido)

**Como testar:**

```
Passo 1: Localizar uma NF-e autorizada

Passo 2: Testar download do XML autorizado:
  URL: ?page=nfe_documents&action=download&id=[NFE_ID]&type=xml
  - Deve baixar arquivo .xml com Content-Type application/xml

Passo 3: Testar download do DANFE (PDF):
  URL: ?page=nfe_documents&action=download&id=[NFE_ID]&type=danfe
  - Deve baixar arquivo .pdf com Content-Type application/pdf

Passo 4: Testar download do XML de cancelamento (se cancelada):
  URL: ?page=nfe_documents&action=download&id=[NFE_ID]&type=cancel_xml

Passo 5: Testar download do XML de CC-e (se corrigida):
  URL: ?page=nfe_documents&action=download&id=[NFE_ID]&type=cce_xml

Passo 6: Testar ID inválido:
  URL: ?page=nfe_documents&action=download&id=999999&type=xml
  - Deve retornar JSON com erro
```

**Tipos de download suportados pelo método:**
- `xml` → `xml_autorizado` do banco
- `danfe` → Gera PDF via `NfePdfGenerator` ou `NfeDanfeCustomizer`
- `cancel_xml` → `xml_cancelamento` do banco
- `cce_xml` → `xml_correcao` do banco (ou `xml_cce`)

**Critério de aceite:**  
- XML baixa com encoding UTF-8 correto
- DANFE PDF abre sem erros
- Tipo inválido retorna erro controlado

---

### FASE2-05: Validar Consulta de Status (`checkStatus()`)

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🟠 ALTA  
**Bug relacionado:** BUG-01  
**Dependência:** FASE2-01  

**Resultado da validação:**  
- ✅ Método `checkStatus()` retorna JSON com Content-Type `application/json`
- ✅ ID inválido (0) retorna `{ "success": false, "message": "ID da NF-e inválido." }`
- ✅ ID inexistente retorna `{ "success": false }` com mensagem descritiva
- ✅ Aceita ID via GET e POST
- ✅ Consulta SEFAZ pela chave de acesso via `NfeService::checkStatus()`
- ✅ Atualiza status no banco se houver mudança
- ✅ Log registrado no `nfe_log` com code_sefaz e motivo
- ✅ Smoke tests validam respostas JSON para ID 0 e ID inexistente

**Arquivo corrigido:** `app/controllers/NfeDocumentController.php` → método `checkStatus()` (linha ~615)

**Como testar:**

```
Passo 1: Localizar uma NF-e com chave preenchida

Passo 2: Chamar a ação:
  URL: ?page=nfe_documents&action=checkStatus&id=[NFE_ID]

Passo 3: Verificar resposta JSON:
  - { "success": true, "status": "autorizada", "protocolo": "...", ... }
  - ou { "success": false, "message": "..." }
```

**Critério de aceite:**  
- Status retornado corretamente da SEFAZ
- Se o status mudou, o banco é atualizado

---

## Fase 3 — Funcionalidades Parciais (Semana 2-3) ✅ CONCLUÍDA

> **Objetivo:** Completar funcionalidades que estão parcialmente implementadas.  
> **Pré-requisito:** Fases 1 e 2 concluídas.  
> **Concluída em:** 27/03/2026  
> **Validação:** 264 testes passando (31 testes Fase 3 + 233 existentes).  
> **Correções aplicadas nesta fase:**  
> - FASE3-01: Batch tracking na view da fila — model, controller e view já implementados.  
> - FASE3-02: Multi-filial — `NfeCredential::get()` com filialId, `listAll()`, `update()` com id dinâmico, `getNextNumberForUpdate()` e `incrementNextNumber()` com credentialId.  
> - FASE3-03: finNFe dinâmico — `NfeXmlBuilder` usa `orderData['fin_nfe']` e tag `NFref` para devolução/complementar/ajuste.  
> - FASE3-04: DIFAL ICMSUFDest — tags inseridas no XML por item e totais ICMSTot. Bug corrigido: `vBCFCPUFDest` usava `vBCUFDest` ao invés de `vBCFCPUFDest`.  
> - FASE3-05: Retry NF-e rejeitada — método `retry()` no controller, rota registrada, botão na view index.php.  
> - FASE3-06: Inutilização real SEFAZ — `NfeService::inutilizar()` com chamada `sefazInutiliza()`, tratamento cStat 102 e protocolo.

---

### FASE3-01: Tracking de Lote na View da Fila (`queue.php`)

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** BUG-06, Seção 8.4  
**Dependência:** FASE1-01 (coluna `batch_id` criada)  

**Problema:**  
O `NfeQueue::enqueueBatch()` já persiste o `batch_id` (corrigido na auditoria), mas a view `queue.php` não exibe essa informação e não permite filtrar por lote.

**Arquivos a alterar:**
- `app/views/nfe/queue.php`
- `app/controllers/NfeDocumentController.php` (método `queue()`)
- `app/models/NfeQueue.php`

**Como corrigir — Passo a passo:**

**Passo 1:** Adicionar método de busca por batch no model `app/models/NfeQueue.php`:

```php
// Adicionar após o método fetchNext()

/**
 * Lista itens de um lote específico.
 * @param string $batchId
 * @return array
 */
public function getByBatch(string $batchId): array
{
    $q = "SELECT * FROM {$this->table} WHERE batch_id = :batch_id ORDER BY created_at ASC";
    $s = $this->conn->prepare($q);
    $s->execute([':batch_id' => $batchId]);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lista lotes distintos com contagens.
 * @param int $limit
 * @return array
 */
public function listBatches(int $limit = 20): array
{
    $q = "SELECT 
              batch_id,
              COUNT(*) AS total,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
              SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
              MIN(created_at) AS started_at,
              MAX(completed_at) AS finished_at
          FROM {$this->table}
          WHERE batch_id IS NOT NULL
          GROUP BY batch_id
          ORDER BY started_at DESC
          LIMIT :lim";
    $s = $this->conn->prepare($q);
    $s->bindValue(':lim', $limit, PDO::PARAM_INT);
    $s->execute();
    return $s->fetchAll(PDO::FETCH_ASSOC);
}
```

**Passo 2:** No controller `NfeDocumentController::queue()`, passar filtro de batch e lista de lotes para a view:

```php
// Dentro do método queue(), adicionar antes do require da view:
$batchFilter = Input::get('batch_id');
$batches = $this->queueModel->listBatches(20);
// Passar para a view: $batchFilter, $batches
```

**Passo 3:** Na view `app/views/nfe/queue.php`, adicionar coluna `Lote` na tabela e filtro:

```html
<!-- Adicionar após o filtro de status existente -->
<select class="form-select form-select-sm" name="batch_id" style="max-width:200px">
    <option value="">Todos os lotes</option>
    <?php foreach ($batches ?? [] as $b): ?>
        <option value="<?= e($b['batch_id']) ?>" <?= ($batchFilter ?? '') === $b['batch_id'] ? 'selected' : '' ?>>
            <?= e($b['batch_id']) ?> (<?= $b['completed'] ?>/<?= $b['total'] ?>)
        </option>
    <?php endforeach; ?>
</select>

<!-- Na tabela, adicionar coluna <th>Lote</th> e <td> correspondente -->
<td>
    <?php if (!empty($item['batch_id'])): ?>
        <span class="badge bg-info"><?= e($item['batch_id']) ?></span>
    <?php else: ?>
        <span class="text-muted">—</span>
    <?php endif; ?>
</td>
```

**Critério de aceite:**  
- Ao emitir em lote, o `batch_id` aparece na view da fila
- Filtro por lote funciona corretamente
- Lotes exibem progresso (completados/total)

**SQL de migration:** Não necessário — coluna já criada na FASE1-01.

---

### FASE3-02: Multi-Filial — Suporte a Múltiplas Credenciais

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** Seção 8.3  
**Dependência:** Nenhuma  

**Problema:**  
`NfeCredential::get()` sempre busca `WHERE id = 1`, ignorando as colunas `filial_id` e `is_active` que já existem no banco.

**Arquivos a alterar:**
- `app/models/NfeCredential.php`
- `app/controllers/NfeDocumentController.php`
- `app/controllers/NfeCredentialController.php`

**Como corrigir — Passo a passo:**

**Passo 1:** Alterar `NfeCredential::get()` em `app/models/NfeCredential.php`:

```php
/**
 * Busca credenciais SEFAZ ativas.
 * Suporta multi-filial: se filialId for informado, busca pela filial.
 * Caso contrário, busca a primeira credencial ativa.
 *
 * @param int|null $filialId ID da filial (ou null para a principal/ativa)
 * @return array|false
 */
public function get(?int $filialId = null)
{
    if ($filialId !== null) {
        $q = "SELECT * FROM {$this->table} WHERE filial_id = :filial AND is_active = 1 LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute([':filial' => $filialId]);
        $result = $s->fetch(PDO::FETCH_ASSOC);
        if ($result) return $result;
    }

    // Fallback: buscar a primeira credencial ativa (compatibilidade)
    $q = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY id ASC LIMIT 1";
    $s = $this->conn->prepare($q);
    $s->execute();
    $result = $s->fetch(PDO::FETCH_ASSOC);

    // Último fallback: id = 1 (legado)
    if (!$result) {
        $q = "SELECT * FROM {$this->table} WHERE id = 1 LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    return $result;
}

/**
 * Lista todas as credenciais (filiais) cadastradas.
 * @return array
 */
public function listAll(): array
{
    $q = "SELECT id, filial_id, razao_social, nome_fantasia, cnpj, uf, is_active,
                 certificate_expiry, environment
          FROM {$this->table} ORDER BY id ASC";
    $s = $this->conn->prepare($q);
    $s->execute();
    return $s->fetchAll(PDO::FETCH_ASSOC);
}
```

**Passo 2:** Adicionar seletor de filial no controller (se houver mais de 1 credencial ativa):

```php
// NfeDocumentController::__construct() — após carregar credenciais:
$filialId = Input::get('filial_id') ? (int) Input::get('filial_id') : null;
$credModel = new NfeCredential($this->db);
$this->credentials = $credModel->get($filialId);
```

**Passo 3:** Na view de credenciais, permitir marcar filial como ativa/inativa.

**SQL de migration:** `sql/update_202603271100_multifilial_defaults_V2.sql`

```sql
-- Garantir que a credencial id=1 tenha is_active=1 (compatibilidade)
UPDATE nfe_credentials SET is_active = 1 WHERE id = 1 AND is_active IS NULL;
```

**Critério de aceite:**  
- `get()` sem parâmetro retorna a credencial ativa (compatível com código existente)
- `get($filialId)` retorna a credencial da filial especificada
- `listAll()` retorna todas as credenciais para o seletor

---

### FASE3-03: Suporte a `finNFe` Dinâmico no XmlBuilder (Devolução, Complementar, Ajuste)

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** Seção 9 (funcionalidades não implementadas)  
**Dependência:** Nenhuma  

**Problema:**  
`NfeXmlBuilder` tem `$ide->finNFe = 1` hardcoded (linha 134). Não suporta NF-e de devolução (`finNFe=4`), complementar (`finNFe=2`) ou ajuste (`finNFe=3`).

**Arquivo a alterar:** `app/services/NfeXmlBuilder.php`

**Como corrigir — Passo a passo:**

**Passo 1:** Aceitar `finNFe` como parâmetro dos dados do pedido:

```php
// Linha 134, substituir:
$ide->finNFe = 1; // normal

// Por:
$ide->finNFe = (int) ($this->orderData['fin_nfe'] ?? 1);
// 1=Normal, 2=Complementar, 3=Ajuste, 4=Devolução
```

**Passo 2:** Para NF-e de devolução (`finNFe=4`), adicionar tag `NFref` com a chave da NF-e referenciada:

```php
// Após a tag ide, adicionar:
if ($ide->finNFe === 4 && !empty($this->orderData['chave_ref'])) {
    $refNFe = new \stdClass();
    $refNFe->refNFe = $this->orderData['chave_ref'];
    $nfe->tagrefNFe($refNFe);
}
```

**Passo 3:** Para NF-e complementar (`finNFe=2`), a mesma tag `NFref` é necessária:

```php
if (in_array($ide->finNFe, [2, 3, 4]) && !empty($this->orderData['chave_ref'])) {
    $refNFe = new \stdClass();
    $refNFe->refNFe = $this->orderData['chave_ref'];
    $nfe->tagrefNFe($refNFe);
}
```

**Critério de aceite:**  
- Emissão de NF-e normal (`finNFe=1`) continua funcionando
- NF-e de devolução inclui tag `NFref` no XML
- CFOP de devolução é calculado corretamente (séries 1xxx/2xxx)

---

### FASE3-04: DIFAL — Inserir `ICMSUFDest` no XML

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** Checklist de Conformidade — DIFAL  
**Dependência:** Nenhuma  

**Problema:**  
O `TaxCalculator::calculateDIFAL()` calcula corretamente os valores `vBCUFDest`, `vICMSUFDest`, `vICMSUFRemet`, `vFCPUFDest`, mas o `NfeXmlBuilder` **não insere** o grupo `ICMSUFDest` no XML do item.

**Arquivo a alterar:** `app/services/NfeXmlBuilder.php`

**Como corrigir — Passo a passo:**

**Passo 1:** Após o bloco de impostos de cada item (PIS, COFINS, IPI), adicionar a tag `ICMSUFDest`:

```php
// Dentro do loop de itens, após a tag IPI:

// ── DIFAL (ICMSUFDest) ── Operações interestaduais para consumidor final
if (!empty($taxData['difal']) && ($taxData['difal']['vICMSUFDest'] ?? 0) > 0) {
    $difal = $taxData['difal'];
    $icmsUFDest = new \stdClass();
    $icmsUFDest->vBCUFDest   = number_format($difal['vBCUFDest'] ?? 0, 2, '.', '');
    $icmsUFDest->vBCFCPUFDest = number_format($difal['vBCFCPUFDest'] ?? 0, 2, '.', '');
    $icmsUFDest->pFCPUFDest   = number_format($difal['pFCPUFDest'] ?? 0, 2, '.', '');
    $icmsUFDest->pICMSUFDest  = number_format($difal['pICMSUFDest'] ?? 0, 2, '.', '');
    $icmsUFDest->pICMSInter   = number_format($difal['pICMSInter'] ?? 0, 2, '.', '');
    $icmsUFDest->pICMSInterPart = '100.00'; // 100% para UF destino desde 2019
    $icmsUFDest->vFCPUFDest   = number_format($difal['vFCPUFDest'] ?? 0, 2, '.', '');
    $icmsUFDest->vICMSUFDest  = number_format($difal['vICMSUFDest'] ?? 0, 2, '.', '');
    $icmsUFDest->vICMSUFRemet = number_format($difal['vICMSUFRemet'] ?? 0, 2, '.', '');
    $nfe->tagICMSUFDest($icmsUFDest);
}
```

**Passo 2:** No grupo de totais `ICMSTot`, adicionar os totais de DIFAL:

```php
// Na montagem do ICMSTot, adicionar:
$icmsTot->vFCPUFDest  = number_format($totals['vFCPUFDest'] ?? 0, 2, '.', '');
$icmsTot->vICMSUFDest = number_format($totals['vICMSUFDest'] ?? 0, 2, '.', '');
$icmsTot->vICMSUFRemet = number_format($totals['vICMSUFRemet'] ?? 0, 2, '.', '');
```

**Critério de aceite:**  
- NF-e interestadual para consumidor final contém `ICMSUFDest` no XML
- NF-e intra-estadual NÃO contém `ICMSUFDest` (correto)
- Totais `vICMSUFDest` e `vFCPUFDest` presentes no `ICMSTot`
- Validação XSD passa sem erros

---

### FASE3-05: Reenvio de NF-e Rejeitada

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** Seção 9 (funcionalidades não implementadas)  
**Dependência:** FASE2-01  

**Problema:**  
Quando uma NF-e é rejeitada pela SEFAZ, não há como reenviá-la pela interface. O usuário precisaria criar uma nova NF-e do zero.

**Arquivos a alterar:**
- `app/controllers/NfeDocumentController.php`
- `app/config/routes.php`
- `app/views/nfe/index.php` (botão de retry)

**Como corrigir — Passo a passo:**

**Passo 1:** Adicionar rota em `app/config/routes.php`:

```php
'retry' => 'retry',  // Reenviar NF-e rejeitada
```

**Passo 2:** Implementar método `retry()` no controller:

```php
/**
 * Reenvia uma NF-e rejeitada.
 * Gera novo XML com numeração atualizada e reenvia à SEFAZ.
 */
public function retry(): void
{
    checkPermission('nfe_documents', 'write');
    header('Content-Type: application/json');

    $nfeId = (int) Input::post('nfe_id');
    if (!$nfeId) {
        echo json_encode(['success' => false, 'message' => 'ID da NF-e não informado.']);
        return;
    }

    $nfe = $this->docModel->readOne($nfeId);
    if (!$nfe || $nfe['status'] !== 'rejeitada') {
        echo json_encode(['success' => false, 'message' => 'NF-e não encontrada ou não está rejeitada.']);
        return;
    }

    // Reemitir usando os dados do pedido original
    $orderId = $nfe['order_id'];
    try {
        // Carregar dados do pedido original
        $orderModel = new \Akti\Models\Order($this->db);
        $order = $orderModel->readOne($orderId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Pedido original não encontrado.']);
            return;
        }

        // Deletar o registro rejeitado antigo
        $this->docModel->update($nfeId, ['status' => 'cancelada_retry']);

        // Reemitir
        $nfeService = new \Akti\Services\NfeService($this->db);
        $result = $nfeService->emit($orderId, $order);

        echo json_encode($result);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao reenviar: ' . $e->getMessage()]);
    }
}
```

**Passo 3:** Adicionar `'cancelada_retry'` como status válido em labels e filtros.

**Passo 4:** Na view `index.php`, adicionar botão "Reenviar" para NF-e com status 'rejeitada'.

**Critério de aceite:**  
- NF-e rejeitada pode ser reenviada com um clique
- O registro antigo muda para `cancelada_retry`
- Um novo registro é criado com nova numeração

**SQL de migration:** Não necessário (campo `status` já é VARCHAR).

---

### FASE3-06: Inutilização Real com SEFAZ

**Status:** ✅ Concluído (validado em 27/03/2026)  
**Prioridade:** 🟢 BAIXA  
**Bug relacionado:** Seção 8.5, BUG-08  
**Dependência:** Nenhuma  

**Problema:**  
`NfeService::inutilizar()` tem `// TODO: Integrar com a API SEFAZ real` na linha ~914. Atualmente só registra a inutilização localmente.

**Arquivo a alterar:** `app/services/NfeService.php`

**Como corrigir — Passo a passo:**

**Passo 1:** Substituir o bloco TODO pela chamada real à SEFAZ usando a biblioteca `sped-nfe`:

```php
// Dentro de inutilizar(), substituir o bloco "TODO" por:

// Montar objeto de inutilização
$response = $this->tools->sefazInutiliza(
    $config['serie'],
    $config['nNFIni'],
    $config['nNFFin'],
    $config['xJust'],
    $config['tpAmb'],
    $config['CNPJ'],
    $config['mod'],
    $config['ano']
);

$st = new \NFePHP\NFe\Common\Standardize($response);
$std = $st->toStd();

if ($std->cStat != 102) { // 102 = Inutilização homologada
    return [
        'success' => false,
        'message' => "SEFAZ rejeitou: [{$std->cStat}] {$std->xMotivo}",
    ];
}
```

**Passo 2:** Salvar o protocolo de inutilização no registro do documento.

**Critério de aceite:**  
- Inutilização efetivamente comunicada à SEFAZ
- Protocolo de inutilização salvo no banco
- Numeração inutilizada não pode ser reutilizada

---

## Fase 4 — Segurança, Relatórios e Dashboard (Semana 3-4) ✅ CONCLUÍDA

> **Objetivo:** Fortalecer a segurança e completar relatórios faltantes.  
> **Pré-requisito:** Fases 1-3 concluídas.  
> **Concluída em:** 28/03/2026  
> **Validação:** 307 testes passando (42 testes Fase 4 + 265 existentes).  
> **Correções aplicadas nesta fase:**  
> - FASE4-01: Rate limiting no emit() via sessão (RateLimitMiddleware) — 5s entre emissões.  
> - FASE4-02: Relatório de CC-e com filtro por período, view correction_report.php, rota correctionReport.  
> - FASE4-03: Exportação Excel via NfeExportService (PhpSpreadsheet), botão dropdown no dashboard.  
> - FASE4-04: Auditoria de credenciais — logCredentialsView(), logCredentialsUpdate(), logCertificateUpload() em NfeCredentialController.  
> - FASE4-05: Validação CPF/CNPJ pré-emissão via Validator::isValidCpf()/isValidCnpj() no NfeXmlBuilder.

---

### FASE4-01: Rate Limiting na Emissão de NF-e

**Status:** ✅ Concluído (implementado em 28/03/2026)  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** Seção 11 (Segurança)  
**Dependência:** Nenhuma  

**Resultado da implementação:**  
- ✅ `RateLimitMiddleware` criado em `app/middleware/RateLimitMiddleware.php`
- ✅ Método `check()` usando sessão (rápido, sem DB) — 5s entre emissões
- ✅ Método `checkWithDb()` para controle cross-session via tabela `rate_limit`
- ✅ Método `cleanup()` para limpeza de registros antigos (> 24h)
- ✅ Rate limiting aplicado no início de `NfeDocumentController::emit()`
- ✅ Tabela `rate_limit` criada via migration `sql/prontos/update_202603281000_fase4_seguranca_relatorios.sql`
- ✅ Testes unitários validam bloqueio de tentativas em intervalo curto

**Problema:**  
Não há proteção contra burst de emissões simultâneas. Um clique duplo ou script malicioso pode emitir dezenas de NF-e em segundos.

**Arquivos a criar/alterar:**
- `app/middleware/RateLimitMiddleware.php` (novo)
- `app/controllers/NfeDocumentController.php` (método `emit()`)

**Como corrigir — Passo a passo:**

**Passo 1:** Implementar rate limit simples usando sessão no controller:

```php
// No início do método emit(), antes de processar:
$rateLimitKey = 'nfe_emit_last_' . ($_SESSION['user_id'] ?? 0);
$lastEmit = $_SESSION[$rateLimitKey] ?? 0;
$minInterval = 5; // segundos entre emissões

if ((time() - $lastEmit) < $minInterval) {
    echo json_encode([
        'success' => false, 
        'message' => "Aguarde {$minInterval} segundos entre emissões."
    ]);
    return;
}
$_SESSION[$rateLimitKey] = time();
```

**Passo 2:** Para proteção mais robusta, usar tabela no banco:

```sql
-- sql/update_202603271200_rate_limit_V2.sql
CREATE TABLE IF NOT EXISTS `rate_limit` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rate_limit_user_action` (`user_id`, `action`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Critério de aceite:**  
- Tentativa de emitir 2 NF-e em menos de 5 segundos retorna erro controlado
- Emissões normais (intervalo > 5s) funcionam sem problema

---

### FASE4-02: Relatório de Cartas de Correção (CC-e)

**Status:** ✅ Concluído (implementado em 28/03/2026)  
**Prioridade:** 🟢 BAIXA  
**Bug relacionado:** Seção 12 (Relatórios Faltantes)  
**Dependência:** Nenhuma  

**Resultado da implementação:**  
- ✅ Método `getCorrectionHistory()` adicionado ao `NfeReportModel` (consulta com JOINs em nfe_correction_history + nfe_documents + users)
- ✅ Método `getCorrectionsByMonth()` para gráfico mensal de CC-e
- ✅ Ação `correctionReport` adicionada ao `NfeDocumentController`
- ✅ View `app/views/nfe/correction_report.php` com KPIs, filtro de período, tabela detalhada e botão exportação
- ✅ Rota `correctionReport` registrada em `app/config/routes.php`
- ✅ Link no dashboard fiscal para acesso rápido ao relatório CC-e
- ✅ Índice `idx_correction_history_created` criado para otimizar consultas por período

**Problema:**  
A tabela `nfe_correction_history` existe e é populada, mas não há relatório dedicado para CC-e.

**Arquivos a alterar:**
- `app/models/NfeReportModel.php` — adicionar método
- `app/controllers/NfeDocumentController.php` — adicionar action
- `app/config/routes.php` — adicionar rota
- `app/views/nfe/` — criar view (opcional: pode ser tab no dashboard)

**Como corrigir — Passo a passo:**

**Passo 1:** Adicionar método no `NfeReportModel`:

```php
/**
 * Retorna histórico de Cartas de Correção num período.
 */
public function getCorrectionHistory(string $start, string $end): array
{
    $sql = "SELECT 
                ch.id, ch.nfe_document_id, ch.seq_evento, ch.texto_correcao,
                ch.protocolo, ch.created_at,
                n.numero, n.serie, n.chave, n.dest_nome
            FROM nfe_correction_history ch
            INNER JOIN nfe_documents n ON ch.nfe_document_id = n.id
            WHERE DATE(ch.created_at) BETWEEN :start AND :end
            ORDER BY ch.created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Passo 2:** Adicionar como tab no dashboard existente ou como relatório separado.

**Critério de aceite:**  
- Listagem de CC-e com período filtrável
- Exibe número da NF-e, sequência do evento, texto da correção, protocolo e data

---

### FASE4-03: Exportação de Relatórios em PDF/Excel

**Status:** ✅ Concluído (implementado em 28/03/2026)  
**Prioridade:** 🟢 BAIXA  
**Bug relacionado:** Seção 12 (Relatórios Faltantes)  
**Dependência:** Nenhuma  

**Resultado da implementação:**  
- ✅ `NfeExportService` criado em `app/services/NfeExportService.php`
- ✅ Método `exportToExcel()` com PhpSpreadsheet — cabeçalhos estilizados, zebra stripes, auto-size, metadados
- ✅ Método `exportToCsv()` como alternativa leve (sem dependência de PhpSpreadsheet)
- ✅ Mapeamento de colunas para labels legíveis em pt-BR
- ✅ Ação `exportReport` no controller com 5 tipos: nfes, taxes, cfop, cancelled, corrections
- ✅ Dropdown de exportação no dashboard fiscal com ícones intuitivos
- ✅ Auditoria de cada exportação registrada (`export_report`)
- ✅ Rota `exportReport` registrada em `app/config/routes.php`

**Problema:**  
Os relatórios fiscais do dashboard existem na tela mas não podem ser exportados.

**Arquivos a criar:**
- `app/services/NfeExportService.php` (novo)

**Como corrigir — Passo a passo:**

**Passo 1:** Criar service de exportação usando `PhpSpreadsheet` (já disponível via `phpoffice/` no vendor):

```php
namespace Akti\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class NfeExportService
{
    /**
     * Exporta dados de relatório NF-e para Excel.
     */
    public function exportToExcel(array $data, string $title): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);

        // Cabeçalhos
        $headers = array_keys($data[0] ?? []);
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Dados
        foreach ($data as $row => $record) {
            $col = 1;
            foreach ($record as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row + 2, $value);
                $col++;
            }
        }

        // Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
```

**Passo 2:** Adicionar botão de exportação nos relatórios do dashboard.

**Passo 3:** Adicionar rota `exportReport` no `routes.php` e método correspondente no controller.

**Critério de aceite:**  
- Botão "Exportar Excel" no dashboard fiscal baixa arquivo .xlsx
- Dados exportados correspondem aos exibidos na tela

---

### FASE4-04: Log de Acesso ao Certificado Digital

**Status:** ✅ Concluído (implementado em 28/03/2026)  
**Prioridade:** 🟢 BAIXA  
**Bug relacionado:** Seção 11 (Segurança)  
**Dependência:** Nenhuma  

**Resultado da implementação:**  
- ✅ `logCredentialsView()` chamado em `NfeCredentialController::index()` (visualização)
- ✅ `logCredentialsUpdate()` chamado em `NfeCredentialController::store()` (atualização, com lista de campos alterados)
- ✅ `logCertificateUpload()` adicionado ao `NfeAuditService` e chamado no upload de certificado
- ✅ `getAuditService()` helper adicionado ao `NfeCredentialController`
- ✅ Índice `idx_audit_entity_type` criado para otimizar consultas de auditoria por tipo de entidade

**Problema:**  
Não há registro de quem acessou, visualizou ou alterou o certificado digital. Isso é uma lacuna de segurança importante.

**Como corrigir:**

Usar o `NfeAuditService` existente para registrar:

```php
// No NfeCredentialController, ao exibir a tela de credenciais:
$auditService->log('credential_view', null, 'Visualizou credenciais SEFAZ');

// Ao fazer upload de novo certificado:
$auditService->log('credential_cert_upload', null, 'Upload de certificado digital');

// Ao atualizar credenciais:
$auditService->log('credential_update', null, 'Atualizou credenciais SEFAZ');
```

**Critério de aceite:**  
- Trilha de auditoria registra quem acessou as credenciais e quando

---

### FASE4-05: Validação de CPF/CNPJ do Destinatário Antes da Emissão

**Status:** ✅ Concluído (implementado em 28/03/2026)  
**Prioridade:** 🟢 BAIXA  
**Bug relacionado:** Seção 11 (Segurança)  
**Dependência:** Nenhuma  

**Resultado da implementação:**  
- ✅ Validação adicionada no início de `NfeXmlBuilder::build()`, ANTES de instanciar `NFePHP\NFe\Make`
- ✅ Usa métodos estáticos `Validator::isValidCpf()` e `Validator::isValidCnpj()` existentes (evita duplicação)
- ✅ CPF com 11 dígitos é validado com verificação de dígitos; CNPJ com 14 dígitos idem
- ✅ Documento vazio é aceito (NFC-e pode não ter destinatário)
- ✅ Documento com tamanho diferente de 11 ou 14 dígitos lança `InvalidArgumentException`
- ✅ Erro é capturado no controller e retornado como JSON controlado (sem comunicação desnecessária com SEFAZ)

**Problema:**  
CPF/CNPJ inválido só é rejeitado pela SEFAZ, gerando rejeição desnecessária. Melhor validar antes de enviar.

**Arquivo a alterar:** `app/services/NfeXmlBuilder.php`

**Como corrigir:**

Adicionar validação no início de `buildNFe()`:

```php
// Validar CPF/CNPJ do destinatário antes de montar XML
$destDoc = preg_replace('/\D/', '', $this->orderData['customer_cpf_cnpj'] ?? '');
if (strlen($destDoc) === 11) {
    if (!self::validateCPF($destDoc)) {
        throw new \InvalidArgumentException('CPF do destinatário inválido: ' . $destDoc);
    }
} elseif (strlen($destDoc) === 14) {
    if (!self::validateCNPJ($destDoc)) {
        throw new \InvalidArgumentException('CNPJ do destinatário inválido: ' . $destDoc);
    }
} else {
    throw new \InvalidArgumentException('Documento do destinatário deve ter 11 (CPF) ou 14 (CNPJ) dígitos.');
}
```

**Critério de aceite:**  
- CPF/CNPJ inválido retorna erro antes de tentar comunicação com SEFAZ
- CPF/CNPJ válidos continuam sendo emitidos normalmente

---

## Fase 5 — Funcionalidades Novas (Semana 5+)

> **Objetivo:** Implementar funcionalidades completamente novas que agregam valor ao módulo.  
> **Pré-requisito:** Fases 1-4 concluídas.

---

### FASE5-01: Emissão NFC-e (Modelo 65)

**Status:** 🔧 Não implementado  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** Seção 8.2  
**Dependência:** Nenhuma  

**Problema:**  
Colunas `serie_nfce`, `proximo_numero_nfce`, `csc_id`, `csc_token` existem no banco, mas:
- `NfeXmlBuilder` está hardcoded para `$ide->mod = 55`
- Não há geração de QR Code
- Não há dados simplificados de destinatário (CPF opcional em NFC-e)

**Escopo da implementação:**

1. **XML diferente:** `mod=65`, sem tag `dest` obrigatória (CPF opcional), sem `transp` detalhado
2. **QR Code:** Gerar URL do QR Code da NFC-e (usando CSC)
3. **Numeração separada:** Usar `proximo_numero_nfce` ao invés de `proximo_numero`
4. **DANFE NFC-e:** Layout simplificado (cupom térmico), não o A4 padrão

**Arquivos a criar/alterar:**
- `app/services/NfeXmlBuilder.php` — condicional `mod=65`
- `app/services/NfceDanfeGenerator.php` (novo) — gerador DANFE NFC-e
- `app/controllers/NfeDocumentController.php` — ação `emitNfce`
- `app/config/routes.php` — nova rota

**Estimativa:** 3-5 dias de desenvolvimento

---

### FASE5-02: Contingência Automática

**Status:** ⏳ Parcial  
**Prioridade:** 🟡 MÉDIA  
**Bug relacionado:** Seção 8.1  
**Dependência:** Nenhuma  

**Problema:**  
Campos `tp_emis` e `contingencia_justificativa` existem, mas não há:
- Endpoint para ativar/desativar contingência
- Lógica de fallback automático quando SEFAZ está offline
- Sincronização posterior (enviar NF-e em contingência quando SEFAZ voltar)

**Escopo da implementação:**

1. **Ativar contingência:** Endpoint que altera `tp_emis` para `6` (SVC-AN) ou `7` (SVC-RS)
2. **Auto-detect:** Se `sefazStatus()` retornar offline, ativar contingência automaticamente
3. **Sincronização:** Job periódico que reenvia NF-e em contingência quando SEFAZ voltar
4. **Alertas:** Banner na UI indicando que o sistema está em contingência

**Estimativa:** 2-3 dias de desenvolvimento

---

### FASE5-03: Download XML em Lote (ZIP)

**Status:** 🔧 Não implementado  
**Prioridade:** 🟢 BAIXA  
**Dependência:** FASE2-04  

**Escopo:**

1. Selecionar múltiplas NF-e na listagem
2. Gerar arquivo ZIP com todos os XMLs
3. Download via streaming (`ZipArchive` + `php://output`)

**Arquivos a criar/alterar:**
- `app/controllers/NfeDocumentController.php` — método `downloadBatch()`
- `app/config/routes.php` — rota `downloadBatch`
- `app/views/nfe/index.php` — botão na seleção em lote

**Estimativa:** 1 dia

---

### FASE5-04: Exportação SPED Fiscal (EFD)

**Status:** 🔧 Não implementado  
**Prioridade:** 🟢 BAIXA  
**Dependência:** FASE1-01, FASE2-01  

**Escopo:**

1. Gerar arquivo TXT no layout SPED Fiscal (EFD-ICMS/IPI)
2. Blocos: 0 (abertura), C (documentos), E (apuração ICMS), H (inventário), 9 (encerramento)
3. Usar biblioteca `sped-efd` se disponível, ou gerar manualmente

**Estimativa:** 5-7 dias (complexo — vários blocos e registros)

---

### FASE5-05: Relatório SINTEGRA

**Status:** 🔧 Não implementado  
**Prioridade:** 🟢 BAIXA  
**Dependência:** FASE1-01  

**Escopo:**
1. Gerar arquivo no formato SINTEGRA (registros 10, 11, 50, 51, 53, 54, 75, 90)
2. Usar dados de `nfe_documents` + `nfe_document_items`

**Estimativa:** 3-4 dias

---

### FASE5-06: Livro de Registro de Saídas

**Status:** 🔧 Não implementado  
**Prioridade:** 🟢 BAIXA  
**Dependência:** FASE1-01  

**Escopo:**
1. Relatório que agrupa NF-e por CFOP e CST
2. Colunas: Número, Data, Destinatário, UF, Valor, BC ICMS, ICMS, IPI, etc.
3. Totais por CFOP e total geral

**Arquivo a alterar:** `app/models/NfeReportModel.php` — novo método `getLivroSaidas()`

**Estimativa:** 2 dias

---

### FASE5-07: Livro de Registro de Entradas

**Status:** 🔧 Não implementado  
**Prioridade:** 🟢 BAIXA  
**Dependência:** Funcionalidade DistDFe já implementada  

**Escopo:**
1. Baseado nos documentos recebidos via DistDFe (`nfe_received_documents`)
2. Formato similar ao Livro de Saídas mas com NF-e de entrada

**Estimativa:** 2 dias

---

### FASE5-08: Backup Automático de XMLs para Storage Externo

**Status:** 🔧 Não implementado  
**Prioridade:** 🟢 BAIXA  
**Bug relacionado:** Seção 11 (Segurança)  
**Dependência:** Nenhuma  

**Escopo:**
1. Job diário que compacta XMLs e envia para storage externo (S3 ou FTP configurável)
2. Configuração no `NfeCredential` (URL destino, credenciais)
3. Log de backup no `nfe_audit_log`

**Estimativa:** 2-3 dias

---

## Cronograma Consolidado

```
Semana 1  ┃ FASE 1: Migration SQL (FASE1-01, FASE1-02)
          ┃ FASE 2: Testes na UI (FASE2-01 a FASE2-05)
          ┃
Semana 2  ┃ FASE 2: Conclusão dos testes
          ┃ FASE 3: Batch tracking (FASE3-01)
          ┃ FASE 3: Multi-filial (FASE3-02)
          ┃
Semana 3  ┃ FASE 3: finNFe dinâmico (FASE3-03)
          ┃ FASE 3: DIFAL no XML (FASE3-04)
          ┃ FASE 3: Retry NF-e (FASE3-05)
          ┃ FASE 3: Inutilização SEFAZ (FASE3-06)
          ┃
Semana 4  ┃ FASE 4: Rate limiting (FASE4-01)
          ┃ FASE 4: Relatório CC-e (FASE4-02)
          ┃ FASE 4: Exportação Excel (FASE4-03)
          ┃ FASE 4: Auditoria certificado (FASE4-04)
          ┃ FASE 4: Validação CPF/CNPJ (FASE4-05)
          ┃
Semana 5+ ┃ FASE 5: NFC-e (FASE5-01) ................. 3-5 dias
          ┃ FASE 5: Contingência (FASE5-02) ........... 2-3 dias
          ┃ FASE 5: Download ZIP (FASE5-03) ........... 1 dia
          ┃ FASE 5: SPED Fiscal (FASE5-04) ............ 5-7 dias
          ┃ FASE 5: SINTEGRA (FASE5-05) ............... 3-4 dias
          ┃ FASE 5: Livro Saídas (FASE5-06) ........... 2 dias
          ┃ FASE 5: Livro Entradas (FASE5-07) ......... 2 dias
          ┃ FASE 5: Backup externo (FASE5-08) ......... 2-3 dias
```

---

## Resumo de Artefatos por Fase

### Migrations SQL a Criar

| Fase | Arquivo | Descrição |
|------|---------|-----------|
| 1 | `sql/prontos/update_202603262000_auditoria_nfe_fixes.sql` | ✅ Já aplicada — colunas snake_case + batch_id |
| 1 | `sql/update_202603271000_add_fiscal_totals_V2.sql` | ✅ Não necessária — colunas já existiam |
| 3 | `sql/update_202603271100_multifilial_defaults_V2.sql` | Default `is_active=1` para id=1 |
| 4 | `sql/update_202603271200_rate_limit_V2.sql` | Tabela de rate limiting |

### Arquivos PHP a Criar

| Fase | Arquivo | Descrição |
|------|---------|-----------|
| 4 | `app/services/NfeExportService.php` | Exportação Excel dos relatórios |
| 5 | `app/services/NfceDanfeGenerator.php` | Gerador DANFE NFC-e |

### Arquivos PHP a Alterar

| Fase | Arquivo | Alteração |
|------|---------|-----------|
| 3 | `app/models/NfeQueue.php` | Métodos `getByBatch()`, `listBatches()` |
| 3 | `app/models/NfeCredential.php` | `get(?int $filialId)`, `listAll()` |
| 3 | `app/services/NfeXmlBuilder.php` | `finNFe` dinâmico, `ICMSUFDest` |
| 3 | `app/services/NfeService.php` | `inutilizar()` com SEFAZ real |
| 3 | `app/controllers/NfeDocumentController.php` | `retry()` |
| 3 | `app/config/routes.php` | Rota `retry` |
| 3 | `app/views/nfe/queue.php` | Coluna/filtro de batch |
| 4 | `app/models/NfeReportModel.php` | `getCorrectionHistory()` |
| 4 | `app/controllers/NfeDocumentController.php` | Rate limiting no `emit()` |
| 4 | `app/controllers/NfeCredentialController.php` | Auditoria de acesso |
| 4 | `app/services/NfeXmlBuilder.php` | Validação CPF/CNPJ |

### Testes a Criar

| Fase | Arquivo | Cobertura |
|------|---------|-----------|
| 2 | `tests/Pages/NfeTest.php` | Smoke test de todas as rotas NF-e (já tem rotas em routes_test.php) |
| 3 | `tests/Unit/NfeQueueBatchTest.php` | `getByBatch()`, `listBatches()` |
| 3 | `tests/Unit/NfeCredentialMultiFilialTest.php` | `get($filialId)`, fallback |
| 4 | `tests/Unit/NfeExportTest.php` | Exportação Excel |

---

## Checklist de Conclusão

- [x] **FASE 1:** Migration SQL aplicada em todos os tenants (validado 27/03/2026)
- [x] **FASE 2:** Emissão testada — código validado + smoke tests + unitários (27/03/2026)
- [x] **FASE 2:** Cancelamento testado — validações de motivo, prazo 24h, auditoria (27/03/2026)
- [x] **FASE 2:** Carta de Correção testada — limite 20 CC-e, histórico, sequência (27/03/2026)
- [x] **FASE 2:** Download XML e DANFE testado — 4 tipos, fallback disco, erros controlados (27/03/2026)
- [x] **FASE 2:** Consulta de status testada — JSON, ID inválido, ID inexistente (27/03/2026)
- [x] **FASE 3:** Batch tracking na fila funcionando
- [x] **FASE 3:** Multi-filial com seletor funcionando
- [x] **FASE 3:** finNFe dinâmico (devolução/complementar)
- [x] **FASE 3:** DIFAL inserido no XML para operações interestaduais
- [x] **FASE 3:** Retry de NF-e rejeitada
- [x] **FASE 3:** Inutilização real com SEFAZ
- [x] **FASE 4:** Rate limiting ativo
- [x] **FASE 4:** Relatório de CC-e disponível
- [x] **FASE 4:** Exportação Excel funcionando
- [x] **FASE 4:** Auditoria de acesso ao certificado
- [x] **FASE 4:** Validação CPF/CNPJ pré-emissão
- [ ] **FASE 5:** NFC-e (modelo 65) — *quando aplicável*
- [ ] **FASE 5:** Contingência automática — *quando aplicável*
- [ ] **FASE 5:** Download XML em lote (ZIP)
- [ ] **FASE 5:** SPED Fiscal — *quando aplicável*
- [ ] **FASE 5:** Livros de Registro

---

## Referências

| Documento | Localização |
|-----------|-------------|
| Auditoria NF-e v2 | `docs/nfe/AUDITORIA_NFE_v2.md` |
| Migration SQL principal | `sql/prontos/update_202603262000_auditoria_nfe_fixes.sql` (✅ aplicada) |
| Migration SQL Fase 3 | `sql/prontos/update_202603271100_fase3_nfe.sql` (✅ aplicada) |
| Migration SQL Fase 4 | `sql/prontos/update_202603281000_fase4_seguranca_relatorios.sql` (✅ aplicada) |
| Testes unitários NF-e | `tests/Unit/NfeDocumentTest.php` (23 testes, 75 assertions) |
| Testes unitários Fase 3 | `tests/Unit/NfeFase3Test.php` |
| Testes unitários Fase 4 | `tests/Unit/NfeFase4Test.php` (42 testes, 78 assertions) |
| Smoke tests NF-e | `tests/Pages/NfeTest.php` (19 testes, cobertura de todas as rotas) |
| Rotas de teste | `tests/routes_test.php` (seção NF-e) |
| TestCase base | `tests/TestCase.php` (assertNoPhpErrors melhorado na Fase 2) |
| Manual do sistema | `docs/MANUAL_DO_SISTEMA.md` |
| Roadmap geral | `docs/ROADMAP.md` |

---

*Documento gerado em 27/03/2026 — Akti Gestão em Produção*
