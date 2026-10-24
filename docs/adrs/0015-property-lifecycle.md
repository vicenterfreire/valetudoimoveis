# ADR 0015 — Ciclo de vida e moderação de anúncios

- **Status:** Aceito
- **Data:** 2026-10-21

## Contexto

O anúncio precisa distinguir a disponibilidade comercial da decisão de moderação do portal. Um imóvel pode estar marcado como publicado pela imobiliária e continuar fora do catálogo até ser aprovado pela administração.

## Decisões

- O estado comercial é independente do estado de moderação.
- Novos imóveis começam como `draft` comercial e `pending` na moderação.
- Registros existentes recebem `approved` na nova coluna durante a migration para preservar a visibilidade atual dos anúncios já publicados.
- O catálogo público, a página do imóvel, os similares e o sitemap só incluem anúncios comercialmente publicados, aprovados e vinculados a imobiliárias ativas.
- Para publicar, o preço deve ser maior que zero, a descrição deve conter ao menos 30 caracteres, cidade e UF devem estar preenchidas corretamente e deve existir pelo menos uma foto.
- Transições comerciais permitidas:
  - Rascunho → Publicado ou Arquivado.
  - Publicado → Pausado, Vendido, Alugado ou Arquivado.
  - Pausado → Publicado, Vendido, Alugado ou Arquivado.
  - Vendido/Alugado → Arquivado.
  - Arquivado → Rascunho.
- Arquivar ou restaurar um anúncio exige papel de responsável pela imobiliária ou administrador global; o arquivamento exige motivo.
- `publishedAt` registra a publicação mais recente: é definido na transição para Publicado, preservado ao pausar e atualizado quando o anúncio é republicado.
- Cada transição comercial é registrada com ator, data, status anterior, novo status e motivo opcional. A criação do imóvel também é registrada como entrada inicial do histórico.
- A moderação possui estados independentes: Aguardando moderação, Aprovado, Reprovado e Ajustes solicitados. Ações de moderação administrativa serão implementadas no tópico próprio do painel administrativo.

## Consequências

- Um anúncio não aparece publicamente apenas porque a imobiliária marcou o estado comercial como Publicado.
- A migration deve ser revisada e executada antes do deploy.
- O mínimo de uma foto é aplicado no momento da publicação, não no salvamento do rascunho.
