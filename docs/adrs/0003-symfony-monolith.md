# ADR-0003: Usar Symfony em um monólito com renderização server-side

- **Status:** Proposed
- **Data:** 2026-10-10

## Contexto

O proprietário já possui experiência implantando projetos Symfony na hospedagem disponível. O produto inicial é um catálogo com páginas públicas, autenticação e painéis administrativos; não há requisito atual para um frontend SPA ou API pública independente.

## Decisão proposta

Usar uma única aplicação Symfony organizada por módulos/domínios e Twig para renderizar as páginas. Introduzir JavaScript apenas onde houver necessidade real de interação.

## Alternativas consideradas

- **Symfony + Twig:** reduz componentes de deploy e complexidade de autenticação.
- **Symfony API + React/Next.js separado:** oferece separação de frontend, mas acrescenta build, deploy, CORS e gestão de sessão/token.
- **PHP sem framework:** reduz dependências, mas exigiria construir ou selecionar convenções para roteamento, segurança, validação e organização.

## Consequências

- A aplicação começa como monólito modular; microserviços não fazem parte do escopo inicial.
- Controllers devem ser finos; regras de negócio ficam em serviços de aplicação/domínio e autorização em mecanismos do Symfony.
- O framework exato e sua versão só ficam fechados depois de verificar compatibilidade com PHP 8.2 e as extensões disponíveis.
- Esta decisão deve passar a Accepted quando o proprietário confirmar explicitamente a stack Symfony + Twig para este projeto.
