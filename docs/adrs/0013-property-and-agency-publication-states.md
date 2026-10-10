# ADR-0013: Separar status da imobiliária e status do anúncio

- **Status:** Proposed
- **Data:** 2026-10-10

## Contexto

Uma imobiliária pode estar ativa enquanto um imóvel específico está pausado, vendido, alugado ou aguardando revisão. Bloquear uma empresa também deve ocultar seus anúncios sem apagar os registros.

## Decisão proposta

Modelar separadamente o estado da imobiliária e o estado do anúncio. A visibilidade pública de um imóvel depende de ambos: a imobiliária precisa estar ativa e o anúncio precisa estar em um estado publicável.

## Alternativas consideradas

- **Um único status compartilhado:** mistura estado comercial da empresa com ciclo de vida de cada anúncio.
- **Estados separados:** representa melhor o domínio e evita transições ambíguas.

## Consequências

- O estado de agência não deve substituir o estado do anúncio.
- Exemplos possíveis para anúncios: draft, published, paused, sold/rented, archived; a lista final precisa ser definida antes da implementação.
- A consulta pública deve excluir anúncios de imobiliárias bloqueadas, mesmo que o status próprio do anúncio seja published.
- Ainda é necessário decidir se reativar uma imobiliária torna seus anúncios publicados visíveis imediatamente ou se exige revisão/republicação.
