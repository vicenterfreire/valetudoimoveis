# ADR-0005: Isolar imobiliárias na camada de aplicação

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

O portal é multi-tenant: diversas imobiliárias compartilham a mesma aplicação e o mesmo banco. Um usuário de uma imobiliária não pode acessar ou alterar os dados privados de outra. O banco escolhido é MySQL, que não oferece RLS nativa por linha equivalente à do PostgreSQL.

## Decisão

Implementar isolamento por tenant na aplicação, usando `agency_id` nas entidades pertencentes à imobiliária, consultas com escopo obrigatório e autorização centralizada. Não chamar essa abordagem de RLS nativa.

## Alternativas consideradas

- **RLS nativa em PostgreSQL:** fornece uma camada adicional no banco, mas exige trocar o banco e cuidar do contexto de sessão/tenant.
- **Filtros manuais espalhados por controllers:** fácil de iniciar, mas vulnerável a omissões.
- **Isolamento centralizado na aplicação:** compatível com MySQL e adequado ao escopo, desde que seja aplicado sistematicamente.

## Consequências

- Repositórios devem expor métodos com escopo explícito, por exemplo `findByAgency()` e `findOneByIdAndAgency()`.
- Toda operação de leitura ou escrita privada deve verificar o tenant no backend; ocultar botões na interface não é autorização.
- A criação e alteração de entidades não deve aceitar `agency_id` arbitrário enviado pelo cliente.
- Testes de autorização devem provar que usuários da agência A não leem, alteram ou excluem registros da agência B.
- A abordagem não protege contra um comprometimento completo do processo da aplicação ou credenciais do banco; a segurança depende da correta implementação e testes.
