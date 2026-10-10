# ADR-0006: Fazer a imobiliária ser dona dos anúncios

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

Funcionários podem entrar ou sair da imobiliária. Os anúncios, porém, continuam pertencendo à empresa e não devem desaparecer nem perder sua propriedade quando a conta de um funcionário for desativada.

## Decisão

Cada imóvel terá uma relação obrigatória com `agency_id`. O sistema pode registrar separadamente qual usuário criou ou atualizou o anúncio, mas a propriedade e o escopo de autorização pertencem à imobiliária.

## Alternativas consideradas

- **Imóvel pertencente ao usuário que o cadastrou:** cria problemas ao desativar ou remover funcionários.
- **Imóvel pertencente à imobiliária:** preserva continuidade operacional e representa a responsabilidade comercial do anúncio.

## Consequências

- `properties.agency_id` será uma FK para a imobiliária.
- O usuário criador, se registrado, será metadado/auditoria, não o dono do anúncio.
- A desativação de um funcionário não apaga nem transfere anúncios.
- A exclusão de imobiliárias não deve ser física e automática; bloqueio/arquivamento preserva histórico.
