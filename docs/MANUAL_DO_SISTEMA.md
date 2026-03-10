# 📘 Akti - Gestão em Produção | Manual do Sistema

> **Versão:** 1.0  
> **Atualizado em:** Março/2026  
> **Suporte:** suporte@useakti.com

---

## 📑 Índice

1. [Visão Geral](#1-visão-geral)
2. [Primeiro Acesso e Tour Guiado](#2-primeiro-acesso-e-tour-guiado)
3. [Dashboard](#3-dashboard)
4. [Pedidos](#4-pedidos)
5. [Pipeline de Produção](#5-pipeline-de-produção)
6. [Clientes](#6-clientes)
7. [Produtos](#7-produtos)
8. [Estoque](#8-estoque)
9. [Setores de Produção](#9-setores-de-produção)
10. [Financeiro](#10-financeiro)
11. [Configurações](#11-configurações)
12. [Usuários e Permissões](#12-usuários-e-permissões)
13. [Perfil do Usuário](#13-perfil-do-usuário)
14. [Limites do Plano](#14-limites-do-plano)
15. [Atalhos e Dicas](#15-atalhos-e-dicas)
16. [Perguntas Frequentes](#16-perguntas-frequentes)

---

## 1. Visão Geral

O **Akti - Gestão em Produção** é um sistema completo para gerenciar o fluxo de produção de empresas que trabalham com pedidos personalizados, confecção, manufatura e similares.

### Funcionalidades principais:
- 📋 **Gestão de Pedidos** — Crie, acompanhe e gerencie pedidos do orçamento à entrega
- 🔄 **Pipeline Visual** — Quadro Kanban para visualizar e mover pedidos entre etapas
- 👥 **Cadastro de Clientes** — Base completa com histórico de pedidos
- 📦 **Catálogo de Produtos** — Produtos com fotos, grades, categorias e dados fiscais
- 🏭 **Controle de Estoque** — Multi-armazém com entradas, saídas e transferências
- ⚙️ **Setores de Produção** — Organize a linha produtiva por setores
- � **Financeiro** — Parcelas, confirmação de pagamentos, entradas/saídas e importação OFX
- �📊 **Dashboard** — Indicadores de faturamento, pedidos e produção
- 👤 **Controle de Acesso** — Usuários com grupos e permissões granulares
- 🏢 **Multi-tenant** — Cada cliente tem seu ambiente isolado por subdomínio

---

## 2. Primeiro Acesso e Tour Guiado

### Tour Automático
Ao acessar o sistema pela **primeira vez**, um tour guiado é iniciado automaticamente. O tour apresenta:

- As áreas do menu principal
- Uma breve explicação de cada módulo
- Dicas de uso para cada funcionalidade

### Navegação do Tour
| Ação | Como fazer |
|------|-----------|
| Avançar | Botão **"Próximo"** ou tecla `→` (seta direita) |
| Voltar | Botão **"Voltar"** ou tecla `←` (seta esquerda) |
| Pular | Botão **"Pular"** ou tecla `Esc` |
| Concluir | Botão **"Concluir"** no último passo |

### Refazer o Tour
Existem **duas formas** de refazer o tour a qualquer momento:

1. **Pelo rodapé do sistema:** Clique no botão azul **"Tutorial"** (com ícone de interrogação) no rodapé da página
2. **Pelo menu do usuário:** Clique no menu **"Sair"** (canto superior direito) → **"Tour Guiado"**

### Botão de Tutorial no Rodapé
O rodapé do sistema sempre exibe dois atalhos úteis:
- 📘 **Manual** — Abre esta documentação completa dentro do sistema
- ❓ **Tutorial** — Inicia o tour guiado interativo

> 💡 O tour se adapta ao seu perfil: você só verá as áreas que tem permissão para acessar.
> O tour **navega automaticamente** entre as páginas, mostrando o contexto real de cada módulo.

---

## 3. Dashboard

O Dashboard é a tela inicial do sistema e fornece uma **visão geral** do negócio.

### Informações exibidas:
- **Resumo de Pedidos** — Total de pedidos por status (pendente, em produção, concluído)
- **Faturamento** — Valores totais e por período
- **Pipeline** — Distribuição de pedidos nas etapas de produção
- **Alertas** — Pedidos com prazo próximo ou atrasados

### Como usar:
1. Acesse clicando em **"Dashboard"** no menu ou no logo
2. Use os filtros de período para ajustar os dados exibidos
3. Clique nos cards para ir direto à área correspondente

---

## 4. Pedidos

O módulo de Pedidos é o coração do sistema. Cada pedido representa uma solicitação de um cliente.

### Criar um Pedido
1. Acesse **Pedidos** no menu
2. Clique em **"Novo Pedido"**
3. Selecione o **cliente** (ou cadastre um novo)
4. Selecione a **tabela de preço** (opcional — usa a vinculada ao cliente por padrão)
5. Adicione os **produtos** ao pedido:
   - Busque pelo nome do produto
   - Se o produto tiver grades (tamanho, cor), selecione a combinação
   - Defina a quantidade
6. Configure os detalhes:
   - **Prioridade** — Baixa, Normal, Alta ou Urgente
   - **Prazo** — Data de entrega
   - **Frete** — Retirada, Entrega ou Correios
   - **Pagamento** — Método, parcelas e status
   - **Observações internas** — Notas da equipe
   - **Observações do orçamento** — Texto que aparece para o cliente
7. Clique em **"Salvar Pedido"**

### Status do Pedido
| Status | Descrição |
|--------|-----------|
| Orçamento | Pedido inicial, aguardando aprovação do cliente |
| Pendente | Aprovado, aguardando início da produção |
| Aprovado | Confirmado, pronto para produzir |
| Em Produção | Sendo fabricado/preparado |
| Concluído | Finalizado e entregue |
| Cancelado | Cancelado pelo cliente ou pela empresa |

### Pipeline do Pedido
Cada pedido percorre etapas no pipeline de produção:
**Contato → Orçamento → Venda → Produção → Preparação → Envio → Financeiro → Concluído**

### Custos Extras
Você pode adicionar custos extras ao pedido (frete especial, urgência, etc.):
1. Na tela de edição do pedido, vá à seção **"Custos Extras"**
2. Adicione a descrição e o valor
3. O valor é somado ao total do pedido

### Link para o Cliente
Gere um link público para o cliente visualizar o orçamento/pedido:
1. Na lista de pedidos, clique no ícone de **link/compartilhar**
2. Um link único é gerado (catálogo público)
3. Envie o link ao cliente por WhatsApp, e-mail, etc.

---

## 5. Pipeline de Produção

O Pipeline é uma visão **Kanban** (quadro visual) de todos os pedidos organizados por etapa.

### Como funciona:
- Cada **coluna** representa uma etapa do processo
- Cada **card** é um pedido
- **Arraste** os cards entre colunas para mover o pedido de etapa

### Etapas padrão:
1. 📞 **Contato** — Primeiro contato com o cliente
2. 📋 **Orçamento** — Orçamento enviado/em análise
3. 💰 **Venda** — Venda confirmada
4. 🏭 **Produção** — Em fabricação
5. 📦 **Preparação** — Sendo preparado para envio
6. 🚚 **Envio** — Enviado ao cliente
7. 💵 **Financeiro** — Aguardando pagamento/confirmação
8. ✅ **Concluído** — Processo finalizado

### Metas por Etapa
O administrador pode definir metas de tempo para cada etapa nas **Configurações**. Pedidos que ultrapassam a meta ficam destacados.

### Filtros e Pesquisa
- Filtre por **prioridade**, **responsável** ou **período**
- Use a **barra de pesquisa** para encontrar pedidos específicos

---

## 6. Clientes

Gerencie sua base de clientes com todas as informações necessárias.

### Cadastrar Cliente
1. Acesse **Clientes** no menu
2. Clique em **"Novo Cliente"**
3. Preencha os dados:
   - **Nome** (obrigatório)
   - **E-mail**
   - **Telefone**
   - **Documento** (CPF/CNPJ)
   - **Endereço**
   - **Foto** do cliente
   - **Tabela de Preço** — Vincule uma tabela de preço específica
4. Clique em **"Salvar"**

### Tabela de Preço do Cliente
Ao vincular uma tabela de preço ao cliente, todos os pedidos criados para ele usarão automaticamente os preços daquela tabela. Se não houver tabela vinculada, será usado o preço padrão do produto.

### Histórico
Na tela de detalhes do cliente você pode ver todos os pedidos feitos por ele.

---

## 7. Produtos

Cadastre todos os seus produtos com informações completas.

### Cadastrar Produto
1. Acesse **Produtos** no menu
2. Clique em **"Novo Produto"**
3. Preencha os dados:
   - **Nome** (obrigatório)
   - **Descrição**
   - **Categoria** e **Subcategoria**
   - **Preço** base
   - **Fotos** — Envie múltiplas fotos (a primeira é a principal)
4. Clique em **"Salvar"**

### Fotos do Produto
- Envie até várias fotos por produto
- Formatos aceitos: JPG, PNG, GIF, WebP, BMP, SVG
- A primeira foto é definida como **imagem principal**
- Na edição, clique no radio para trocar a foto principal
- Clique no **X** para remover uma foto

### Categorias e Subcategorias
Organize os produtos em categorias (ex: Camisetas) e subcategorias (ex: Manga Longa):
1. Na tela de produtos, clique na aba **"Categorias"**
2. Adicione categorias e subcategorias
3. Ao cadastrar um produto, selecione a categoria/subcategoria

### Grades (Variações)
Grades permitem criar variações do produto (tamanho, cor, material, etc.):
1. Na aba **"Grades"** do produto, adicione tipos de grade
2. Para cada tipo, adicione valores (ex: P, M, G, GG)
3. O sistema gera automaticamente as **combinações** (ex: M/Branca, G/Preta)
4. Cada combinação pode ter preço e estoque específicos

> 💡 Grades também podem ser definidas por **categoria** ou **subcategoria** para aplicar automaticamente a todos os produtos daquela categoria.

### Dados Fiscais
Na aba **"Fiscal"** do produto, configure:
- **NCM** — Nomenclatura Comum do Mercosul
- **CEST** — Código Especificador da Substituição Tributária
- **CFOP** — Código Fiscal de Operações e Prestações
- **CST** — ICMS, PIS, COFINS, IPI
- **Alíquotas** — ICMS, IPI, PIS, COFINS
- **EAN/GTIN** — Código de barras
- **Unidade fiscal** — UN, KG, MT, etc.

### Setores de Produção
Vincule setores de produção ao produto para definir quais setores participam da fabricação:
1. Na seção de setores do produto, selecione os setores
2. Os setores vinculados são exibidos no pipeline durante a produção

### Controle de Estoque
- Ative **"Usar controle de estoque"** no cadastro do produto
- Quando ativado e há estoque disponível, o pedido pode pular a etapa de produção
- O estoque é gerenciado no módulo de **Estoque**

---

## 8. Estoque

Controle o estoque dos seus produtos em múltiplos armazéns.

### Armazéns
Crie armazéns para organizar o estoque:
1. Acesse **Estoque** no menu
2. Clique na aba **"Armazéns"**
3. Adicione armazéns (ex: Estoque Principal, Loja, Depósito)

### Movimentações
Registre entradas e saídas de estoque:
1. Na tela de estoque, selecione o **produto**
2. Escolha o **armazém**
3. Defina o tipo: **Entrada** ou **Saída**
4. Informe a **quantidade** e uma **observação**
5. Clique em **"Registrar"**

### Transferências
Transfira estoque entre armazéns:
1. Selecione o armazém de **origem** e **destino**
2. Escolha o produto e a quantidade
3. O sistema registra a saída de um e a entrada no outro

### Consulta
- Veja o saldo de estoque por produto e por armazém
- Consulte o histórico de movimentações com filtros

---

## 9. Setores de Produção

Defina os setores da sua linha de produção.

### Criar Setor
1. Acesse **Setores** no menu
2. Clique em **"Novo Setor"**
3. Defina:
   - **Nome** do setor (ex: Costura, Corte, Estamparia)
   - **Descrição**
   - **Cor** identificadora
   - **Ícone**
4. Clique em **"Salvar"**

### Vincular Setores
Você pode vincular setores a:
- **Produtos individuais** — Na tela de cadastro/edição do produto
- **Categorias** — Todos os produtos da categoria herdam os setores
- **Subcategorias** — Todos os produtos da subcategoria herdam os setores

### No Pipeline
Quando um pedido entra em produção, o sistema exibe os setores vinculados aos produtos do pedido, permitindo acompanhar o progresso setor a setor.

---

## 10. Financeiro

O módulo Financeiro controla o ciclo de pagamento dos pedidos e o livro-caixa da empresa.

### Áreas do módulo
| Área | Descrição |
|------|-----------|
| **Pagamentos** | Lista de pedidos com status de pagamento e controle de parcelas |
| **Entradas e Saídas** | Registro manual de transações, importação OFX e visualização de estornos |

### Pagamentos e Parcelas

#### Fluxo de pagamento
1. No **Pipeline** (detalhe do pedido), o operador define a forma de pagamento, parcelamento e entrada. As parcelas são geradas automaticamente.
2. Em **Financeiro > Pagamentos**, o operador visualiza todos os pedidos com seus status.
3. Ao clicar em **"Parcelas"**, pode:
   - **Registrar pagamento** — Informa data, valor pago e método
   - **Confirmar** — Valida um pagamento já registrado
   - **Estornar** — Reverte o pagamento para pendente

#### Status de pagamento
| Status | Significado |
|--------|-------------|
| **Pendente** | Nenhuma parcela paga |
| **Parcial** | Algumas parcelas pagas |
| **Pago** | Todas as parcelas confirmadas |
| **Atrasado** | Parcela vencida sem pagamento |

> 💡 O status é calculado automaticamente conforme as parcelas são pagas e confirmadas.

### Entradas e Saídas (Caixa)

A tela de **Entradas e Saídas** funciona como um livro-caixa, registrando todas as movimentações financeiras.

#### Nova Transação
1. Clique em **"Nova Transação"**
2. Escolha o tipo: **Entrada** ou **Saída**
3. A **categoria** é selecionada automaticamente:
   - Para entradas: **"Outra Entrada"** (padrão)
   - Para saídas: **"Outra Saída"** (padrão)
4. Preencha descrição, valor, data e método de pagamento
5. Clique em **"Registrar"**

#### Tipos de registro na listagem

| Tipo | Badge | Ícone | Contabiliza no saldo? |
|------|-------|-------|-----------------------|
| **Entrada** | 🟢 Verde | Seta para baixo ↓ | ✅ Sim |
| **Saída** | 🔴 Vermelho | Seta para cima ↑ | ✅ Sim |
| **Estorno** | ⚫ Cinza | Risco — | ❌ Não |
| **Registro** | ⚫ Cinza | Risco — | ❌ Não |

> ⚠️ **Estornos** são gerados automaticamente pelo sistema ao estornar uma parcela. Não é possível lançar um estorno manualmente.

> 💡 **Registros** são importações OFX no modo "apenas registro" — servem para consulta, sem impactar o saldo.

#### Importar Extrato OFX
1. Clique em **"Importar OFX"**
2. Selecione o arquivo `.ofx` exportado do seu banco
3. Escolha o modo de importação:
   - **Registro** (padrão) — As transações aparecem na lista com badge cinza e **não contabilizam** no caixa
   - **Contabilizar** — Créditos entram como **entrada** e débitos como **saída** no caixa
4. Clique em **"Importar"**

#### Cards de resumo
No topo da tela são exibidos três cards:
- **Entradas** — Total de entradas confirmadas (exclui estornos e registros)
- **Saídas** — Total de saídas confirmadas (exclui estornos e registros)
- **Saldo** — Diferença entre entradas e saídas

#### Filtros
- **Tipo** — Entradas, Saídas ou Registros
- **Categoria** — Filtra por categoria de transação
- **Mês/Ano** — Filtra por período
- **Busca** — Pesquisa por texto na tabela

---

## 11. Configurações

O módulo de Configurações permite personalizar todo o sistema.

### Dados da Empresa
- **Nome da empresa**, CNPJ, endereço, telefone, e-mail
- **Logo** — Exibida nos orçamentos e documentos
- **Informações fiscais** — Regime tributário, inscrição estadual, etc.

### Tabelas de Preço
Crie tabelas com preços diferenciados:
1. Acesse **Configurações → Tabelas de Preço**
2. Clique em **"Nova Tabela"**
3. Defina nome e descrição
4. Adicione os produtos e seus preços naquela tabela
5. A tabela pode ser marcada como **padrão**

> 💡 Vincule tabelas de preço aos clientes para aplicar preços especiais automaticamente.

### Etapas do Pipeline
Configure as etapas do pipeline de produção:
1. Acesse **Configurações → Pipeline**
2. Defina **metas de tempo** para cada etapa (em horas)
3. Configure **cores** e **ícones** das etapas
4. Ative ou desative etapas conforme seu fluxo

### Etapas de Preparação
Configure o checklist de preparação dos pedidos:
1. Acesse **Configurações → Preparação**
2. Adicione as etapas de conferência (ex: "Conferir embalagem", "Anexar nota fiscal")
3. Na tela de preparação do pedido, cada etapa pode ser marcada como concluída

### Dados Fiscais (NF-e)
Configure os dados fiscais padrão para emissão de NF-e:
- Certificado digital, série da nota, natureza da operação
- CSTs padrão, alíquotas, benefícios fiscais

### Dados de Boleto
Configure dados para geração de boletos:
- Banco, agência, conta, carteira
- Cedente, convênio, instruções

---

## 12. Usuários e Permissões

> ⚠️ **Apenas administradores** podem acessar esta área.

### Criar Usuário
1. Acesse **Usuários** no menu
2. Clique em **"Novo Usuário"**
3. Preencha:
   - **Nome**
   - **E-mail** (usado para login)
   - **Senha**
   - **Perfil** — Admin ou Funcionário
   - **Grupo** — Grupo de permissões
4. Clique em **"Salvar"**

### Perfis
| Perfil | Acesso |
|--------|--------|
| **Admin** | Acesso total a todas as áreas do sistema |
| **Funcionário** | Acesso limitado às áreas definidas pelo grupo |

### Grupos de Permissões
Crie grupos para controlar o que cada equipe pode acessar:
1. Na aba **"Grupos"**, clique em **"Novo Grupo"**
2. Defina um nome (ex: "Equipe de Produção")
3. Marque as **páginas** que o grupo pode acessar:
   - Dashboard, Pedidos, Pipeline, Clientes, Produtos, Estoque, Setores, Configurações
4. Clique em **"Salvar"**

### Exemplo de Grupos
| Grupo | Permissões |
|-------|-----------|
| Administração | Tudo |
| Produção | Dashboard, Pedidos, Pipeline, Setores |
| Vendas | Dashboard, Pedidos, Clientes, Produtos |
| Estoque | Dashboard, Estoque, Produtos |

---

## 13. Perfil do Usuário

Cada usuário pode gerenciar seus próprios dados:

1. Clique no **seu nome** no canto superior direito
2. Clique em **"Meu Perfil"**
3. Altere:
   - **Nome**
   - **E-mail**
   - **Senha** (deixe em branco para manter a atual)
4. Clique em **"Atualizar"**

> ⚠️ O perfil (admin/funcionário) e o grupo de permissões só podem ser alterados por um administrador na área de Usuários.

---

## 14. Limites do Plano

O sistema possui limites definidos pelo plano contratado:

| Recurso | Comportamento quando no limite |
|---------|-------------------------------|
| **Usuários** | Botão "Novo Usuário" desabilitado + alerta na tela |
| **Produtos** | Botão "Novo Produto" desabilitado + alerta na tela |
| **Armazéns** | Botão "Novo Armazém" desabilitado + alerta na tela |
| **Tabelas de Preço** | Botão "Nova Tabela" desabilitado + alerta na tela |
| **Setores** | Formulário de criação desabilitado + alerta na tela |

Quando o limite é atingido:
- Um **alerta amarelo** é exibido no topo da página
- Os **botões de criação** ficam desabilitados
- O sistema **bloqueia** a criação no backend por segurança

Para aumentar os limites, entre em contato com o suporte.

---

## 15. Atalhos e Dicas

### Atalhos do Tour Guiado
| Tecla | Ação |
|-------|------|
| `→` ou `Enter` | Próximo passo |
| `←` | Passo anterior |
| `Esc` | Pular tour |

### Atalhos de Teclado — Navegação Rápida

O sistema possui atalhos de teclado para navegação rápida entre as páginas principais. Os atalhos utilizam a tecla **Alt** combinada com uma letra. Pressione **Alt + K** a qualquer momento para visualizar o painel de atalhos.

> ⚠️ Os atalhos **não funcionam** quando o cursor está dentro de um campo de texto (input, textarea ou select).

| Atalho | Página de Destino |
|--------|-------------------|
| `Alt + H` | Dashboard |
| `Alt + P` | Pipeline (Kanban) |
| `Alt + O` | Pedidos |
| `Alt + N` | Novo Pedido |
| `Alt + C` | Clientes |
| `Alt + R` | Produtos |
| `Alt + E` | Estoque |
| `Alt + F` | Financeiro |
| `Alt + S` | Configurações |
| `Alt + U` | Usuários |
| `Alt + A` | Agenda de Contatos |
| `Alt + K` | Exibir painel de atalhos |

### Dicas Gerais
- 🔍 Use a **barra de pesquisa** nas listagens para encontrar registros rapidamente
- 📱 O sistema é **responsivo** — funciona em celular e tablet
- 💾 Sempre clique em **"Salvar"** para confirmar alterações
- 🔄 No Pipeline, **arraste os cards** para mover pedidos entre etapas
- 📊 O Dashboard atualiza automaticamente os indicadores
- 🖼️ Fotos de produtos aceitam **WebP** (formato moderno, menor tamanho)
- 📋 Use **observações internas** nos pedidos para comunicação entre a equipe
- 📎 Na timeline do pedido, você pode **anexar arquivos** (fotos de produção, comprovantes)

### Navegação Rápida
- Clique no **logo Akti** para voltar ao Dashboard
- Use o **menu do usuário** (canto superior direito) para acessar Perfil, Tour e Sair
- O **sino** 🔔 mostra alertas de pedidos com prazo próximo
- No **rodapé**, use o botão **"Tutorial"** para refazer o tour guiado
- No **rodapé**, use o link **"Manual"** para acessar a documentação completa

---

## 16. Perguntas Frequentes

### Como mudo minha senha?
Acesse **Perfil** (menu do usuário → Meu Perfil), preencha a nova senha e clique em Atualizar.

### Como adiciono um novo funcionário?
Acesse **Usuários → Novo Usuário**, preencha os dados, selecione o perfil "Funcionário" e o grupo de permissões.

### Como crio variações de produto (tamanhos/cores)?
Na edição do produto, acesse a aba **Grades**. Adicione tipos (ex: Tamanho, Cor) e valores (ex: P, M, G / Branca, Preta).

### Como defino preços especiais para um cliente?
1. Crie uma **Tabela de Preço** em Configurações
2. Adicione os produtos com preços especiais
3. Vincule a tabela ao **cadastro do cliente**

### Como funciona o controle de estoque?
Ative "Usar controle de estoque" no produto. Registre entradas/saídas no módulo Estoque. Pedidos com estoque disponível podem pular a produção.

### Como refaço o tour guiado?
Existem duas opções:
1. Clique no botão azul **"Tutorial"** no **rodapé** de qualquer página do sistema
2. Clique no menu **"Sair"** (canto superior direito) → **"Tour Guiado"**

### O que acontece quando atinjo o limite do plano?
Os botões de criação são desabilitados e um alerta é exibido. O sistema bloqueia a criação no backend. Para aumentar o limite, entre em contato com o suporte.

### Como gero um link de orçamento para o cliente?
Na lista de pedidos, clique no ícone de **compartilhar/link**. Um link público é gerado para o cliente visualizar o orçamento.

### Como importo um extrato bancário (OFX)?
Acesse **Financeiro > Entradas e Saídas**, clique em **"Importar OFX"**, selecione o arquivo `.ofx` do banco e escolha se quer apenas registrar (não contabiliza) ou contabilizar no caixa.

### Qual a diferença entre "Registro" e "Contabilizar" na importação OFX?
No modo **Registro**, as transações aparecem na lista com badge cinza apenas para consulta, sem afetar o saldo. No modo **Contabilizar**, créditos viram entradas e débitos viram saídas reais no caixa.

### Por que estornos aparecem em cinza com um risco?
Estornos são gerados automaticamente ao cancelar um pagamento. Eles não contam como entrada nem saída — servem apenas como registro histórico. Por isso aparecem com badge cinza e ícone de risco (—).

### Posso lançar um estorno manualmente?
Não. A categoria "Estorno de Pagamento" é interna do sistema. Para registrar um estorno, utilize a funcionalidade de estorno na tela de parcelas do pedido.

---

## 🏷️ Sobre o Akti

**Akti - Gestão em Produção** é um sistema desenvolvido para empresas que precisam gerenciar pedidos, produção e estoque de forma integrada e profissional.

- **Multi-tenant** — Cada cliente tem seu ambiente isolado
- **Seguro** — Controle de acesso por perfil e grupo
- **Escalável** — Limites configuráveis por plano
- **Responsivo** — Funciona em desktop, tablet e celular

---

*© 2026 Akti - Gestão em Produção. Todos os direitos reservados.*
