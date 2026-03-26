# Comparativo com ERPs Profissionais — Módulo NF-e / NFC-e

**Data:** 2026-03-26  
**Referência:** `docs/nfe/01_AUDITORIA_COMPLETA.md`  
**Objetivo:** Comparar a implementação atual com padrões de mercado de ERPs profissionais (Bling, Tiny, Omie, SAP B1, etc.)

---

## 1. Funcionalidades Essenciais de NF-e em ERPs

### Legenda
- ✅ Implementado e funcional
- ⚠️ Implementado parcialmente ou com problemas
- ❌ Não implementado

| Funcionalidade | Akti Atual | ERP Profissional | Gap |
|---------------|------------|-------------------|-----|
| **Emissão NF-e (mod 55)** | ⚠️ Parcial | ✅ | Impostos fixos, sem validação |
| **Emissão NFC-e (mod 65)** | ❌ | ✅ | Totalmente ausente |
| **Cancelamento** | ⚠️ Parcial | ✅ | Sem verificação de prazo |
| **Carta de Correção** | ⚠️ Parcial | ✅ | Sem limite, sem histórico |
| **Inutilização** | ❌ | ✅ | Funcionalidade ausente |
| **DANFE (PDF)** | ⚠️ Parcial | ✅ | Sem personalização, sem NFC-e |
| **Contingência** | ❌ | ✅ | Nenhum modo implementado |
| **Consulta SEFAZ** | ✅ | ✅ | OK |
| **Teste de conexão** | ✅ | ✅ | OK |
| **Download XML** | ✅ | ✅ | OK |
| **Log de comunicação** | ✅ | ✅ | OK |
| **Manifestação dest.** | ❌ | ✅ | Ausente |
| **DistDFe** | ❌ | ✅ | Ausente |
| **Emissão em lote** | ❌ | ✅ | Ausente |
| **Envio XML por e-mail** | ❌ | ✅ | Ausente |
| **Cálculo tributário** | ❌ | ✅ | Impostos fixos |
| **Múltiplos regimes** | ❌ | ✅ | Apenas SN |
| **CFOP automático** | ❌ | ✅ | CFOP fixo |
| **DIFAL** | ❌ | ✅ | Ausente |
| **ST (Substituição)** | ❌ | ✅ | Ausente |
| **IBPTax (Lei 12741)** | ❌ | ✅ | Ausente |
| **SPED Fiscal** | ❌ | ✅ | Ausente |
| **Relatórios fiscais** | ❌ | ✅ | Ausente |
| **Preview antes envio** | ❌ | ✅ | Ausente |
| **Reprocessamento** | ❌ | ✅ | Ausente |
| **Fila de emissão** | ❌ | ✅/⚠️ | Ausente |
| **Auditoria de acessos** | ❌ | ✅ | Ausente |
| **Webhook/Integração** | ❌ | ✅ | Ausente |
| **Multi-filial** | ❌ | ✅ | Credencial única |
| **Proteção CSRF** | ❌ | ✅ | Vulnerabilidade |
| **Permissões granulares** | ❌ | ✅ | Sem verificação |

---

## 2. Nível de Maturidade

### Escala de Maturidade Fiscal

| Nível | Descrição | Akti |
|-------|-----------|------|
| **Nível 1** — Básico | Emite NF-e simples, modelo fixo, impostos fixos | ⬅️ **Atual** |
| **Nível 2** — Funcional | Emite NF-e/NFC-e, cálculo tributário, inutilização, contingência | Objetivo curto prazo |
| **Nível 3** — Profissional | Multi-regime, SPED, DistDFe, manifestação, integrações | Objetivo médio prazo |
| **Nível 4** — Enterprise | Multi-filial, emissão em lote, fila assíncrona, BI fiscal | Objetivo longo prazo |

**Posição atual do Akti: Nível 1 (Básico)**  
**Objetivo recomendado: Nível 2-3 em 3 meses**

---

## 3. Análise por Dimensão

### 3.1 Segurança

| Aspecto | Akti | Padrão ERP | Ação |
|---------|------|-----------|------|
| CSRF | ❌ Ausente | Token em todo POST | Implementar imediato |
| Permissões | ❌ Ausente | Granular por ação | Implementar imediato |
| Criptografia cert. | ⚠️ Chave previsível | Chave forte (env) | Corrigir imediato |
| Auditoria | ⚠️ Logs básicos | Trilha completa | Melhorar |
| Acesso certificado | ⚠️ Path fixo | Vault/HSM | Melhorar |

### 3.2 Conformidade Fiscal

| Aspecto | Akti | Padrão ERP | Ação |
|---------|------|-----------|------|
| Regime tributário | Apenas SN | SN + LP + LR | Implementar TaxCalculator |
| NCM | Fallback inválido | Obrigatório | Tornar obrigatório |
| CFOP | Fixo 5102 | Automático | Implementar lógica |
| CEST | Ausente | Por NCM | Implementar |
| vTotTrib | Ausente | Obrigatório | Implementar IBPTax |
| Guarda XML | Só banco | Banco + disco 5 anos | Implementar |
| Inutilização | Ausente | Obrigatório | Implementar |
| Contingência | Ausente | Recomendado | Implementar |

### 3.3 Experiência do Usuário

| Aspecto | Akti | Padrão ERP | Ação |
|---------|------|-----------|------|
| Emissão 1 clique | ⚠️ AJAX direto | Com preview | Adicionar preview |
| Feedback tempo real | ❌ sleep(3) | Polling/WebSocket | Melhorar |
| Dashboard fiscal | ⚠️ Cards básicos | Gráficos + KPIs | Melhorar |
| Busca avançada | ⚠️ Filtros básicos | Por período, CFOP, UF | Melhorar |
| Alertas proativos | ❌ | Certificado, prazo, erros | Implementar |

### 3.4 Resiliência

| Aspecto | Akti | Padrão ERP | Ação |
|---------|------|-----------|------|
| Retry automático | ❌ | Com backoff | Implementar |
| Contingência | ❌ | SVC/EPEC/FS-DA | Implementar |
| Reprocessamento | ❌ | Manual e automático | Implementar |
| Fila de emissão | ❌ | Assíncrona | Implementar |
| Timeout config | ❌ sleep fixo | Configurável | Implementar |

---

## 4. Benchmarks de ERPs Brasileiros

### 4.1 Bling
- Emissão NF-e e NFC-e
- Cálculo automático de impostos (SN, LP, LR)
- Contingência automática
- Inutilização integrada
- Envio automático de XML por e-mail
- Dashboard com gráficos
- API REST para integrações
- Emissão em lote

### 4.2 Tiny ERP
- Emissão NF-e, NFC-e, NFS-e
- Cálculo tributário completo
- Manifestação do destinatário
- SPED Fiscal integrado
- Emissão via API
- Preview antes do envio
- Regras fiscais por produto

### 4.3 Omie
- Emissão de todos os tipos de NF
- Motor tributário proprietário
- Contingência automática (SVC)
- Multi-filial
- Auditoria completa
- Certificado em nuvem (A3 via API)
- Relatórios fiscais avançados

---

## 5. Recomendações Prioritárias para Alcançar Nível 2

Para sair do **Nível 1 (Básico)** e chegar ao **Nível 2 (Funcional)**, as seguintes implementações são necessárias, em ordem:

1. ✅ **Segurança** — CSRF + Permissões (1-2 dias)
2. 📦 **Dependências** — Instalar sped-nfe e sped-da (1 hora)
3. 🗄️ **Banco** — Criar migrations SQL (1 dia)
4. 🔢 **Tributação** — TaxCalculator básico (1 semana)
5. 📋 **NCM/CFOP** — Tornar obrigatórios (2-3 dias)
6. 🔄 **Retry** — Consulta de recibo com backoff (1 dia)
7. ❌ **Inutilização** — Implementar funcionalidade (2-3 dias)
8. 📱 **NFC-e** — Suporte ao modelo 65 (1 semana)
9. ⚡ **Contingência** — SVC-AN/SVC-RS (3-5 dias)
10. 💾 **Storage** — XMLs em disco (1-2 dias)

**Estimativa total para Nível 2:** 4-6 semanas

---

## 6. Conclusão

O módulo NF-e do Akti tem uma **base arquitetural sólida** (MVC, camada de serviço, eventos, multi-tenant), mas a **implementação fiscal está em estágio inicial**. Os principais gaps são:

1. **Tributação** — O cálculo de impostos é fixo e limitado ao Simples Nacional
2. **Segurança** — Faltam proteções básicas (CSRF, permissões)
3. **NFC-e** — Totalmente ausente apesar da UI sugerir suporte
4. **Funcionalidades obrigatórias** — Inutilização e contingência não existem
5. **Conformidade** — NCM, CFOP, CEST e vTotTrib não são tratados adequadamente
6. **Resiliência** — Sem retry, sem fila, sem reprocessamento

A boa notícia é que a arquitetura permite a evolução incremental. As sugestões foram priorizadas para permitir um crescimento gradual e seguro do módulo, com foco primeiro na segurança e conformidade, e depois em funcionalidades avançadas.

---

> **Documentos complementares:**
> - `01_AUDITORIA_COMPLETA.md` — Auditoria detalhada
> - `02_SUGESTOES_MELHORIAS.md` — Sugestões técnicas de implementação
> - `03_MAPEAMENTO_FUNCOES.md` — Referência de todas as funções
> - `04_CONFIGURACOES_INTEGRACOES.md` — Configurações e integrações
