# Checklist de Auditoria — Testes e Qualidade

## PHPUnit
- [ ] Configuração em `phpunit.xml`
- [ ] Bootstrap: `tests/bootstrap.php` com autoload e mock de sessão
- [ ] Suites definidas: Unit, Pages, Integration
- [ ] Total de tests e assertions (executar `phpunit --no-coverage`)
- [ ] Sem testes falhando
- [ ] Sem testes que verificam existência de .sql (regra copilot-instructions)

## Cobertura de Testes
- [ ] Controllers: quais têm testes, quais não
- [ ] Models: quais têm testes, quais não
- [ ] Services: quais têm testes, quais não
- [ ] Rotas: todas as pages testadas em PagesTest
- [ ] CRUD: create/read/update/delete testados por módulo
- [ ] Listar módulos sem testes

## Qualidade dos Testes
- [ ] Assertions significativas (não apenas "não dá erro")
- [ ] Testes de edge cases (null, vazio, inválido)
- [ ] Mock de banco de dados (PDO mockado)
- [ ] Testes de segurança: CSRF, auth, permissões
- [ ] Sem dependência entre testes (isolamento)

## PHPStan
- [ ] Nível configurado em `phpstan.neon`
- [ ] Total de erros pendentes
- [ ] Baseline atualizada
- [ ] Progressive: nível sendo aumentado gradualmente

## Linting
- [ ] PHP_CodeSniffer configurado (PSR-12)
- [ ] ESLint para JavaScript
- [ ] Stylelint para CSS (se aplicável)

## CI/CD
- [ ] Pipeline definida (GitHub Actions, GitLab CI, etc.)
- [ ] Stages: lint → test → build → deploy
- [ ] Deploy automatizado ou semi-automatizado
- [ ] Rollback documentado

## Scripts
- [ ] `scripts/` inventariados com propósito
- [ ] Scripts de diagnóstico vs scripts de fix
- [ ] Scripts de migração testados
- [ ] Sem scripts com credenciais hardcoded

## Métricas de Qualidade
- [ ] Ratio tests/code (ideal: 1 test file per model/controller)
- [ ] Assertions per test (ideal: >=2)
- [ ] Tempo de execução da suite (ideal: <60s)
- [ ] Zero warnings do PHPUnit
