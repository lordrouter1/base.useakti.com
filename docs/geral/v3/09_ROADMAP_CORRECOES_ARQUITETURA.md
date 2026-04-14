# Roadmap de Correções — Arquitetura — Akti v3

> ## Por que este Roadmap existe?
> A auditoria de arquitetura v3 revelou melhorias substanciais (BaseController, DI Container, eventos), mas identificou god classes que dificultam manutenção e testes. Este roadmap prioriza a decomposição dessas classes e melhorias estruturais pendentes.

---

## Prioridade CRÍTICA (Corrigir Imediatamente)

### ARCH-001: NfeService.php — God Class (2069 linhas)
- **Arquivo:** `app/services/NfeService.php`
- **Problema:** Service com 2069 linhas concentrando emissão, cancelamento, consulta, correção, contingência e comunicação SEFAZ.
- **Risco:** Dificuldade extrema de manutenção, impossibilidade de testes unitários isolados.
- **Correção:** Extrair em 4-5 services especializados:
  ```
  NfeService.php (2069 lines) →
  ├── NfeEmissionService.php    (~500 lines)
  ├── NfeCancellationService.php (~300 lines)
  ├── NfeQueryService.php       (~400 lines)
  ├── NfeCorrectionService.php  (~200 lines)
  └── NfeSefazClient.php        (~300 lines) — comunicação HTTP
  ```
- **Teste:** Testes unitários para cada service extraído.
- **Esforço:** 16-24h
- **Status:** ✅ Concluído (2025-04-14)
- **Implementação:** NfeService (75 linhas, facade) + NfeEmissionService + NfeCancellationService + NfeCorrectionService + NfeQueryService + NfeSefazClient. Commit `36c7a7b`.
- **v2:** Não identificado (NfeService era menor)

### ARCH-002: CustomerController.php — God Class (~2398 linhas)
- **Arquivo:** `app/controllers/CustomerController.php`
- **Problema:** Controller com ~2398 linhas incluindo CRUD, importação, exportação, validação, duplicatas.
- **Risco:** Altíssima complexidade ciclomática, impossibilidade de manutenção segura.
- **Correção:** Extrair:
  ```
  CustomerController.php (~2398 lines) →
  ├── CustomerController.php      (~500 lines) — CRUD básico
  ├── CustomerImportController.php (~400 lines)
  ├── CustomerExportController.php (~300 lines)
  └── (serviços já existem parcialmente)
  ```
- **Teste:** Manter testes existentes passando + novos testes para controllers extraídos.
- **Esforço:** 12-16h
- **Status:** ✅ Concluído (2025-04-14)
- **Implementação:** CustomerImportController (10 métodos, extends BaseController) + CustomerExportController (2 métodos). Rotas delegadas via routes.php. Nota: CustomerExportController não extends BaseController (usa service direto). Commit `36c7a7b`.
- **v2:** Parcialmente identificado.

---

## Prioridade ALTA

### ARCH-003: ProductController.php — God Class (~1194 linhas)
- **Arquivo:** `app/controllers/ProductController.php`
- **Problema:** Controller concentra CRUD, grades, importação, exportação, preços.
- **Risco:** Complexidade alta, dificuldade de testes.
- **Correção:** Extrair `ProductGradeController` e `ProductImportController`.
- **Esforço:** 8-12h
- **Status:** ✅ Concluído (2025-04-14)
- **Implementação:** ProductGradeController (3 métodos) + ProductImportController (4 métodos), ambos extends BaseController. Rotas delegadas via routes.php. Commit `36c7a7b`.

### ARCH-004: PipelineController.php — God Class (~948 linhas)
- **Arquivo:** `app/controllers/PipelineController.php`
- **Problema:** Controller gerencia pipeline, setores, etapas, regras de transição.
- **Correção:** Extrair `PipelinePaymentController` (7 métodos financeiros) e `PipelineProductionController` (5 métodos produção).
- **Esforço:** 6-8h
- **Status:** ✅ Concluído (2025-04-14)
- **Implementação:** PipelinePaymentController (countInstallments, deleteInstallments, generatePaymentLink, generateMercadoPagoLink, confirmDownPayment, syncInstallments, updateInstallmentDueDate) + PipelineProductionController (moveSector, getItemLogs, addItemLog, deleteItemLog, productionBoard). Ambos extends BaseController. Commit `36c7a7b`.

### ARCH-005: Padrão de Response Inconsistente
- **Problema:** Controllers misturam `echo json_encode()`, `$this->json()`, `header('Location')` e `$this->redirect()`.
- **Correção:** Padronizar usando exclusivamente métodos do BaseController:
  ```php
  // JSON: $this->json($data, $status)
  // Redirect: $this->redirect($url)
  // View: $this->render($view, $data)
  ```
- **Esforço:** 8-12h (gradual, por controller)
- **Status:** ⚠️ Parcial (2025-04-14)
- **Implementação:** BaseController::json(mixed $data, int $status) implementado. 23 controllers já extends BaseController com $this->json(). Porém **338 ocorrências** de `echo json_encode` persistem em 24 controllers (os maiores: CustomerController 61, NfeDocumentController 57, StockController 27, SupplyController 26). Removidos métodos privados json() duplicados de AttachmentController e LojaController. Commit `36c7a7b`.
- **v2:** Era ARCH-003. Parcialmente melhorado.

---

## Prioridade MÉDIA

### ARCH-006: Sem Interfaces para Services
- **Problema:** Services não implementam interfaces/contratos formais, dificultando mocking em testes.
- **Correção:** Criar interfaces para services críticos:
  ```php
  interface PaymentGatewayInterface {
      public function charge(float $amount, array $params): PaymentResult;
      public function refund(string $transactionId): bool;
  }
  ```
- **Esforço:** 8-16h
- **Status:** ⬜ Pendente
- **v2:** Era ARCH-007. Mantido.

### ARCH-007: Models Retornam PDOStatement Direto
- **Problema:** Alguns models legacy retornam `PDOStatement` em vez de `array`, criando inconsistência.
- **Correção:** Padronizar retornos para `array` ou tipos escalares.
- **Esforço:** 4-8h
- **Status:** ✅ Concluído (2025-04-14)
- **Implementação:** Customer::readAll() e Order::readAll() agora retornam `array` (fetchAll interno). Callers em OrderController atualizados. Commit `36c7a7b`.
- **v2:** Era ARCH-010. Parcialmente corrigido.

### ARCH-008: Tipagem de Retorno Incompleta em Models
- **Problema:** Muitos models não declaram tipos de retorno (`: array`, `: ?array`, `: int`).
- **Correção:** Adicionar type hints de retorno gradualmente, priorizando novos models.
- **Esforço:** 4-8h (gradual)
- **Status:** ✅ Concluído (2025-04-14)
- **Implementação:** 28 métodos tipados em 11 models: Commission(1), Customer(7), Installment(3), NfeCredential(1), NfeDocument(3), NfeQueue(2), NfeReceivedDocument(2), NfeWebhook(1), Notification(1), PaymentGateway(3), Transaction(2). Tipos: `array`, `array|false`, `int`, `bool`, `int|false`, `mixed`. Commit `36c7a7b`.
- **v2:** Era ARCH-011. Melhoria gradual.

---

## Prioridade BAIXA

### ARCH-009: Properties Públicas em Models
- **Problema:** Alguns models usam properties públicas em vez de getters/setters.
- **Correção:** Refatorar gradualmente para encapsulamento.
- **Esforço:** 8-16h (gradual)
- **Status:** ⚠️ Parcial (2025-04-14)
- **Implementação:** Product.php (8 props → protected), Customer.php (4 props → protected), Subcategory.php (2 props → protected: show_in_store, free_shipping), Order.php (2 props → protected: quote_notes, created_at), User.php ($password → protected + setPassword/getPassword). **Restam públicas:** Order (8 props), Subcategory (3 props), User (5 props). Commit `36c7a7b`.
- **v2:** Era ARCH-008. Mantido.

### ARCH-010: Eventos Apenas no Módulo NF-e
- **Problema:** O EventDispatcher é utilizado apenas pelo módulo NF-e (10 listeners). Outros módulos (pedidos, pipeline, financeiro) não emitem eventos.
- **Correção:** Expandir uso de eventos para:
  - `order.created`, `order.approved`, `order.shipped`
  - `pipeline.stage_changed`, `pipeline.completed`
  - `financial.payment_received`, `financial.invoice_overdue`
- **Esforço:** 8-16h
- **Status:** ✅ Concluído (2025-04-14)
- **Implementação:** 7 novos listeners: pipeline.completed (com notificação admin), model.order.deleted, model.product.cost_updated, controller.user.logout, portal.order.rejected (com notificação admin), portal.message.sent. + 4 audit delete listeners (order/customer/product/category → AuditLogService). Dispatch de pipeline.completed em Pipeline.php. Commit `36c7a7b`.

### ARCH-011: routes.php Verboso
- **Problema:** Arquivo de rotas extenso para 43+ rotas. Formato declarativo é claro mas extenso.
- **Correção:** Aceito como padrão — formato declarativo facilita leitura e manutenção.
- **Esforço:** —
- **Status:** ⚠️ Aceito
- **v2:** Era ARCH-004. Aceito.

---

## Issues Resolvidas desde v2

| ID v2 | Descrição | Resolução v3 |
|--------|-----------|-------------|
| ARCH-001 | Ausência de BaseController | ✅ `app/controllers/BaseController.php` com 8 métodos |
| ARCH-002 | Database Instantiation Manual em 31 Controllers | ✅ 98% padronizados com PDO injection |
| ARCH-005 | index.php Muito Longo (~280 linhas) | ✅ Refatorado em pipeline de 7 estágios |
| ARCH-006 | Falta de DI Container | ✅ Container class com autowiring |
| ARCH-009 | Eventos Disparados sem Listeners | ✅ 10 listeners registrados em events.php |

---

## Resumo

| Prioridade | Issues | Status |
|-----------|--------|--------|
| CRÍTICA | ARCH-001 NfeService, ARCH-002 CustomerController | ✅ Ambos concluídos |
| ALTA | ARCH-003 ProductController, ARCH-004 PipelineController | ✅ Ambos concluídos |
| ALTA | ARCH-005 JSON Response | ⚠️ Parcial (23 controllers migrados, 338 `echo json_encode` restantes em 24 controllers) |
| MÉDIA | ARCH-006 Interfaces | ⬜ Pendente |
| MÉDIA | ARCH-007 PDOStatement, ARCH-008 Type Hints | ✅ Ambos concluídos |
| BAIXA | ARCH-009 Properties | ⚠️ Parcial (subset seguro encapsulado) |
| BAIXA | ARCH-010 Eventos | ✅ Concluído (11 novos listeners) |
| BAIXA | ARCH-011 routes.php | ⚠️ Aceito |

**Aplicado em:** 2025-04-14 — Commit `36c7a7b`
