# ADR-0008: Administrador cadastra e bloqueia imobiliárias

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

No início, a aquisição de parceiros será conduzida pessoalmente pelo administrador. Não é necessário permitir que qualquer empresa se cadastre e publique sem aprovação.

## Decisão

O administrador do portal cadastra as imobiliárias e pode ativar, bloquear e reativar seus acessos. O bloqueio será aplicado no backend, não apenas na interface.

## Alternativas consideradas

- **Cadastro aberto com revisão posterior:** reduz atrito, mas permite entrada de empresas não verificadas.
- **Cadastro administrado e aprovação controlada:** dá controle comercial inicial e reduz abuso.
- **Bloqueio apenas visual:** insuficiente, pois sessões e endpoints poderiam continuar acessíveis.

## Consequências

- Uma imobiliária bloqueada não poderá autenticar-se ou executar operações privadas.
- Seus anúncios não aparecerão no catálogo público enquanto o bloqueio estiver ativo.
- Os dados e anúncios serão preservados, não apagados.
- A operação de bloqueio deve invalidar ou negar sessões subsequentes; o backend deve verificar o estado da agência nas requisições protegidas.
- Registrar autor, data, estado anterior/novo e motivo do bloqueio é recomendado, mas o desenho de auditoria permanece Proposed no ADR-0012.
- A política de reativação e eventual necessidade de republicar/revisar anúncios permanece pendente.
