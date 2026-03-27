# 📖 Índice Geral — Avaliação do Cadastro de Clientes

> **Projeto:** Akti - Gestão em Produção  
> **Data da avaliação:** 27/03/2026  
> **Autor:** Avaliação técnica automatizada

---

## Documentos da Avaliação

| #  | Arquivo                          | Descrição                                                       |
|----|----------------------------------|-----------------------------------------------------------------|
| 01 | `01_DIAGNOSTICO_ATUAL.md`        | Diagnóstico completo do cadastro atual — banco, model, controller e views. Lista todos os problemas identificados com classificação de severidade. |
| 02 | `02_PROPOSTA_CAMPOS.md`          | Proposta detalhada de todos os campos necessários para um cadastro profissional. Inclui tabelas auxiliares (multi-contato, multi-endereço), índices e comparativo antes/depois. |
| 03 | `03_PROPOSTA_UX.md`              | Proposta de UX/UI com design minimalista avançado. Wizard multi-step, seletor PF/PJ, auto-preenchimento CEP/CNPJ, validação em tempo real, layout responsivo, micro-interações e funcionalidades avançadas (auto-save, tags, completude). |
| 04 | `04_PLANO_IMPLEMENTACAO.md`      | Plano de implementação em 4 fases com cronograma, tarefas detalhadas, esboço da migration SQL, checklist de entrega e dependências técnicas. |
| 05 | `05_COMPARATIVO_MERCADO.md`      | Benchmark contra sistemas profissionais do mercado brasileiro (Bling, Tiny, Omie, TOTVS) — comparativo de campos, score de completude e análise de UX. |
| 06 | `06_CHECKLIST_VALIDACOES.md`     | Checklist completo de validações client-side e server-side — CPF/CNPJ, e-mail, CEP, duplicidade, campos condicionais PF/PJ, APIs de integração (ViaCEP, BrasilAPI). |
| 🗺️ | `ROADMAP_CADASTRO_CLIENTES.md`  | **Roadmap completo e detalhado** — Desdobramento operacional das 4 fases com 69 tarefas, descrições técnicas por arquivo, fluxos, pseudocódigos, checklists de entrega, riscos, métricas de sucesso e calendário de marcos. |

---

## Resumo Executivo

### Situação Atual
- O cadastro de clientes possui **9 campos** e atinge **24% de completude** comparado a ERPs profissionais
- **20 problemas críticos** identificados entre banco, backend e interface
- Ausência de distinção PF/PJ, dados fiscais, dados comerciais e auditoria
- Interface sem máscaras, auto-preenchimento ou validação em tempo real
- Endereço armazenado como JSON (sem possibilidade de filtros por cidade/estado)
- Campo de observações presente no formulário mas **não salvo** no banco

### Proposta
- Expandir para **40+ campos** organizados em 5 grupos (Identificação, Contato, Endereço, Comercial, Controle)
- Score projetado: **96% de completude** (superior a Bling e Tiny, próximo de TOTVS)
- Interface wizard multi-step com design minimalista avançado
- Integrações: ViaCEP (endereço), BrasilAPI (CNPJ), validação CPF/CNPJ em tempo real
- 4 fases de implementação, estimativa de 8 semanas

### Impacto Esperado
- **UX:** Redução do atrito no cadastro com wizard, auto-preenchimento e campos condicionais
- **Dados:** Informações completas para operação fiscal (NF-e), comercial (CRM) e logística
- **Qualidade:** Validações robustas evitam dados inválidos e duplicados
- **Performance:** Índices no banco para buscas rápidas por documento, e-mail, cidade
- **Compliance:** Dados fiscais completos (IE, IM, IBGE) para emissão de NF-e

---

## Diagrama de Dependências entre Fases

```
┌─────────────────────┐
│  FASE 1             │
│  Banco + Model      │──────────┐
└─────────────────────┘          │
         │                        │
         ▼                        ▼
┌─────────────────────┐  ┌─────────────────────┐
│  FASE 2             │  │  FASE 3             │
│  Controller         │  │  Views / Interface  │
└─────────────────────┘  └─────────────────────┘
         │                        │
         └──────────┬─────────────┘
                    ▼
         ┌─────────────────────┐
         │  FASE 4             │
         │  Integrações/Testes │
         └─────────────────────┘
```

> **Nota:** As Fases 2 e 3 podem ser executadas em paralelo após a conclusão da Fase 1.
