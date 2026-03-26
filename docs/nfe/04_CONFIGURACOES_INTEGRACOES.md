# Configurações e Integrações — Módulo NF-e / NFC-e

**Data:** 2026-03-26  
**Referência:** `docs/nfe/01_AUDITORIA_COMPLETA.md`

---

## 1. Configurações Atuais

### 1.1 Configurações do Emitente (nfe_credentials)

| Configuração | Onde | Valor Padrão | Editável |
|-------------|------|--------------|----------|
| CNPJ | nfe_credentials | vazio | Sim (form) |
| IE | nfe_credentials | vazio | Sim (form) |
| Razão Social | nfe_credentials | vazio | Sim (form) |
| Nome Fantasia | nfe_credentials | vazio | Sim (form) |
| CRT | nfe_credentials | 1 (Simples Nacional) | Sim (form) |
| UF | nfe_credentials | RS | Sim (form) |
| Código Município | nfe_credentials | vazio | Sim (form) |
| Município | nfe_credentials | vazio | Sim (form) |
| Endereço completo | nfe_credentials | vazio | Sim (form) |
| Certificado .pfx | nfe_credentials | vazio | Upload |
| Senha certificado | nfe_credentials | criptografada | Sim (form) |
| Validade certificado | nfe_credentials | auto-detectada | Automático |
| Ambiente | nfe_credentials | homologacao | Sim (form) |
| Série NF-e | nfe_credentials | 1 | Sim (form) |
| Próximo número | nfe_credentials | 1 | Sim (form) |
| CSC ID | nfe_credentials | vazio | Sim (form) |
| CSC Token | nfe_credentials | vazio | Sim (form) |

### 1.2 Configurações em Settings (company_settings)

| Configuração | Chave | Onde Usada | Status |
|-------------|-------|-----------|--------|
| Modelo NF-e | `fiscal_modelo_nfe` | Settings view | ⚠️ Não integrado com emissão |
| Série NF-e | `fiscal_serie_nfe` | Settings view | ⚠️ Duplicado com nfe_credentials |
| Próx. Número | `fiscal_proximo_numero_nfe` | Settings view | ⚠️ Duplicado com nfe_credentials |
| Tipo Emissão | `fiscal_tipo_emissao` | Settings controller | ⚠️ Não integrado |
| Finalidade | `fiscal_finalidade` | Settings controller | ⚠️ Não integrado |

### 1.3 Configurações Hardcoded no Código

| Configuração | Arquivo | Linha | Valor | Deveria ser |
|-------------|---------|-------|-------|-------------|
| Versão NF-e | NfeXmlBuilder | ~44 | `'4.00'` | Config (ok, versão atual) |
| Modelo | NfeService | ~106 | `'55'` | Dinâmico (55/65) |
| schemes | NfeService | ~96 | `'PL_009_V4'` | Config |
| CSOSN | NfeXmlBuilder | ~158 | `'102'` | Por produto |
| PIS CST | NfeXmlBuilder | ~163 | `'99'` | Por produto |
| COFINS CST | NfeXmlBuilder | ~170 | `'99'` | Por produto |
| modFrete | NfeXmlBuilder | ~208 | `9` | Por pedido |
| idDest | NfeXmlBuilder | ~62 | `1` | Calculado |
| indPres | NfeXmlBuilder | ~66 | `1` | Por tipo venda |
| tpImp | NfeXmlBuilder | ~63 | `1` | Por modelo |
| finNFe | NfeXmlBuilder | ~65 | `1` | Por operação |
| natOp | NfeXmlBuilder | ~55 | `'VENDA DE MERCADORIA'` | Por operação |
| verProc | NfeXmlBuilder | ~68 | `'Akti 1.0'` | Config global |

---

## 2. Integrações com Outros Módulos

### 2.1 Integração com Pedidos (orders)

**Direção:** NF-e → Orders (atualização de status)

| Momento | Ação | Campos atualizados em orders |
|---------|------|------------------------------|
| Autorização | `markAuthorized()` | `nfe_id`, `nfe_status='autorizada'`, `nf_number`, `nf_access_key`, `nf_series`, `nf_status='emitida'` |
| Cancelamento | `markCancelled()` | `nfe_status='cancelada'`, `nf_status='cancelada'` |

**Direção:** Orders → NF-e (dados para emissão)

| Dado | Campo em orders | Mapeamento |
|------|----------------|------------|
| Nome cliente | `customer_name` | Direto |
| CPF/CNPJ | `customer_cpf` / `customer_cnpj` | Concatenado |
| IE | `customer_ie` | Direto |
| Endereço | `customer_address` | Direto |
| UF | `customer_state` | Mapeado para `customer_uf` |
| Valor total | `total_amount` | Direto |
| Desconto | `discount` | Direto |
| Frete | `shipping_cost` | Direto |
| Pagamento | `payment_method` | Mapeado via `mapPaymentMethod()` |
| Observações | `notes` | → `observation` |
| Itens | `Order::getItems()` | Array de itens |

**⚠️ Problemas de integração:**
- Não há validação se o pedido já tem NF-e autorizada antes de emitir outra
- Campos do cliente podem estar incompletos (endereço, CEP, município, código IBGE)
- Itens do pedido podem não ter NCM ou CFOP
- Não há campo `customer_number` (número do endereço) em orders
- Não há campo `customer_bairro` em orders
- Não há campo `customer_cod_municipio` em orders

### 2.2 Integração com Produtos (products)

**Status:** ⚠️ Indireta (via itens do pedido)

Os dados fiscais do produto (NCM, CFOP, CST, CEST) devem vir do cadastro de produtos, mas atualmente:
- NCM usa fallback `'00000000'`
- CFOP usa fallback `'5102'`
- Não há campos fiscais no cadastro de produtos

### 2.3 Integração com Clientes (customers)

**Status:** ⚠️ Indireta (via pedido)

Dados do destinatário vêm do pedido, não diretamente do cadastro de clientes. Se os dados estiverem desatualizados no pedido, a NF-e será emitida com dados incorretos.

### 2.4 Integração com Pipeline

**Status:** ❌ Sem integração

Não há integração com o pipeline de produção. Possibilidades:
- Emitir NF-e quando o pedido chegar em determinada etapa
- Bloquear avanço de etapa se NF-e não estiver emitida
- Mostrar status da NF-e na visualização do pipeline

### 2.5 Integração com Financeiro

**Status:** ❌ Sem integração

Não há integração entre:
- NF-e e módulo de pagamentos/contas a receber
- Status da NF-e e liberação de cobrança
- Cancelamento de NF-e e estorno financeiro

### 2.6 Integração com Módulo Bootloader

**Status:** ✅ Implementado

- `ModuleBootloader::isModuleEnabled('nfe')` é verificado nos construtores dos controllers
- Se o módulo NF-e não estiver habilitado, exibe mensagem de módulo desativado
- Menu condicional: `'module' => 'nfe'`

---

## 3. Configurações da Biblioteca sped-nfe

### 3.1 Config JSON passado ao Tools

```json
{
    "atualizacao": "2026-03-26 12:00:00",
    "tpAmb": 2,
    "razaosocial": "Razão Social do Tenant",
    "siglaUF": "RS",
    "cnpj": "12345678000100",
    "schemes": "PL_009_V4",
    "versao": "4.00",
    "tokenIBPT": "",
    "CSC": "CSC Token",
    "CSCid": "CSC ID"
}
```

**Campos ausentes na config que poderiam ser configurados:**
- `proxyConf` — configuração de proxy (para ambientes corporativos)
- `atualizacao` — fixo em `date()`, ok
- `tokenIBPT` — sempre vazio, deveria ter token para consulta IBPTax

### 3.2 Certificado Digital

- Formato aceito: `.pfx` ou `.p12`
- Leitura: `Certificate::readPfx($pfxContent, $password)`
- Armazenamento: `storage/certificates/{tenant_db}/certificate.pfx`
- Senha: criptografada com AES-256-CBC

---

## 4. Pontos de Integração Futuros Recomendados

### 4.1 Com o módulo de Estoque
- Baixa de estoque automática ao autorizar NF-e
- Estorno de estoque ao cancelar NF-e
- Validação de estoque antes da emissão

### 4.2 Com o módulo de Relatórios
- Relatório SPED Fiscal
- Relatório SPED Contribuições
- Relatório de NF-e por período
- Relatório de impostos (ICMS, PIS, COFINS, IPI)

### 4.3 Com Notificações
- Alerta de certificado expirando (30, 15, 7, 1 dia)
- Alerta de NF-e rejeitada
- Alerta de prazo de cancelamento expirando
- Notificação de NF-e recebida (via DistDFe)

### 4.4 Com Portal do Cliente
- Disponibilizar DANFE e XML no portal do cliente
- Histórico de NF-e do cliente

### 4.5 Com API Node.js
- Endpoint REST para consulta de NF-e
- Webhook de eventos NF-e
- Emissão via API (para integrações externas)

---

## 5. Variáveis de Ambiente Recomendadas

```env
# Chave de criptografia (para senhas de certificado)
APP_KEY=base64:CHAVE_ALEATORIA_DE_32_BYTES

# SEFAZ
NFE_DEFAULT_AMBIENTE=2
NFE_SCHEMES=PL_009_V4
NFE_VERSAO=4.00
NFE_VER_PROC=Akti 1.0

# IBPTax
IBPTAX_TOKEN=

# Proxy (se necessário)
NFE_PROXY_IP=
NFE_PROXY_PORT=
NFE_PROXY_USER=
NFE_PROXY_PASS=

# Responsável Técnico
NFE_RESP_TEC_CNPJ=
NFE_RESP_TEC_CONTATO=
NFE_RESP_TEC_EMAIL=
NFE_RESP_TEC_FONE=

# Armazenamento de XMLs
NFE_XML_STORAGE_PATH=storage/nfe
NFE_XML_STORAGE_DISK=local
```

---

## 6. Checklist de Configuração para Produção

- [ ] Certificado digital A1 válido e não expirado
- [ ] CNPJ, IE e endereço completo do emitente
- [ ] Ambiente configurado como 'producao'
- [ ] Série e número inicial corretos
- [ ] CSC configurado (se usar NFC-e)
- [ ] Biblioteca sped-nfe instalada
- [ ] Biblioteca sped-da instalada
- [ ] Conexão com SEFAZ testada e funcional
- [ ] Permissões de usuário configuradas
- [ ] Backup do banco de dados atualizado
- [ ] XMLs sendo salvos em disco (além do banco)
- [ ] Monitoramento de erros configurado
- [ ] NCM cadastrado em todos os produtos
- [ ] CFOP definido por tipo de operação
- [ ] Impostos configurados conforme regime tributário
