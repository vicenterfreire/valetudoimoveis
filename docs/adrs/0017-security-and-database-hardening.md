# ADR-0017 — Reforço de autenticação, sessão e consistência do schema

- **Status:** Aceito para o MVP; validar em homologação antes da produção.
- **Data:** 2026-10-23

## Contexto

A revisão S1/S2 encontrou logout exposto como GET, ausência de flags explícitas para cookies de sessão, verificação pós-autenticação vazia e dois índices de consulta declarados no mapeamento Doctrine mas ausentes das migrations versionadas.

## Decisões

1. O logout passa a usar POST com token CSRF do firewall.
2. Cookies de sessão usam `HttpOnly`, `SameSite=Lax` e `Secure=auto`.
3. O `UserChecker` valida o estado tanto no pré-login quanto no pós-autenticação/restauração do usuário, bloqueando contas desativadas e usuários associados a imobiliárias não ativas.
4. As respostas recebem cabeçalhos de segurança básicos; HSTS só é enviado quando a requisição é reconhecida como HTTPS.
5. Uma migration versionada cria os índices `idx_property_agency_status` e `idx_property_location` já declarados na entidade `Property`.
6. A exclusão física segue as regras atuais de foreign keys; para contas e imobiliárias, desativação/bloqueio é preferível à exclusão.
7. O procedimento de backup/restauração está documentado, mas precisa de ensaio real no ambiente de homologação.

## Limitações aceitas / pendências

- Limitação de tentativas de login não foi adicionada nesta entrega porque `symfony/rate-limiter` não está declarado no `composer.lock` e o ambiente não dispõe de dependências instaladas para validar uma nova integração. Não simular proteção com contador somente em sessão.
- A comparação com o schema real e a restauração de backup exigem acesso ao banco do ambiente e continuam pendentes.
- Os slugs de imóveis permanecem descritivos e não únicos globalmente; as URLs incluem o ID. `agencies.slug` e `users.email` têm restrição única no banco.
- O HTTPS deve ser habilitado no provedor; `cookie_secure: auto` e HSTS não substituem configuração TLS. Caso exista proxy reverso, revisar `trusted_proxies` antes de confiar no reconhecimento de HTTPS.
