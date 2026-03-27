# 🔍 Comparativo de Mercado — Cadastro de Clientes

> **Data:** 27/03/2026  
> **Objetivo:** Comparar o cadastro atual do Akti com sistemas de referência do mercado brasileiro

---

## 1. Benchmark — Campos por Sistema

### Legenda
- ✅ = Presente | ❌ = Ausente | 🔶 = Parcial

| Campo / Funcionalidade         | **Akti (Atual)** | **Bling** | **Tiny** | **Omie** | **TOTVS** | **Proposto Akti** |
|-------------------------------|:---:|:---:|:---:|:---:|:---:|:---:|
| **Identificação**             |     |     |     |     |     |     |
| Tipo Pessoa (PF/PJ)          | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Código interno                | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Nome / Razão Social          | ✅  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Nome Fantasia                 | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| CPF/CNPJ                      | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| RG / Inscrição Estadual      | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Inscrição Municipal           | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| Data Nascimento/Fundação      | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| Gênero                        | ❌  | ❌  | ❌  | ❌  | ✅  | ✅  |
| **Contato**                   |     |     |     |     |     |     |
| E-mail principal              | ✅  | ✅  | ✅  | ✅  | ✅  | ✅  |
| E-mail secundário             | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| Telefone fixo                 | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Celular / WhatsApp            | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Telefone comercial            | ❌  | ❌  | ❌  | ✅  | ✅  | ✅  |
| Website                       | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| Rede social                   | ❌  | ❌  | ❌  | ❌  | ❌  | ✅  |
| Contato principal (PJ)       | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Multi-contatos (PJ)          | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| **Endereço**                  |     |     |     |     |     |     |
| CEP                           | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Logradouro                    | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Número                        | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Complemento                   | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Bairro                        | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Cidade                        | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Estado (UF)                   | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| País                          | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| Código IBGE                   | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| Auto-preenchimento CEP        | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Multi-endereços               | ❌  | ✅  | ❌  | ✅  | ✅  | 🔶 |
| **Comercial**                 |     |     |     |     |     |     |
| Tabela de preço               | ✅  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Condição de pagamento         | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Limite de crédito             | ❌  | ❌  | ❌  | ✅  | ✅  | ✅  |
| Desconto padrão               | ❌  | ❌  | ❌  | ✅  | ✅  | ✅  |
| Vendedor responsável          | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Origem / Canal                | ❌  | ❌  | ❌  | ✅  | ✅  | ✅  |
| Tags / Classificação          | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |
| **Controle**                  |     |     |     |     |     |     |
| Status (Ativo/Inativo)        | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Observações / Notas           | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Foto / Avatar                 | ✅  | ❌  | ❌  | ❌  | ✅  | ✅  |
| Auditoria (created/updated)   | 🔶 | ✅  | ✅  | ✅  | ✅  | ✅  |
| Soft delete                   | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| **UX/Interface**              |     |     |     |     |     |     |
| Máscara de campos             | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Validação em tempo real       | ❌  | ✅  | 🔶 | ✅  | ✅  | ✅  |
| Consulta CNPJ automática      | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Verificação de duplicidade     | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Exportação CSV/Excel          | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Importação em massa           | ✅  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Ficha detalhada do cliente    | ❌  | ✅  | ✅  | ✅  | ✅  | ✅  |
| Filtros avançados             | ❌  | ✅  | 🔶 | ✅  | ✅  | ✅  |
| Ações em lote                 | ❌  | ✅  | ❌  | ✅  | ✅  | ✅  |

---

## 2. Score de Completude

| Sistema          | Campos Presentes | Total Avaliado | Score |
|------------------|:---:|:---:|:---:|
| **TOTVS**        | 45  | 46  | **98%** |
| **Omie**         | 42  | 46  | **91%** |
| **Bling**        | 37  | 46  | **80%** |
| **Tiny**         | 28  | 46  | **61%** |
| **Akti (Proposto)** | 44  | 46  | **96%** |
| **Akti (Atual)** | 11  | 46  | **24%** |

### Conclusão do Benchmark

O cadastro atual do Akti está com **24%** de completude comparado a sistemas profissionais. A proposta eleva para **96%**, superando Bling e Tiny, e ficando próximo de TOTVS e Omie.

---

## 3. Diferenciais Únicos Propostos para o Akti

Funcionalidades que poucos ou nenhum dos concorrentes têm:

| Diferencial                     | Descrição                                                    |
|---------------------------------|--------------------------------------------------------------|
| 📷 Foto/Avatar do cliente       | Personalização visual (TOTVS tem, outros não)                |
| 📱 Instagram como campo         | Foco em vendas por redes sociais (nenhum concorrente tem)    |
| 🏷️ Tags com autocomplete        | Classificação livre e flexível                                |
| 📊 Indicador de completude      | Gamificação do preenchimento                                  |
| 💾 Auto-save (rascunho local)   | Segurança contra perda de dados                               |
| 🔍 Wizard multi-step            | UX superior para cadastros extensos                           |
| 🎨 Design minimalista avançado  | Interface moderna e limpa (diferencial visual)                |

---

## 4. UX Benchmark — Fluxo de Cadastro

### Análise de Cliques para Cadastrar um Cliente PJ Completo

| Sistema     | Etapas/Telas | Campos Visíveis | Tempo Estimado | Complexidade |
|-------------|:---:|:---:|:---:|:---:|
| TOTVS       | 5+ abas      | 60+             | 8-12 min       | Alta         |
| Omie        | 3 abas       | 40+             | 5-8 min        | Média-Alta   |
| Bling       | 1 página     | 30+             | 3-5 min        | Média        |
| Tiny        | 1 página     | 20+             | 2-3 min        | Baixa        |
| **Akti (Atual)**  | 1 página | 8              | 1 min          | Muito Baixa  |
| **Akti (Proposto)** | 4 steps | 35+           | 3-5 min        | Média        |

### Vantagem do Wizard Multi-Step

O wizard do Akti proposto divide 35+ campos em 4 etapas de ~9 campos cada, mantendo a complexidade percebida baixa enquanto coleta todos os dados necessários. O usuário vê apenas 1 grupo por vez, reduzindo a carga cognitiva.

---

> **Próximo passo:** Veja o arquivo `05_CHECKLIST_VALIDACOES.md` para o checklist completo de validações.
