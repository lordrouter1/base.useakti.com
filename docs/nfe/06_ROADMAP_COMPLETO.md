# ROADMAP COMPLETO — Módulo NF-e / NFC-e

**Data:** 2026-03-26  
**Sistema:** Akti - Gestão em Produção  
**Objetivo:** Transformar o módulo fiscal de **Nível 1 (Básico)** para **Nível 3 (Profissional)**, com emissão robusta, UX moderna e minimalista, integração de eventos e relatórios fiscais, atingindo padrão de ERP profissional.

**Documentos de referência:**
- `01_AUDITORIA_COMPLETA.md` — 48+ problemas identificados (13 críticos)
- `02_SUGESTOES_MELHORIAS.md` — Soluções técnicas detalhadas
- `03_MAPEAMENTO_FUNCOES.md` — Referência de todas as funções
- `04_CONFIGURACOES_INTEGRACOES.md` — Configurações e integrações
- `05_COMPARATIVO_ERP.md` — Benchmark com ERPs profissionais

---

## Sumário Executivo

| Indicador | Valor Atual | Objetivo |
|-----------|-------------|----------|
| Nível de Maturidade | 3 (Profissional — Fases 1+2+3+4 concluídas) ✅ | 3 (Profissional) |
| Issues Críticas | 0 ✅ | 0 |
| Issues Altas | 0 ✅ | 0 |
| Funcionalidades faltantes | ~0 (Fase 4 completa) | Todas implementadas ✅ |
| Cobertura NFC-e | 100% ✅ | 100% |
| Integração Relatórios | 100% ✅ | 100% |
| Segurança (CSRF/Permissões) | ✅ | ✅ |
| Estimativa restante | — | Fases 5-6 opcionais (funcionalidades avançadas + UX) |

---

## FASE 1 — FUNDAÇÃO E SEGURANÇA ⚡
**Duração estimada:** 1-2 semanas  
**Prioridade:** 🔴 BLOQUEANTE — Nada deve ir para produção antes desta fase.  
**Objetivo:** Corrigir todas as vulnerabilidades críticas e garantir infraestrutura mínima.

---

### 1.1 Instalar dependências obrigatórias (P0.1)
**Impacto:** Sem as bibliotecas, 0% da emissão funciona.

**Ações:**
- Adicionar `nfephp-org/sped-nfe: ^5.0` ao `composer.json`
- Adicionar `nfephp-org/sped-da: ^4.0` ao `composer.json`
- Executar `composer update`
- Verificar que `NfeService::isLibraryAvailable()` retorna `true`

**Teste de validação:** Acessar `?page=nfe_documents`, clicar "Testar Conexão SEFAZ" e obter status de sucesso.

**Estimativa:** 1 hora

---

### 1.2 Criar migrations SQL para tabelas NF-e (P0.2 / DB1)
**Impacto:** Viola regra fundamental do projeto (toda alteração de banco precisa de SQL em `/sql`).

**Ações — Criar arquivo `sql/update_202603261200_nfe_tables.sql`:**

Tabelas a criar:
1. **`nfe_credentials`** — Credenciais e dados do emitente (CNPJ, certificado, ambiente, série, numeração, CSC)
2. **`nfe_documents`** — Documentos fiscais emitidos (NF-e/NFC-e) com todos os campos para emissão, autorização, cancelamento, CC-e, contingência
3. **`nfe_logs`** — Logs de comunicação com a SEFAZ (ação, status, XMLs de request/response, usuário, IP)
4. **`nfe_document_items`** (**NOVA**) — Itens da NF-e armazenados separadamente para relatórios fiscais (NCM, CFOP, CEST, alíquotas, valores de ICMS/PIS/COFINS/IPI)
5. **`nfe_correction_history`** (**NOVA**) — Histórico de Cartas de Correção (CC-e) com sequência, texto, protocolo, XML

**Índices obrigatórios:**
- `nfe_documents`: `order_id`, `chave`, `status`, `numero+serie`, `modelo`, `emitted_at`
- `nfe_logs`: `nfe_document_id`, `order_id`, `action`, `created_at`
- `nfe_document_items`: `nfe_document_id`, `ncm`, `cfop`

**Foreign keys:**
- `nfe_documents.order_id` → `orders.id` (`ON DELETE SET NULL`)
- `nfe_logs.nfe_document_id` → `nfe_documents.id` (`ON DELETE CASCADE`)
- `nfe_document_items.nfe_document_id` → `nfe_documents.id` (`ON DELETE CASCADE`)
- `nfe_correction_history.nfe_document_id` → `nfe_documents.id` (`ON DELETE CASCADE`)

**Campo `modelo`** na `nfe_documents`: `TINYINT DEFAULT 55` (55=NF-e, 65=NFC-e) — resolve DB4 e M6.

**Estimativa:** 1 dia

---

### 1.3 Proteção CSRF em todos os endpoints POST (P0.3 / S1)
**Impacto:** Vulnerabilidade crítica — qualquer site pode forjar requisições.

**Ações:**
- Adicionar `\Akti\Middleware\CsrfMiddleware::validate()` no início de:
  - `NfeDocumentController::emit()`
  - `NfeDocumentController::cancel()`
  - `NfeDocumentController::correction()`
  - `NfeCredentialController::store()`
- Adicionar token CSRF nos formulários HTML das views (`credentials.php`)
- Adicionar header `X-CSRF-Token` em todas as chamadas AJAX do módulo NF-e
- Incluir `<meta name="csrf-token">` no `header.php` (se ainda não existir)

**Teste de validação:** Requisição POST sem token deve retornar erro 403.

**Estimativa:** 4 horas

---

### 1.4 Verificação de permissões nos controllers (P0.4 / S2)
**Impacto:** Qualquer usuário logado pode emitir/cancelar NF-e sem restrição.

**Ações:**
- No construtor de `NfeDocumentController`: verificar `checkPermission('nfe_documents', 'view')`
- Nos métodos de escrita (`emit`, `cancel`, `correction`): verificar `checkPermission('nfe_documents', 'edit')`
- No construtor de `NfeCredentialController`: verificar `checkPermission('nfe_credentials', 'view')`
- Em `NfeCredentialController::store()`: verificar `checkPermission('nfe_credentials', 'edit')`
- Adicionar `nfe_documents` e `nfe_credentials` ao array `$pages` em `app/views/users/groups.php`

**Estimativa:** 4 horas

---

### 1.5 Implementar retry com backoff na consulta de recibo (P0.5 / E1+E2)
**Impacto:** `sleep(3)` fixo bloqueia a thread PHP; se SEFAZ retornar cStat 105 ("em processamento"), o sistema falha.

**Ações:**
- Substituir `sleep(3)` por loop com backoff exponencial
- Intervalos: `[3, 5, 10, 15, 30]` segundos (5 tentativas máximas)
- Se cStat `104` (processado): sair e processar
- Se cStat `105` (em processamento): retry
- Se outro cStat: sair e reportar erro
- Registrar cada tentativa no `NfeLog`

**Estimativa:** 4 horas

---

### 1.6 Corrigir chave de criptografia do certificado (P0.6 / S4 / CR1)
**Impacto:** A chave AES-256-CBC é derivada de `sha256(db_name + salt)` — qualquer dev descriptografa.

**Ações:**
- Criar suporte a variável de ambiente `APP_KEY` (prioridade: `getenv()` → arquivo `.env` → fallback atual com warning)
- Implementar `getEncryptionKey()` com prioridade em APP_KEY
- Criar script de re-criptografia para migrar senhas existentes para nova chave
- Documentar a necessidade de definir `APP_KEY` em produção

**Estimativa:** 4 horas

---

### 1.7 Verificar prazo de cancelamento (P1.8 / C1)
**Impacto:** Sem verificação, o usuário tenta cancelar fora do prazo e recebe erro genérico da SEFAZ.

**Ações:**
- Antes de enviar cancelamento à SEFAZ, calcular diferença em horas entre `emitted_at` e `now()`
- Limite padrão: 24 horas (configurável por UF futuramente)
- Se prazo excedido: retornar mensagem clara ao usuário com o tempo decorrido
- Adicionar alerta visual na view de detalhe quando o prazo estiver próximo de expirar

**Estimativa:** 2 horas

---

### 1.8 Corrigir geração do cNF com random_int() (X5)
**Impacto:** `rand()` não é criptograficamente seguro.

**Ações:**
- Substituir `rand(10000000, 99999999)` por `random_int(10000000, 99999999)` no `NfeXmlBuilder`

**Estimativa:** 15 minutos

---

### ✅ Critérios de conclusão da Fase 1
- [x] `composer.json` com sped-nfe e sped-da
- [x] Arquivo SQL de migração em `/sql`
- [x] Todas as 5 tabelas criadas com índices e FKs
- [x] CSRF validado em todos os POSTs
- [x] Permissões verificadas em ambos os controllers
- [x] Retry com backoff implementado
- [x] Chave de criptografia usando APP_KEY
- [x] Prazo de cancelamento verificado
- [x] cNF usando random_int()

**Concluído em:** 2026-03-26

---

## FASE 2 — EMISSÃO ROBUSTA E CONFORMIDADE FISCAL 📋
**Duração estimada:** 3-4 semanas  
**Prioridade:** 🟡 ALTA — Necessário para emissão confiável.  
**Objetivo:** Tornar a emissão fiscalmente correta para todos os regimes tributários.

---

### 2.1 Implementar TaxCalculator — Cálculo dinâmico de impostos (P1.1 / X1+X2)
**Impacto:** Atualmente, impostos são fixos para Simples Nacional CSOSN 102 com PIS/COFINS zerados. Empresas de Lucro Presumido/Real não podem usar o sistema.

**Ações — Criar `app/services/TaxCalculator.php`:**

| Método | Descrição |
|--------|-----------|
| `calculateICMS($product, $operation, $crt, $ufOrig, $ufDest)` | CRT 1/2 → CSOSN; CRT 3 → CST |
| `calculatePIS($product, $regime)` | Cumulativo 0.65%; Não-cumulativo 1.65% |
| `calculateCOFINS($product, $regime)` | Cumulativo 3.00%; Não-cumulativo 7.60% |
| `calculateIPI($product, $operation)` | Por NCM e tipo de operação |
| `calculateDIFAL($product, $ufOrig, $ufDest)` | Diferencial de alíquota interestadual |
| `calculateTotal($items)` | Totalização de impostos |

**Regimes suportados:**
- CRT 1 — Simples Nacional (CSOSN: 101, 102, 103, 201, 202, 203, 300, 400, 500, 900)
- CRT 2 — Simples Nacional Excedente
- CRT 3 — Regime Normal (CST: 00, 10, 20, 30, 40, 41, 50, 51, 60, 70, 90)

**Integração:**
- `NfeXmlBuilder::build()` deve usar `TaxCalculator` ao montar tags `imposto[]`
- Alíquotas e CSTs devem vir do cadastro de produtos (não hardcoded)

**Estimativa:** 1 semana

---

### 2.2 Campos fiscais obrigatórios no cadastro de produtos (P1.6 / X3+X4+X11)
**Impacto:** NCM com fallback `'00000000'` causa rejeição; CFOP fixo `'5102'` impede operações diversas.

**Ações — Criar SQL `sql/update_YYYYMMDD_product_fiscal_fields.sql`:**

| Campo | Tipo | Obrigatório | Default |
|-------|------|-------------|---------|
| `ncm` | VARCHAR(8) | ✅ Sim | — |
| `cest` | VARCHAR(7) | Não | — |
| `cfop_venda_interna` | VARCHAR(4) | Não | `5102` |
| `cfop_venda_interestadual` | VARCHAR(4) | Não | `6102` |
| `icms_cst` | VARCHAR(3) | Não | — |
| `icms_aliquota` | DECIMAL(5,2) | Não | `0.00` |
| `icms_reducao_bc` | DECIMAL(5,2) | Não | `0.00` |
| `pis_cst` | VARCHAR(2) | Não | — |
| `cofins_cst` | VARCHAR(2) | Não | — |
| `ipi_cst` | VARCHAR(2) | Não | — |
| `ipi_aliquota` | DECIMAL(5,2) | Não | `0.00` |
| `origem` | TINYINT | Não | `0` (Nacional) |

**Na View de produtos:**
- Adicionar aba/seção "Dados Fiscais" no formulário de cadastro/edição
- Autocompletar NCM com busca em tabela de referência
- Validar NCM (8 dígitos numéricos) no front e back

**Na emissão:**
- Rejeitar emissão se algum item tiver NCM vazio ou inválido
- Usar CFOP do produto conforme operação interna/interestadual

**Estimativa:** 3-5 dias

---

### 2.3 Calcular idDest dinamicamente (P1.7 / X6)
**Impacto:** `idDest=1` fixo em "operação interna" — operações interestaduais e exportação são incorretas.

**Ações no `NfeXmlBuilder`:**
- Comparar `uf_emitente` com `uf_destinatario`
- Se iguais: `idDest = 1` (interna)
- Se diferentes e ≠ 'EX': `idDest = 2` (interestadual)
- Se 'EX': `idDest = 3` (exportação)

**Estimativa:** 1 hora

---

### 2.4 Validar XML contra schema XSD antes do envio (P1.5 / E6)
**Impacto:** Sem validação, XMLs malformados são enviados à SEFAZ gerando rejeições evitáveis.

**Ações:**
- Após assinar o XML e antes de enviar o lote, validar com `DOMDocument::schemaValidate()`
- Usar schemas XSD do pacote sped-nfe (`vendor/nfephp-org/sped-nfe/schemas/`)
- Se inválido: abortar envio, registrar log com detalhes da validação, retornar erro claro ao usuário
- Marcar documento como `rascunho` (não `rejeitada`) para permitir correção e reenvio

**Estimativa:** 4 horas

---

### 2.5 Salvar XMLs em disco além do banco (P2.1 / E10)
**Impacto:** Legislação exige guarda dos XMLs por 5 anos. Armazenar apenas no banco é frágil.

**Ações:**
- Criar estrutura: `storage/nfe/{tenant_db}/{YYYY}/{MM}/`
- Salvar arquivos: `{chave}-nfe.xml`, `{chave}-danfe.pdf`, `{chave}-cancel.xml`, `{chave}-cce.xml`
- Registrar path no campo `danfe_path` e em novo campo `xml_path` (adicionar ao SQL de migração)
- Proteger diretório `storage/nfe/` via `.htaccess` (deny from all)

**Estimativa:** 1 dia

---

### 2.6 Geração automática de DANFE via evento (P2.2 / E9)
**Impacto:** Atualmente o DANFE só é gerado sob demanda (download manual).

**Ações:**
- No listener de `model.nfe_document.authorized` em `events.php`:
  1. Carregar XML autorizado do documento
  2. Gerar DANFE via `NfePdfGenerator::generate()`
  3. Salvar em `storage/nfe/{tenant}/{YYYY}/{MM}/{chave}-danfe.pdf`
  4. Atualizar `danfe_path` no documento

**Estimativa:** 4 horas

---

### 2.7 Adicionar tag infRespTec (P2.5 / X10)
**Impacto:** Recomendado pela SEFAZ para identificar o desenvolvedor do software emissor.

**Ações:**
- Adicionar no `NfeXmlBuilder::build()` após `infAdic`:
  - CNPJ, contato, e-mail e telefone do desenvolvedor (Akti)
- Valores configuráveis via variáveis de ambiente ou `company_settings`

**Estimativa:** 1 hora

---

### 2.8 Limite de 20 Cartas de Correção + Histórico (P2.7+P2.8 / CC1+CC2)
**Impacto:** SEFAZ permite no máximo 20 CC-e; atualmente sem limite e sem histórico.

**Ações:**
- Antes de enviar CC-e, verificar se `correcao_seq >= 20` → retornar erro
- Ao registrar CC-e, inserir na tabela `nfe_correction_history` (nova, criada na Fase 1)
- Manter apenas a última CC-e no campo `xml_correcao` do documento principal
- Na view de detalhe, exibir timeline de todas as CC-e enviadas

**Estimativa:** 4 horas

---

### 2.9 Implementar tag cobr — Fatura/Duplicata (P2.4 / X9)
**Impacto:** Vendas a prazo sem informação de fatura/duplicata na NF-e.

**Ações:**
- No `NfeXmlBuilder::build()`, se o pedido tiver parcelas/duplicatas:
  - Montar tag `fat` (fatura) com número, valor original, desconto, valor líquido
  - Montar tags `dup` (duplicatas) com número sequencial, vencimento e valor
- Dados de parcelas devem vir do pedido ou do módulo financeiro

**Estimativa:** 4 horas

---

### 2.10 Configurar modFrete e indPres dinamicamente (X7+X8)
**Impacto:** Fixos em "sem frete" e "presencial" — inadequado para vendas online ou com frete.

**Ações:**
- `modFrete`: obter do pedido (campo `shipping_type` ou equivalente) — mapear para códigos NF-e (0-9)
- `indPres`: obter do tipo de venda — `1` (presencial), `2` (internet), `4` (telemarketing), `9` (outros)
- Defaults configuráveis em `company_settings`

**Estimativa:** 2 horas

---

### 2.11 Resolver transações aninhadas (E7 / M5)
**Impacto:** `NfeService::emit()` abre transação e chama `markAuthorized()` que abre outra.

**Ações:**
- Implementar verificação `$db->inTransaction()` antes de `beginTransaction()`
- Ou refatorar para que `markAuthorized()` aceite flag `$useTransaction = true`
- Garantir que commit/rollback funcione corretamente em ambos os cenários

**Estimativa:** 2 horas

---

### ✅ Critérios de conclusão da Fase 2
- [x] TaxCalculator implementado com suporte a CRT 1, 2 e 3 (`app/services/TaxCalculator.php`)
- [x] Campos fiscais no cadastro de produtos com validação (`_fiscal_partial.php` + SQL migration)
- [x] NCM obrigatório; emissão rejeitada se ausente (`NfeDocumentController::emit()` + `NfeXmlBuilder`)
- [x] idDest calculado dinamicamente (`TaxCalculator::calculateIdDest()`)
- [x] XML validado contra XSD antes de enviar (`NfeXmlValidator.php`)
- [x] XMLs salvos em disco + banco (`NfeStorageService.php` + `NfeService`)
- [x] DANFE gerado automaticamente via evento (`events.php` → `model.nfe_document.authorized`)
- [x] Tag infRespTec presente (`NfeXmlBuilder::buildInfRespTec()`)
- [x] Limite de 20 CC-e com histórico (`NfeService::correction()` + `nfe_correction_history`)
- [x] Tag cobr para vendas a prazo (`NfeXmlBuilder::buildCobr()`)
- [x] modFrete e indPres dinâmicos (`TaxCalculator::mapModFrete()` + `mapIndPres()`)
- [x] Transações sem conflito (verificação `inTransaction()` + refatoração)
- [x] Controller enriquece itens com dados fiscais dos produtos
- [x] Controller carrega parcelas financeiras para tag cobr
- [x] Totais fiscais persistidos no documento (`saveFiscalTotals()`)
- [x] Itens fiscais persistidos em `nfe_document_items` (`saveDocumentItems()`)

**Concluído em:** 2026-03-27  
**Arquivos criados/modificados:**
- `sql/update_202603271200_fase2_fiscal.sql` — Migration de campos fiscais
- `app/services/TaxCalculator.php` — Cálculo dinâmico de impostos
- `app/services/NfeXmlValidator.php` — Validação XSD
- `app/services/NfeStorageService.php` — Armazenamento em disco
- `app/services/NfeXmlBuilder.php` — XML compliant Fase 2
- `app/services/NfeService.php` — Integração completa
- `app/controllers/NfeDocumentController.php` — Dados fiscais enriquecidos
- `app/models/NfeDocument.php` — markAuthorized com xmlPath
- `app/bootstrap/events.php` — DANFE automático via evento

---

## FASE 3 — NFC-e, INUTILIZAÇÃO E CONTINGÊNCIA 🏗️
**Duração estimada:** 2-3 semanas  
**Prioridade:** 🟡 ALTA — Funcionalidades obrigatórias para módulo fiscal completo.  
**Objetivo:** Completar o escopo funcional do módulo fiscal conforme exigências legais.

---

### 3.1 Implementar NFC-e — Modelo 65 (P1.2)
**Impacto:** NFC-e totalmente ausente apesar da UI sugerir suporte.

**Ações:**

**3.1.1 Backend — NfeService:**
- `initTools()` aceitar parâmetro `$modelo` (55 ou 65)
- `emit()` aceitar `$modelo` como parâmetro ou detectar do pedido
- Séries e numeração separados: `serie_nfce`, `proximo_numero_nfce`

**3.1.2 NfeXmlBuilder — Adaptações para modelo 65:**
- `mod = 65`
- `tpImp = 4` (DANFE NFC-e)
- `idDest = 1` (sempre operação interna)
- `indFinal = 1` (sempre consumidor final)
- `indPres = 1` (presencial) ou `4` (entrega em domicílio)
- Tag `dest` opcional (apenas se CPF informado)
- Gerar QR Code usando CSC ID e CSC Token
- Tag `infNFeSupl` com URL do QR Code e URL de consulta

**3.1.3 Novo serviço — NfceQrCodeGenerator:**
- Calcular hash do QR Code conforme Manual NFC-e
- Gerar URL completa com parâmetros: chave, versão QR, ambiente, CSC
- Validar CSC ID e Token configurados

**3.1.4 NfePdfGenerator — DANFE NFC-e (formato cupom):**
- Layout em formato cupom térmico (58mm ou 80mm)
- Dados da empresa no topo
- Lista de itens com quantidade, descrição e valor
- Total e forma de pagamento
- QR Code centralizado
- Chave de acesso e protocolo no rodapé

**3.1.5 View:**
- Botão/ação de emissão NFC-e distinto da NF-e
- Filtro por modelo (55/65) na listagem
- Download de DANFE-NFC-e em formato adequado

**Estimativa:** 1 semana

---

### 3.2 Implementar Inutilização de Numeração (P1.3 / E8)
**Impacto:** Números reservados e não utilizados precisam ser inutilizados junto à SEFAZ (obrigação legal).

**Ações:**

**3.2.1 Serviço — `NfeService::inutilizar()`:**
- Parâmetros: `$numInicial`, `$numFinal`, `$justificativa`, `$modelo = 55`
- Chamar `$this->tools->sefazInutiliza($serie, $numInicial, $numFinal, $justificativa)`
- Validar que justificativa tenha mínimo 15 caracteres
- Registrar resposta no `NfeLog`
- Criar registro em `nfe_documents` com status `inutilizada`

**3.2.2 Controller — `NfeDocumentController::inutilizar()`:**
- Tela com formulário: número inicial, número final, justificativa, modelo
- Validação CSRF e permissões
- Retorno JSON para AJAX

**3.2.3 Rota:**
- Adicionar `'inutilizar' => 'inutilizar'` no array de actions de `nfe_documents`

**3.2.4 View:**
- Modal ou seção dedicada na view de NF-e para inutilização
- Confirmar ação com aviso de irreversibilidade
- Exibir resultado da SEFAZ

**Estimativa:** 2-3 dias

---

### 3.3 Implementar Emissão em Contingência (P1.4 / E3)
**Impacto:** Se a SEFAZ estiver offline, a empresa não pode emitir notas.

**Ações:**

**3.3.1 Tipos de contingência a implementar:**

| Tipo | tpEmis | Uso |
|------|--------|-----|
| Normal | 1 | SEFAZ online (padrão) |
| SVC-AN | 6 | SEFAZ offline, contingência virtual (AN) |
| SVC-RS | 7 | SEFAZ offline, contingência virtual (RS) |
| Contingência off-line NFC-e | 9 | NFC-e em modo offline |

**3.3.2 NfeService — novos métodos:**
- `setContingency($tpEmis, $justificativa)`: ativa contingência via `tools->contingency->activate()`
- `deactivateContingency()`: desativa contingência
- `isInContingency()`: verifica estado atual

**3.3.3 Detecção automática:**
- Se `sefazEnviaLote()` falhar por timeout/conexão, sugerir ativação de contingência
- Alerta visual no painel de NF-e quando em modo contingência
- Ao desativar contingência, reprocessar notas emitidas em contingência

**3.3.4 Armazenamento:**
- Salvar `tp_emis` e `contingencia_justificativa` na `nfe_documents`
- Atualizar `tp_emis` nas credenciais ao ativar/desativar

**Estimativa:** 3-5 dias

---

### 3.4 Preview de NF-e antes da emissão (P3.4)
**Impacto:** Usuário emite NF-e sem poder revisar dados antes — erros só são descobertos após envio.

**Ações:**
- Criar action `preview` no controller que monta os dados e gera o XML SEM enviar
- Exibir em modal os dados:
  - Emitente, destinatário, itens com valores
  - Impostos calculados pelo TaxCalculator
  - Total de impostos, valor total, forma de pagamento
  - Alertas visuais para campos incompletos ou suspeitos
- Botão "Confirmar e Emitir" que dispara o envio real
- Botão "Cancelar / Voltar" para correções

**Estimativa:** 2-3 dias

---

### ✅ Critérios de conclusão da Fase 3
- [x] NFC-e (modelo 65) emitindo e autorizando
- [x] DANFE-NFC-e com QR Code sendo gerado
- [x] CSC configurado e validado
- [x] Inutilização funcional com tela dedicada
- [x] Contingência SVC-AN/SVC-RS implementada
- [x] Detecção automática de necessidade de contingência
- [x] Preview de NF-e funcional antes da emissão

**Concluído em:** 2026-03-28
**Arquivos criados/modificados:**
- `app/services/NfceQrCodeGenerator.php` — Gerador de QR Code NFC-e
- `app/services/NfeService.php` — Inutilização, contingência, NFC-e (modelo 65)
- `app/services/NfeXmlBuilder.php` — Adaptações modelo 65, QR Code, infNFeSupl
- `app/services/NfePdfGenerator.php` — DANFE-NFC-e formato cupom
- `app/controllers/NfeDocumentController.php` — Actions inutilizar, preview, NFC-e
- `app/models/NfeDocument.php` — Campos contingência, modelo 65
- `app/models/NfeCredential.php` — CSC, séries NFC-e
- `app/views/nfe/index.php` — Filtro por modelo, botões NFC-e
- `app/views/nfe/detail.php` — Preview, inutilização modal
- `app/config/routes.php` — Novas actions (inutilizar, preview)

---

## FASE 4 — EVENTOS, RELATÓRIOS E INTEGRAÇÕES 📊
**Duração estimada:** 3-4 semanas  
**Prioridade:** 🟡 MÉDIA-ALTA — Diferencial competitivo e conformidade.  
**Objetivo:** Integrar o módulo fiscal ao sistema de relatórios e eventos do Akti, criar relatórios fiscais completos e automatizar fluxos.

---

### 4.1 Relatórios Fiscais — Integração com módulo de Relatórios

O sistema já possui um módulo robusto de relatórios (`ReportController`) com suporte a PDF (TCPDF) e Excel (PhpSpreadsheet), design profissional com paleta de cores, cabeçalho com logo e rodapé com data/usuário. Os relatórios fiscais devem seguir **exatamente** esse mesmo padrão.

**4.1.1 Novos relatórios a implementar:**

| Relatório | Tipo | Filtros | Formato |
|-----------|------|---------|---------|
| NF-e por Período | Fiscal | data_início, data_fim, status, modelo | PDF + Excel |
| Impostos por Período | Fiscal | data_início, data_fim, regime | PDF + Excel |
| NF-e por Cliente | Fiscal | data_início, data_fim, cliente | PDF + Excel |
| Resumo CFOP | Fiscal | data_início, data_fim | PDF + Excel |
| NF-e Canceladas | Fiscal | data_início, data_fim | PDF + Excel |
| Inutilizações | Fiscal | data_início, data_fim | PDF + Excel |
| Log de Comunicação SEFAZ | Operacional | data_início, data_fim, ação | PDF + Excel |

**4.1.2 Implementação — Model:**

Criar **`app/models/NfeReportModel.php`** com namespace `Akti\Models`:

```
Métodos:
├── getNfesByPeriod($start, $end, $filters) → dados para relatório de NF-e
├── getTaxSummary($start, $end) → resumo de impostos (ICMS, PIS, COFINS, IPI)
├── getNfesByCustomer($start, $end, $customerId) → NF-e agrupadas por cliente
├── getCfopSummary($start, $end) → ranking de CFOPs com valores
├── getCancelledNfes($start, $end) → NF-e canceladas com motivos
├── getInutilizacoes($start, $end) → numerações inutilizadas
├── getSefazLogs($start, $end, $action) → logs de comunicação
└── getFiscalKpis($start, $end) → KPIs para resumo executivo
```

Fontes de dados:
- `nfe_documents` (principal)
- `nfe_document_items` (detalhamento por item — NCM, CFOP, impostos)
- `nfe_logs` (comunicação SEFAZ)
- `nfe_correction_history` (CC-e)
- `orders` (dados complementares do pedido)

**4.1.3 Implementação — Controller:**

Adicionar métodos ao **`ReportController`** existente:

```
Novos métodos privados:
├── exportPdfNfesByPeriod($start, $end)
├── exportExcelNfesByPeriod($start, $end)
├── exportPdfTaxSummary($start, $end)
├── exportExcelTaxSummary($start, $end)
├── exportPdfNfesByCustomer($start, $end)
├── exportExcelNfesByCustomer($start, $end)
├── exportPdfCfopSummary($start, $end)
├── exportExcelCfopSummary($start, $end)
├── exportPdfCancelledNfes($start, $end)
├── exportExcelCancelledNfes($start, $end)
└── exportPdfSefazLogs($start, $end)
```

Roteamento nos métodos `exportPdf()` e `exportExcel()`:
- Adicionar cases no switch de `$type` para cada novo relatório fiscal

**4.1.4 Implementação — View:**

Na view `app/views/reports/index.php`, adicionar uma **nova categoria "Fiscal"** com cards para cada relatório fiscal, seguindo o mesmo padrão visual (card com ícone, título, descrição, formulário de filtro com datas e botões PDF/Excel).

**Novo grupo visual na view de relatórios:**
```
📂 Fiscal
├── 📄 NF-e por Período (filtro: datas, status, modelo)
├── 📄 Resumo de Impostos (filtro: datas)
├── 📄 NF-e por Cliente (filtro: datas, cliente)
├── 📄 Resumo CFOP (filtro: datas)
├── 📄 NF-e Canceladas (filtro: datas)
├── 📄 Inutilizações (filtro: datas)
└── 📄 Logs SEFAZ (filtro: datas, ação)
```

**4.1.5 Design dos relatórios PDF:**

Seguir o padrão existente do `ReportController`:
- Cabeçalho com logo, nome da empresa e título do relatório
- Resumo executivo com KPIs em cards coloridos (total de NF-e, valor total, impostos totais, etc.)
- Tabela principal com dados, linhas zebradas e bordas sutis
- Cores da paleta existente: `CLR_PRIMARY`, `CLR_ACCENT`, `CLR_SUCCESS`, `CLR_DANGER`
- Rodapé com data de emissão e nome do usuário responsável

**4.1.6 Design dos relatórios Excel:**

Seguir o padrão existente:
- Aba de resumo com KPIs
- Aba de dados detalhados
- Formatação profissional (cabeçalho com cor, bordas, formatos numéricos)
- Auto-dimensionamento de colunas

**Estimativa:** 1-2 semanas

---

### 4.2 Eventos NF-e — Expansão e integração

**4.2.1 Mapa completo de eventos a implementar:**

| Evento | Disparado em | Listener(s) |
|--------|-------------|-------------|
| `model.nfe_document.created` | `NfeDocument::create()` | Log + auditoria |
| `model.nfe_document.authorized` | `markAuthorized()` | Log + DANFE + XML em disco + e-mail + notificação |
| `model.nfe_document.cancelled` | `markCancelled()` | Log + notificação + estorno financeiro |
| `model.nfe_document.corrected` | `correction()` | Log + notificação + histórico CC-e |
| `model.nfe_document.rejected` | `emit()` (rejeição) | Log + notificação + alerta |
| `model.nfe_document.inutilized` | `inutilizar()` | Log + notificação |
| `model.nfe_document.error` | qualquer erro SEFAZ | Log + alerta admin |
| `model.nfe_credential.updated` | `NfeCredential::update()` | Log de auditoria |
| `model.nfe_credential.cert_expiring` | Cron/verificação | Notificação 30/15/7/1 dias |
| `model.nfe_document.contingency_activated` | `setContingency()` | Log + alerta admin |
| `model.nfe_document.contingency_deactivated` | `deactivateContingency()` | Log |

**4.2.2 Implementação dos listeners em `events.php`:**

**Listener de autorização (expandido):**
1. ✅ Log em arquivo (já existe)
2. **NOVO:** Gerar e salvar DANFE automaticamente
3. **NOVO:** Salvar XML em disco
4. **NOVO:** Enviar XML + DANFE por e-mail ao destinatário (se e-mail disponível)
5. **NOVO:** Criar notificação interna para o usuário
6. **NOVO:** Disparar webhook (se configurado)

**Listener de cancelamento (expandido):**
1. ✅ Log em arquivo (já existe)
2. **NOVO:** Salvar XML de cancelamento em disco
3. **NOVO:** Notificar usuário e admin
4. **NOVO:** Integrar com módulo financeiro (marcar recebimentos como "estornados" se houver)
5. **NOVO:** Disparar webhook (se configurado)

**Listener de rejeição (expandido):**
1. ✅ Log em arquivo (já existe)
2. **NOVO:** Notificação interna com código e motivo da rejeição
3. **NOVO:** Sugestão automática de correção baseada no código de rejeição (tabela de códigos SEFAZ)

**Listener de expiração de certificado (NOVO):**
1. Executar verificação periódica (via cron ou ao acessar o módulo)
2. Se `certificate_expiry` estiver a ≤30 dias: alerta informativo
3. Se ≤7 dias: alerta urgente
4. Se expirado: bloquear emissão com mensagem clara

**Estimativa:** 3-5 dias

---

### 4.3 Envio de XML/DANFE por e-mail (P2.3)
**Impacto:** Em ERPs profissionais, o XML e DANFE são enviados automaticamente ao destinatário.

**Ações:**
- No listener de `model.nfe_document.authorized`:
  1. Carregar dados do pedido → obter e-mail do cliente
  2. Se e-mail disponível: montar e-mail com template HTML (número NF-e, chave, valores)
  3. Anexar XML autorizado e DANFE (PDF)
  4. Enviar usando serviço de e-mail do sistema (se existir) ou PHPMailer
  5. Registrar envio no log
- Configuração: permitir ativar/desativar envio automático por e-mail via `company_settings`

**Estimativa:** 2-3 dias

---

### 4.4 Implementar vTotTrib — Lei 12741 / IBPTax (P2.6 / X12)
**Impacto:** Obrigatório por lei — valor aproximado de tributos deve constar na NF-e.

**Ações:**
- Criar tabela `tax_ibptax` com dados da tabela IBPTax (NCM, alíquota federal, estadual, municipal, importação)
- Criar script de importação da tabela IBPTax (arquivo CSV atualizado periodicamente pelo IBPT)
- No `NfeXmlBuilder::build()`:
  - Para cada item, buscar percentual de tributos pelo NCM
  - Calcular `vTotTrib` por item e total
  - Incluir na tag `ICMSTot`
  - Adicionar mensagem de tributos aproximados em `infAdic`
- Configurar `tokenIBPT` nas variáveis de ambiente (para API se necessário)

**Estimativa:** 2-3 dias

---

### 4.5 Dashboard Fiscal Avançado (P2.11)
**Impacto:** Cards atuais são básicos — falta visão gerencial.

**Ações:**
- Expandir a view `index.php` de NF-e com seção de dashboard:
  - **Gráfico de barras:** NF-e emitidas por mês (últimos 12 meses)
  - **Gráfico de pizza:** Distribuição por status (autorizada, cancelada, rejeitada)
  - **KPIs em cards:** Total emitidas, valor total autorizado, impostos totais, ticket médio, taxa de rejeição
  - **Tabela top 5:** CFOPs mais utilizados
  - **Tabela top 5:** Clientes com maior faturamento fiscal
  - **Alertas:** Certificado próximo de expirar, NF-e processando há muito tempo, séries com gaps na numeração
- Usar Chart.js (já utilizado no sistema?) ou biblioteca leve para gráficos
- Dados fornecidos por novos métodos no `NfeDocument` ou `NfeReportModel`

**Estimativa:** 3-5 dias

---

### 4.6 Integração com Módulo Financeiro
**Impacto:** NF-e e financeiro são módulos desconectados.

**Ações:**
- Ao autorizar NF-e: se houver parcelas financeiras vinculadas ao pedido, marcar como "faturadas"
- Ao cancelar NF-e: marcar parcelas como "estornadas" ou reverter status
- Na view de detalhe da NF-e: mostrar status de pagamento vinculado
- Na view do financeiro: mostrar link para NF-e do pedido

**Estimativa:** 2-3 dias

---

### 4.7 Integração com Pipeline
**Impacto:** Linha de produção desconectada do status fiscal.

**Ações:**
- Na view de detalhe do pipeline: mostrar badge com status da NF-e (se houver)
- Permitir emitir NF-e diretamente do card do pipeline (quando pedido atingir etapa configurável)
- Configuração: definir em qual etapa do pipeline a NF-e é emitida automaticamente (opcional) ou sugerida

**Estimativa:** 2-3 dias

---

### 4.8 Integração com Estoque
**Impacto:** Autorização de NF-e deveria dar baixa no estoque; cancelamento deveria estornar.

**Ações:**
- No listener de `model.nfe_document.authorized`:
  1. Para cada item do pedido, dar baixa no estoque
  2. Registrar movimentação de estoque com referência à NF-e
- No listener de `model.nfe_document.cancelled`:
  1. Estornar a baixa de estoque dos itens
  2. Registrar movimentação de estorno com referência

**Estimativa:** 2-3 dias

---

### ✅ Critérios de conclusão da Fase 4
- [x] 7 relatórios fiscais em PDF e Excel integrados ao módulo de relatórios
- [x] Categoria "Fiscal" visível na tela de relatórios
- [x] Todos os eventos expandidos com novos listeners
- [x] XML/DANFE enviados por e-mail automaticamente (configurável)
- [x] vTotTrib (IBPTax) calculado e incluído no XML
- [x] Dashboard fiscal com gráficos e KPIs
- [x] Integração básica com financeiro, pipeline e estoque
- [x] NF-e detail view: exibe parcelas financeiras e status de pagamento
- [x] Financial view: exibe link/badge de NF-e nas parcelas
- [x] IBPTax: importação de CSV via UI (credenciais SEFAZ)
- [x] NF-e detail view: exibe valor de tributos aproximados (Lei 12.741)

**Concluído em:** 2026-03-28  
**Arquivos criados/modificados (Fase 4):**
- `sql/update_202603281200_fase4_ibptax_integracoes.sql` — Migration IBPTax, notificações, índices, settings
- `app/models/IbptaxModel.php` — Lookup, cálculo, importação CSV IBPTax
- `app/models/NfeReportModel.php` — Queries para relatórios fiscais e dashboard KPIs
- `app/services/NfeXmlBuilder.php` — Integração vTotTrib (Lei 12.741) no XML
- `app/controllers/NfeDocumentController.php` — Methods detail() com financeiro/IBPTax, dashboard()
- `app/controllers/NfeCredentialController.php` — Actions importIbptax(), ibptaxStats()
- `app/controllers/ReportController.php` — 7 relatórios fiscais (PDF + Excel)
- `app/views/nfe/detail.php` — Seção financeira, tributos aproximados
- `app/views/nfe/dashboard.php` — Dashboard fiscal completo com Chart.js
- `app/views/nfe/credentials.php` — Seção IBPTax com importação CSV e stats
- `app/views/reports/index.php` — Categoria "Fiscal" com 7 cards de relatórios
- `app/views/financial/payments.php` — Coluna NF-e na tabela de parcelas
- `app/views/financial/partials/_section_payments.php` — Coluna NF-e na tabela parcelas
- `app/views/financial/installments.php` — Link NF-e no header do pedido
- `app/models/Installment.php` — JOIN com nfe_documents no getPaginated()
- `assets/js/financial-payments.js` — Renderização badge NF-e na tabela
- `app/config/routes.php` — Actions importIbptax, ibptaxStats
- `app/bootstrap/events.php` — Listeners expandidos (financeiro, estoque, pipeline)
- `app/views/pipeline/index.php` — Badge NF-e nos cards do pipeline

---

## FASE 5 — FUNCIONALIDADES AVANÇADAS 🚀
**Duração estimada:** 4-6 semanas (pode ser implementada incrementalmente)  
**Prioridade:** 🟢 MÉDIA — Diferencial de mercado.  
**Objetivo:** Alcançar Nível 3-4 de maturidade fiscal, equiparando-se a ERPs profissionais.

---

### 5.1 Manifestação do Destinatário (P2.9)
**Impacto:** Permite confirmar, desconhecer ou recusar NF-e emitidas contra o CNPJ do emitente.

**Ações:**
- Implementar `NfeService::manifestar($chave, $tipoEvento, $justificativa)`
- Tipos de evento:
  - `210200` — Confirmação da Operação
  - `210210` — Ciência da Operação
  - `210220` — Desconhecimento da Operação
  - `210240` — Operação Não Realizada
- Criar tela de manifestação com listagem de NF-e recebidas
- Integrar com DistDFe para descobrir NF-e emitidas contra o CNPJ

**Estimativa:** 3-5 dias

---

### 5.2 Consulta DistDFe (P2.10)
**Impacto:** Receber automaticamente NF-e emitidas por fornecedores contra o CNPJ do emitente.

**Ações:**
- Implementar `NfeService::consultaDistDFe($ultNSU)`
- Armazenar último NSU consultado nas credenciais
- Agendar consulta periódica (cron ou manual)
- Listar documentos recebidos em nova aba da view de NF-e
- Opções: manifestar, baixar XML, importar dados

**Estimativa:** 1 semana

---

### 5.3 Emissão em Lote (P3.5)
**Impacto:** Emitir NF-e para múltiplos pedidos de uma vez.

**Ações:**
- Na view de NF-e ou pedidos: checkbox para selecionar múltiplos pedidos
- Botão "Emitir NF-e em Lote"
- Processamento sequencial (ou via fila se implementada) com progress bar
- Relatório final: quantas autorizadas, quantas rejeitadas, detalhes dos erros
- Limite configurável (ex: máximo 50 por lote)

**Estimativa:** 3-5 dias

---

### 5.4 Fila de Emissão Assíncrona (P3.6)
**Impacto:** Desacoplar a emissão da request HTTP para evitar timeouts e melhorar UX.

**Ações:**
- Criar tabela `nfe_queue` com status: `pending`, `processing`, `completed`, `failed`
- Ao solicitar emissão: criar registro na fila e retornar imediatamente
- Processador de fila (via cron ou Node.js API):
  - Buscar próximo item `pending`
  - Marcar como `processing`
  - Executar `NfeService::emit()`
  - Marcar como `completed` ou `failed`
- Atualizar UI via polling ou WebSocket para mostrar progresso

**Estimativa:** 1 semana

---

### 5.5 Personalização do DANFE (P3.1)
**Impacto:** Logo da empresa e informações adicionais no DANFE.

**Ações:**
- Configurar logo da empresa via `company_settings` ou upload dedicado
- Passar logo ao `Danfe` da sped-da
- Permitir definir informações adicionais padrão (texto fixo no rodapé)
- Para NFC-e: logo no topo do cupom

**Estimativa:** 1-2 dias

---

### 5.6 Webhook para integrações externas (P3.2)
**Impacto:** Permite que sistemas externos reajam a eventos fiscais.

**Ações:**
- Criar configuração de webhooks por tenant: URL, eventos assinados, headers personalizados, secret
- No dispatcher de eventos, após cada evento NF-e: disparar webhook (HTTP POST com payload JSON)
- Implementar retry com backoff para falhas
- Log de entregas de webhook

**Estimativa:** 2-3 dias

---

### 5.7 SPED Fiscal e SPED Contribuições (P3.3)
**Impacto:** Geração de obrigações acessórias — diferencial significativo.

**Ações:**
- Utilizar biblioteca `nfephp-org/sped-efdreinf` ou construir manualmente
- Gerar arquivo SPED Fiscal (EFD ICMS/IPI) com dados das NF-e emitidas
- Gerar arquivo SPED Contribuições (EFD PIS/COFINS)
- Integrar como novo relatório na área de relatórios fiscais

**Estimativa:** 2-3 semanas (complexidade alta)

---

### 5.8 Auditoria Completa de Acessos (P3.7)
**Impacto:** Trilha de auditoria para compliance.

**Ações:**
- Registrar em tabela de auditoria:
  - Quem acessou cada NF-e
  - Quem emitiu, cancelou, corrigiu
  - Quem fez download de XML/DANFE
  - Quem alterou credenciais
  - IP, data/hora, user agent
- View de auditoria acessível a administradores

**Estimativa:** 2-3 dias

---

### 5.9 Multi-filial / Multi-CNPJ (CR4)
**Impacto:** Tenants com múltiplas filiais precisam de credenciais separadas.

**Ações:**
- Alterar `nfe_credentials` para suportar múltiplos registros (remover restrição id=1)
- Adicionar campo `filial_id` ou `cnpj_emitente` como seletor
- Na emissão, selecionar qual credencial/filial emite
- Séries e numeração independentes por filial

**Estimativa:** 3-5 dias

---

### 5.10 Tabelas Fiscais de Referência
**Impacto:** Autocompletar e validar dados fiscais.

**Ações — Criar SQL de tabelas auxiliares:**

| Tabela | Descrição | Registros (aprox.) |
|--------|-----------|-------------------|
| `tax_ncm` | NCMs com descrição | ~13.000 |
| `tax_cfop` | CFOPs com descrição | ~600 |
| `tax_cest` | CESTs vinculados a NCMs | ~800 |
| `tax_ibptax` | Alíquotas IBPTax por NCM | ~13.000 |
| `tax_icms_interstate` | Alíquotas interestaduais UF×UF | 27×27 |
| `tax_municipios_ibge` | Municípios com código IBGE | ~5.600 |

- Importar dados de fontes oficiais (Receita Federal, IBGE, IBPT)
- Criar script de atualização periódica
- Usar para autocompletar no cadastro de produtos e emissão

**Estimativa:** 1 semana

---

### ✅ Critérios de conclusão da Fase 5
- [x] Manifestação do destinatário funcional
- [x] DistDFe consultando e listando documentos
- [x] Emissão em lote operacional
- [x] Fila assíncrona de emissão implementada
- [x] DANFE personalizado com logo
- [x] Webhooks configuráveis
- [x] Trilha de auditoria completa
- [ ] Multi-filial suportado (adiado — baixa prioridade)
- [ ] Tabelas fiscais de referência importadas (parcial — IBPTax implementado)

**Concluído em:** 2026-03-26 (7/9 critérios)
**Arquivos criados/modificados (Fase 5):**
- `sql/update_202603261500_fase5_funcionalidades_avancadas.sql` — Migration Fase 5
- `app/models/NfeAuditLog.php` — Model de auditoria
- `app/models/NfeQueue.php` — Model da fila de emissão
- `app/models/NfeWebhook.php` — Model de webhooks
- `app/models/NfeReceivedDocument.php` — Model de documentos recebidos
- `app/services/NfeAuditService.php` — Serviço de auditoria
- `app/services/NfeQueueService.php` — Serviço da fila
- `app/services/NfeWebhookService.php` — Serviço de webhooks
- `app/services/NfeDistDFeService.php` — Serviço DistDFe
- `app/services/NfeManifestationService.php` — Serviço de manifestação
- `app/services/NfeDanfeCustomizer.php` — Personalização DANFE
- `app/controllers/NfeDocumentController.php` — Métodos Fase 5
- `app/views/nfe/queue.php` — View da fila
- `app/views/nfe/received.php` — View documentos recebidos
- `app/views/nfe/audit.php` — View de auditoria
- `app/views/nfe/webhooks.php` — View de webhooks
- `app/views/nfe/danfe_settings.php` — View personalização DANFE
- `app/config/routes.php` — Actions Fase 5

---

## FASE 6 — UX MODERNA E MINIMALISTA 🎨
**Duração estimada:** 2-3 semanas  
**Prioridade:** 🟢 MÉDIA — Aplicável em qualquer momento, preferencialmente junto com as fases 2-4.  
**Objetivo:** Interface intuitiva, responsiva, com feedback em tempo real e fluxo minimalista.

---

### 6.1 Redesign do Painel de NF-e (index.php)

**Estado atual:** Cards de resumo + tabela paginada. Funcional mas básico.

**Proposta de redesign:**

```
┌────────────────────────────────────────────────────────────────┐
│  📊 Dashboard Fiscal                                          │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐               │
│  │ 156  │ │ 142  │ │  8   │ │  4   │ │R$89k │               │
│  │Total │ │Autor.│ │Canc. │ │Rejei.│ │Valor │               │
│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘               │
│                                                                │
│  ┌──────────────────────┐  ┌──────────────────────┐           │
│  │ 📈 NF-e por mês      │  │ 🟢🔴🟡 Por status    │           │
│  │   (gráfico barras)   │  │   (gráfico pizza)    │           │
│  └──────────────────────┘  └──────────────────────┘           │
│                                                                │
│  ⚠️ Alertas: Certificado expira em 15 dias | 2 NF-e pendentes │
├────────────────────────────────────────────────────────────────┤
│  🔍 Filtros Rápidos                                           │
│  [Status ▼] [Modelo ▼] [Mês ▼] [Ano ▼] [Buscar...]          │
├────────────────────────────────────────────────────────────────┤
│  📋 Listagem de NF-e                                          │
│  ┌─────┬────┬───────┬──────────┬──────────┬────────┬────────┐│
│  │  #  │Mod.│Número │Destinat. │Valor     │Status  │ Ações  ││
│  ├─────┼────┼───────┼──────────┼──────────┼────────┼────────┤│
│  │ ... │ 55 │ 1001  │ Emp ABC  │ R$1.500  │✅ Aut. │ 🔍📥  ││
│  └─────┴────┴───────┴──────────┴──────────┴────────┴────────┘│
│  [◀ 1 2 3 ... 10 ▶]                                          │
├────────────────────────────────────────────────────────────────┤
│  ⚡ Ações Rápidas                                             │
│  [+ Nova NF-e] [📦 Lote] [❌ Inutilizar] [⚙️ Contingência]   │
└────────────────────────────────────────────────────────────────┘
```

**Princípios de UX:**
- **Minimalismo:** informação essencial visível, detalhes sob demanda
- **Feedback:** toasts para ações, spinners durante processamento, progress bars para lote
- **Responsividade:** cards empilham em mobile, tabela com scroll horizontal
- **Cores:** seguir paleta do sistema (Bootstrap 5 + customizações Akti)
- **Acessibilidade:** labels em todos os controles, contraste adequado, navegação por teclado

**Estimativa:** 3-5 dias

---

### 6.2 Modal de Emissão com Preview (V1)

**Fluxo ideal de emissão:**

```
1. Usuário clica "Emitir NF-e" no pedido (ou na listagem de NF-e)
                    ↓
2. Modal abre com PREVIEW dos dados:
   ┌────────────────────────────────────┐
   │  📄 Preview NF-e — Pedido #1234   │
   │                                    │
   │  Emitente: Empresa XYZ            │
   │  Destinatário: Cliente ABC        │
   │  CPF/CNPJ: 12.345.678/0001-00    │
   │                                    │
   │  Itens:                           │
   │  ┌───┬──────────┬─────┬────────┐  │
   │  │ # │ Produto  │ NCM │ Valor  │  │
   │  │ 1 │ Prod A   │ ... │ 50,00  │  │
   │  │ 2 │ Prod B   │ ... │ 75,00  │  │
   │  └───┴──────────┴─────┴────────┘  │
   │                                    │
   │  Impostos calculados:             │
   │  ICMS: R$ X | PIS: R$ Y          │
   │  COFINS: R$ Z | Total: R$ 125,00 │
   │                                    │
   │  ⚠️ Alertas: (se houver)          │
   │  • Item "Prod A" sem NCM         │
   │                                    │
   │  [Cancelar]     [✅ Emitir NF-e]  │
   └────────────────────────────────────┘
                    ↓
3. Ao clicar "Emitir NF-e":
   - Botão muda para spinner "Emitindo..."
   - Barra de progresso aparece:
     [████████░░░░░░] Assinando XML...
     [████████████░░] Enviando à SEFAZ...
     [██████████████] Autorizada! ✅
                    ↓
4. Toast de sucesso com ações:
   "NF-e 1001 autorizada! [📥 Download XML] [📄 DANFE]"
```

**Estimativa:** 2-3 dias

---

### 6.3 Tela de Detalhe Modernizada

**Proposta de layout com tabs:**

```
┌────────────────────────────────────────────┐
│  NF-e #1001 — Série 1                     │
│  Status: ✅ Autorizada                     │
│  Chave: 3526061234567800010055001000010... │
├────────────────────────────────────────────┤
│  [Dados] [Itens] [Impostos] [Eventos]     │
│  ─────────────────────────────────────     │
│  Tab ativa: Dados                          │
│  Emitente: ...                             │
│  Destinatário: ...                         │
│  Valor: R$ 125,00                          │
│  ...                                       │
├────────────────────────────────────────────┤
│  📎 Downloads                              │
│  [XML Autorizado] [DANFE] [XML Cancel.]    │
├────────────────────────────────────────────┤
│  ⚙️ Ações                                 │
│  [❌ Cancelar] [📝 Carta de Correção]      │
│  [🔄 Consultar SEFAZ]                     │
└────────────────────────────────────────────┘
```

**Estimativa:** 1-2 dias

---

### 6.4 Formulário de Credenciais com Wizard

**Proposta — Wizard em 3 etapas:**

```
Etapa 1: Dados da Empresa
  → CNPJ, IE, Razão Social, Nome Fantasia, CRT
  → Endereço completo (com busca por CEP via API)

Etapa 2: Certificado Digital
  → Upload do .pfx com validação imediata
  → Exibir: validade, CNPJ do certificado, confirmação de compatibilidade
  → Senha com máscara e opção de visualizar

Etapa 3: Configurações de Emissão
  → Ambiente (homologação/produção) com aviso claro
  → Série e número inicial
  → CSC (para NFC-e)
  → Botão "Testar Conexão SEFAZ" com feedback visual
```

**Estimativa:** 1-2 dias

---

### 6.5 Notificações e Alertas no Sistema

**Integrar alertas fiscais no sistema de notificações existente (se houver) ou criar:**

- **Badge no menu:** Número de NF-e rejeitadas/pendentes
- **Toast automático:** Ao autorizar/cancelar NF-e em background
- **Alerta de certificado:** Banner no topo do painel fiscal quando próximo de expirar
- **Alerta de contingência:** Banner vermelho quando em modo contingência

**Estimativa:** 1-2 dias

---

### ✅ Critérios de conclusão da Fase 6
- [x] Painel de NF-e com dashboard avançado e gráficos ✅ (KPIs, filtros, tabela moderna, ações rápidas)
- [x] Modal de emissão com preview e progress bar ✅ (emissão em lote, modal inutilização)
- [x] Tela de detalhe com tabs organizadas ✅ (Dados/Destinatário/Financeiro/Eventos/Ocorrências + timeline + modais Cancel/CC-e)
- [x] Formulário de credenciais com wizard ✅ (3 etapas: Empresa→Certificado→Configuração + busca CEP + progresso visual)
- [x] Notificações e alertas visuais implementados ✅ (partials/alerts_banner.php + partials/toast_notifications.php + NfeToast JS + NfeStatusPoller)
- [ ] Layout responsivo testado em mobile (implementado, falta teste manual)

---

## RESUMO DE ARQUIVOS A CRIAR/ALTERAR

### Arquivos NOVOS

| Arquivo | Fase | Descrição |
|---------|------|-----------|
| `sql/update_YYYYMMDD_nfe_tables.sql` | 1 | Migração das 5 tabelas NF-e |
| `sql/update_YYYYMMDD_product_fiscal_fields.sql` | 2 | Campos fiscais na tabela products |
| `app/services/TaxCalculator.php` | 2 | Cálculo dinâmico de impostos |
| `app/services/NfceQrCodeGenerator.php` | 3 | Gerador de QR Code NFC-e |
| `app/models/NfeReportModel.php` | 4 | Queries para relatórios fiscais |
| `sql/update_YYYYMMDD_tax_tables.sql` | 5 | Tabelas de referência fiscal (NCM, CFOP, CEST, IBPTax) |
| `sql/update_YYYYMMDD_nfe_queue.sql` | 5 | Tabela de fila de emissão |

### Arquivos ALTERADOS

| Arquivo | Fase | Alterações |
|---------|------|-----------|
| `composer.json` | 1 | Adicionar sped-nfe e sped-da |
| `app/controllers/NfeDocumentController.php` | 1-3 | CSRF, permissões, inutilizar, preview |
| `app/controllers/NfeCredentialController.php` | 1 | CSRF, permissões |
| `app/services/NfeService.php` | 1-3 | Retry, contingência, inutilização, NFC-e |
| `app/services/NfeXmlBuilder.php` | 2-3 | TaxCalculator, idDest, cobr, infRespTec, NFC-e, QR Code |
| `app/services/NfePdfGenerator.php` | 3 | DANFE-NFC-e |
| `app/models/NfeDocument.php` | 1-2 | Novos campos, soft delete |
| `app/models/NfeCredential.php` | 1-3 | APP_KEY, multi-filial, NFC-e |
| `app/bootstrap/events.php` | 2-4 | Novos listeners expandidos |
| `app/views/nfe/index.php` | 4-6 | Dashboard, gráficos, redesign |
| `app/views/nfe/detail.php` | 3-6 | Tabs, CC-e histórico, modernização |
| `app/views/nfe/credentials.php` | 6 | Wizard |
| `app/views/users/groups.php` | 1 | Adicionar permissões NF-e |
| `app/views/products/create.php` e `edit.php` | 2 | Campos fiscais |
| `app/controllers/ReportController.php` | 4 | Novos métodos de relatórios fiscais |
| `app/views/reports/index.php` | 4 | Categoria Fiscal com cards |
| `app/config/routes.php` | 3 | Novas actions (inutilizar, preview, manifestar) |

---

## CRONOGRAMA VISUAL

```
Semana  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18
       ├──┤
Fase 1  ████                                              
       Segurança, dependências, migrations, CSRF, retry

            ├────────────┤
Fase 2       ████████████
            TaxCalculator, NCM/CFOP, validação XSD, XML em disco

                          ├────────┤
Fase 3                     ████████
                          NFC-e, inutilização, contingência, preview

                                    ├────────────┤
Fase 4                               ████████████
                                    Relatórios, eventos, integrações

                                                  ├──────────────┤
Fase 5                                             ██████████████
                                                  Manifestação, DistDFe, lote, SPED

                 ├──── UX pode ser aplicada em paralelo ────┤
Fase 6            ████████████████████████████████████████ ✅
                 Design, modais, dashboard, responsividade
                 (5/6 critérios concluídos — falta teste mobile)
```

---

## MÉTRICAS DE SUCESSO

| Métrica | Fase 1 | Fase 2 | Fase 3 | Fase 4 | Fase 5 |
|---------|--------|--------|--------|--------|--------|
| Issues Críticas | 0 | 0 | 0 | 0 | 0 |
| Issues Altas | ≤10 | ≤5 | 0 | 0 | 0 |
| Nível de Maturidade | 1.5 | 2 | 2.5 | 3 | 3.5-4 |
| Cobertura de Testes | — | Básica | Média | Boa | Alta |
| NFC-e | ❌ | ❌ | ✅ | ✅ | ✅ |
| Relatórios Fiscais | 0 | 0 | 0 | 7+ | 10+ |
| Regimes Tributários | 1 (SN) | 3 (SN+LP+LR) | 3 | 3 | 3 |
| Integrações | 1 (Orders) | 1 | 1 | 4+ | 6+ |

---

## NOTAS IMPORTANTES

1. **Cada fase gera arquivo(s) SQL de migração** na pasta `/sql`, seguindo a regra do projeto.
2. **Testes devem acompanhar cada fase** — mínimo: testes unitários para TaxCalculator e NfeXmlBuilder.
3. **A Fase 1 é bloqueante** — nenhum código de fases posteriores deve ir para produção antes da conclusão da Fase 1.
4. **A Fase 6 (UX) pode ser paralelizada** — melhorias visuais podem ser aplicadas junto com qualquer fase funcional.
5. **Homologação obrigatória** — cada funcionalidade nova deve ser testada em ambiente `homologacao` da SEFAZ antes de ir para produção.
6. **Documentação** — ao concluir cada fase, atualizar este roadmap marcando os itens como `[x]` e atualizar os documentos da pasta `docs/nfe/`.

---

> **Próximo passo recomendado:** Iniciar a **Fase 1** imediatamente, priorizando P0.1 (instalar dependências) e P0.2 (criar SQL de migração), pois são pré-requisitos para todo o restante.

---

*Documento gerado em 2026-03-26 — Akti - Gestão em Produção*  
*Baseado na auditoria completa de 48+ issues identificadas em 721+ linhas de análise.*
