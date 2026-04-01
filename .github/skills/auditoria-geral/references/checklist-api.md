# Checklist de Auditoria — API e Integrações

## API Node.js (Express)
- [ ] Inventariar routes com métodos HTTP
- [ ] Sequelize models mapeados
- [ ] Autenticação: JWT com claims válidos (iss, exp, sub)
- [ ] CORS: origins específicos configurados
- [ ] Rate limiting por endpoint
- [ ] Input validation (express-validator ou similar)
- [ ] Error handling centralizado (middleware)
- [ ] Logging estruturado
- [ ] Health check endpoint

## Payment Gateways
- [ ] Strategy pattern implementado (interface comum)
- [ ] Gateways implementados: listar
- [ ] Tokenização de cartão (nunca armazenar PAN)
- [ ] Webhook com validação de signature
- [ ] Idempotência em cobranças
- [ ] Logs de transação (sem dados sensíveis)
- [ ] Sandbox/produção configurável por tenant

## NF-e / NFC-e
- [ ] Services inventariados
- [ ] Certificado digital: leitura segura, expiração monitorada
- [ ] Comunicação SEFAZ: HTTPS com certificado client
- [ ] Tratamento de rejeições SEFAZ
- [ ] Armazenamento de XMLs (autorizado, cancelamento, CCe)
- [ ] Contingência offline
- [ ] DANFE/DANFCE geração (PDF)

## Webhooks (recebimento)
- [ ] Validação de origin/signature
- [ ] Idempotência (não processar duplicatas)
- [ ] Retry handling (retorno 200 rápido, processamento async)
- [ ] Log de payloads recebidos

## Integrações Externas
- [ ] CEP (ViaCEP): tratamento de timeout
- [ ] CNPJ (ReceitaWS): rate limiting
- [ ] Email/SMTP: credenciais em config, não hardcoded
- [ ] Storage externo (S3, etc.): se aplicável
