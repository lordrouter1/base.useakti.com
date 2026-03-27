# 📋 Diagnóstico Completo — Cadastro de Clientes Atual

> **Data da avaliação:** 27/03/2026  
> **Versão analisada:** Módulo `customers` (Model, Controller, Views)  
> **Arquivo de referência BD:** `sql/database.sql` (linhas 75-88)

---

## 1. Estrutura Atual do Banco de Dados

### Tabela `customers` (atual)

| Coluna          | Tipo             | Nullable | Observação                        |
|-----------------|------------------|----------|-----------------------------------|
| `id`            | INT AUTO_INCREMENT | NOT NULL | PK                               |
| `name`          | VARCHAR(100)     | NOT NULL | Nome único (PF ou PJ junto)       |
| `email`         | VARCHAR(100)     | YES      | Único campo de e-mail             |
| `phone`         | VARCHAR(20)      | YES      | Único campo de telefone            |
| `document`      | VARCHAR(20)      | YES      | CPF e CNPJ juntos sem distinção    |
| `address`       | TEXT (JSON)      | YES      | Endereço serializado em JSON       |
| `photo`         | VARCHAR(255)     | YES      | Caminho do arquivo de foto         |
| `price_table_id`| INT              | YES      | FK para `price_tables`             |
| `created_at`    | TIMESTAMP        | DEFAULT  | Data de criação                    |

### 🔴 Problemas Críticos Identificados no Banco

1. **Ausência de `updated_at`** — Impossível saber quando o registro foi modificado pela última vez.
2. **Sem campo `status` / `active`** — Não há como inativar clientes sem excluí-los (perda de histórico).
3. **Sem distinção PF/PJ** — Não há campo `person_type` (Pessoa Física / Jurídica). Isso impede lógica condicional de campos obrigatórios (CPF vs CNPJ, Nome vs Razão Social, etc).
4. **Sem Inscrição Estadual / Municipal** — Essencial para emissão de NF-e (o sistema já tem módulo NFe).
5. **Endereço em JSON** — Impossibilita consultas SQL por cidade, estado, bairro, CEP. Sem campo `city` e `state` separados.
6. **Sem campos de contato secundário** — Apenas 1 telefone e 1 e-mail. Sistemas profissionais permitem múltiplos contatos.
7. **Sem campo `notes` / `observations`** — A view tem um campo de observações no form, mas **não é salvo** no banco (não existe a coluna).
8. **Sem `fantasy_name`** — Para PJ, o nome fantasia é fundamental.
9. **Sem `birth_date`** — Para PF, a data de nascimento é usada em CRM e marketing.
10. **Sem `website`** — Informação básica para PJ.
11. **Sem soft delete (`deleted_at`)** — A exclusão é permanente, perdendo todo o histórico.
12. **Sem índices** — Não há índice em `email`, `document`, `phone` para buscas rápidas.
13. **Sem unicidade** — O `document` (CPF/CNPJ) não tem constraint UNIQUE, permitindo cadastros duplicados.

---

## 2. Análise do Model (`app/models/Customer.php`)

### ✅ Pontos Positivos
- Segue o padrão MVC com namespace PSR-4
- Usa PDO com prepared statements (segurança contra SQL Injection)
- Despacha eventos (EventDispatcher) no CRUD
- Tem paginação com filtros
- Tem busca para Select2 (autocomplete AJAX)
- Tem importação de dados mapeados

### 🔴 Problemas Identificados
1. **Propriedades públicas incompletas** — Declara `$id`, `$name`, `$email`, `$phone`, mas não usa essas propriedades (usa arrays diretamente).
2. **Sem validação de CPF/CNPJ** — O model não valida o formato ou dígitos verificadores do documento.
3. **Sem verificação de duplicidade** — Não checa se já existe um cliente com o mesmo CPF/CNPJ ou e-mail.
4. **`create()` hardcoded** — Os campos no INSERT são fixos; se mudar a tabela, precisa alterar manualmente.
5. **Endereço como JSON** — O `importFromMapped()` monta JSON do endereço, mas isso deveria ser em colunas separadas ou em tabela relacional.
6. **Sem método `search()` por campos específicos** — A busca é genérica (LIKE em tudo).

---

## 3. Análise do Controller (`app/controllers/CustomerController.php`)

### ✅ Pontos Positivos
- Usa `Input::post()` / `Input::get()` com sanitização
- Usa `Validator` para validação server-side
- Upload de foto com validação de tipo e tamanho
- Suporta importação em massa com mapeamento dinâmico
- Template de importação CSV

### 🔴 Problemas Identificados
1. **Validação mínima** — Apenas `required('name')` e `maxLength('name')`. Não valida:
   - Formato de e-mail
   - Formato de telefone
   - Formato de CPF/CNPJ
   - CEP válido
2. **Campo `observations`** — Existe no form mas **não é capturado** no `store()` nem no `update()`. O dado é perdido.
3. **Sem verificação de duplicidade** — Permite cadastrar o mesmo CPF/CNPJ várias vezes.
4. **`delete()` usa GET** — Exclusão deveria ser POST/DELETE por segurança (CSRF). Hoje, um link simples exclui o registro.
5. **Sem log de auditoria** — O Logger existe mas não é usado no `store()`, `update()` ou `delete()`.
6. **Sem controle de permissão por action** — Não verifica `checkAdmin()` ou permissão por grupo em cada método.

---

## 4. Análise das Views

### 4.1 `create.php` — Formulário de Criação

#### ✅ Pontos Positivos
- Layout com fieldsets organizados (Foto, Dados Principais, Endereço, Tabela de Preço)
- Upload de foto com preview
- Bootstrap 5 responsivo
- Ícones Font Awesome

#### 🔴 Problemas Identificados
1. **Sem distinção PF/PJ** — Não há seletor de tipo de pessoa; campos são idênticos para ambos.
2. **Sem máscara de CPF/CNPJ** — O campo `document` não aplica máscara dinâmica.
3. **Sem máscara de telefone** — Sem formatação automática (XX) XXXXX-XXXX.
4. **Sem máscara de CEP** — Sem formatação XXXXX-XXX.
5. **Sem auto-preenchimento por CEP** — Não integra com API de CEP (ViaCEP).
6. **Sem auto-preenchimento por CNPJ** — Não consulta dados da Receita Federal.
7. **Campo `observations` não é salvo** — Existe no form mas o controller ignora.
8. **Sem campo de cidade e estado** — O endereço não tem UF e cidade (essenciais).
9. **Sem validação client-side** — Não usa validação em tempo real (feedback visual).
10. **Sem stepper/wizard** — O formulário é uma página longa. Sistemas modernos usam wizard multi-step para formulários extensos.
11. **Sem campo de contato adicional** — Apenas 1 telefone, 1 e-mail.
12. **Sem indicador de progresso** — Usuário não sabe quanto falta para completar.

### 4.2 `edit.php` — Formulário de Edição

- Mesmos problemas do `create.php`
- **Não exibe histórico** do cliente (pedidos, última compra, total gasto)
- **Não exibe dados de auditoria** (quem criou, quem editou, quando)

### 4.3 `index.php` — Listagem / Visão Geral

#### ✅ Pontos Positivos
- Sidebar com seções (Visão Geral, Cadastro, Importação) — padrão SPA-like
- Busca com debounce AJAX
- Paginação dinâmica
- Importação em massa com stepper visual
- Drag & drop para upload de arquivo
- Auto-mapeamento inteligente de colunas

#### 🔴 Problemas Identificados
1. **Tabela sem colunas extras** — Não exibe cidade/estado, status, última compra.
2. **Sem exportação** — Não permite exportar clientes para CSV/Excel.
3. **Sem filtros avançados** — Apenas busca textual. Falta filtrar por cidade, estado, status, data.
4. **Sem visualização de cards** — Apenas tabela. Sistemas modernos oferecem view de cards.
5. **Sem ações em lote** — Não permite selecionar vários clientes para excluir, exportar, etc.
6. **Seção "Cadastro de Clientes" na sidebar é apenas um link** — Poderia ser o formulário inline (sem redirecionar).

---

## 5. Resumo de Gravidade

| Categoria        | Crítico (🔴) | Importante (🟡) | Sugestão (🟢) |
|------------------|:---:|:---:|:---:|
| Banco de Dados   | 7   | 4   | 2   |
| Model            | 3   | 2   | 1   |
| Controller       | 4   | 2   | 0   |
| View (UX/UI)     | 6   | 5   | 4   |
| **Total**        | **20** | **13** | **7** |

---

## 6. Conclusão

O cadastro de clientes atual é **funcional mas básico**, adequado para um MVP inicial mas **insuficiente para uso profissional**. Os principais gaps são:

1. **Estrutura de dados incompleta** — Faltam campos essenciais para operação comercial e fiscal
2. **Sem distinção PF/PJ** — Impede lógica de negócio adequada
3. **UX deficiente** — Sem máscaras, auto-preenchimento, validação em tempo real
4. **Sem integrações** — ViaCEP, consulta CNPJ, validação de documentos
5. **Sem dados de auditoria** — Impossível rastrear alterações
6. **Endereço como JSON** — Impossibilita relatórios e buscas geográficas

> **Próximo passo:** Veja o arquivo `02_PROPOSTA_CAMPOS.md` para a proposta completa de campos profissionais.
