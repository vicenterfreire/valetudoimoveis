# Ambiente e execução dos testes automatizados

## Estado inicial

- `composer.json` e `composer.lock` estão versionados e presentes no pacote.
- As dependências de desenvolvimento incluem PHPUnit 11.5, Symfony BrowserKit e o cliente de testes Symfony.
- A suíte estava vazia (apenas `tests/bootstrap.php`) antes deste lote.
- O arquivo `vendor.zip` fornecido foi extraído para este projeto e o console Symfony carrega corretamente. A suíte PHPUnit continua bloqueada neste executor porque faltam as extensões PHP `dom`, `mbstring` e `xmlwriter`; não registrar isso como uma suíte aprovada.

## Preparação local

1. Usar PHP 8.2 ou superior e instalar Composer.
2. Se `vendor/` já estiver disponível, confira se corresponde ao `composer.lock`; caso contrário, instale as dependências exatamente conforme o lock: `composer install`.
3. Para testes com banco, habilitar `pdo_sqlite`; `.env.test` aponta para `var/test.db`, nunca para o banco usado por `.env`.
4. Executar `composer test` ou `php bin/phpunit --testdox`.
5. Não usar credenciais nem banco de produção no ambiente de testes.

## Cobertura adicionada

- `tests/Security/UserCheckerTest.php`: contas inativas, usuário sem imobiliária, imobiliária bloqueada, conta ativa e administrador global.
- `tests/Security/AgencyAccessTest.php`: privilégio de responsável, funcionário sem permissão de responsável e isolamento de imóvel entre imobiliárias.
- `tests/Controller/PrivateRouteAccessTest.php`: visitante anônimo redirecionado para login ao acessar áreas administrativas e de imobiliária.
- `tests/Service/UserManagementPolicyTest.php`: impede autoalteração de conta, valida a gestão de outros usuários e protege o último administrador ativo.

## Limites atuais

Os testes funcionais HTTP e unitários foram adicionados. As dependências foram fornecidas, mas a suíte ainda precisa ser executada em PHP com as extensões exigidas pelo PHPUnit (`dom`, `mbstring`, `xmlwriter`) e, para testes com banco, `pdo_sqlite`. Testes de permissões com usuários autenticados, mutações com CSRF, mudança de papéis e ciclo completo de bloqueio/revogação de sessão exigem fixtures de banco e devem ser considerados pendentes até que sejam implementados e executados em ambiente isolado.
