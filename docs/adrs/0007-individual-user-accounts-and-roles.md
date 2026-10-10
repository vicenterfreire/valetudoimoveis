# ADR-0007: Usar contas individuais e papéis por usuário

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

Cada imobiliária poderá cadastrar funcionários para operar o painel. Contas compartilhadas dificultam revogação de acesso, atribuição de responsabilidade e auditoria.

## Decisão

Cada pessoa terá sua própria conta, vinculada à imobiliária, com permissões definidas por papel. O modelo inicial prevê administrador global, gestor da imobiliária e funcionário.

## Alternativas consideradas

- **Uma credencial compartilhada por imobiliária:** simples, mas impede revogar uma pessoa individualmente e dificulta auditoria.
- **Contas individuais com papéis:** melhora segurança e responsabilização, com custo moderado de gestão de usuários.

## Consequências

- `users` referencia `agency_id`; a relação pode ser nula para um administrador global, se essa for a modelagem escolhida.
- O gestor poderá administrar usuários apenas da própria imobiliária.
- Funcionários terão permissões limitadas às operações autorizadas.
- A desativação de uma conta revoga o acesso dessa pessoa sem afetar a empresa ou os imóveis.
- A matriz exata de permissões, a política de e-mail único e o processo de convite/recuperação de senha ainda precisam ser especificados.
- Senhas devem ser armazenadas com o mecanismo de hash seguro suportado pelo PHP/Symfony, nunca em texto puro.
