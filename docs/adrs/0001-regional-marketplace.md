# ADR-0001: Começar como portal imobiliário regional

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

O produto será um catálogo de imóveis divulgado por imobiliárias parceiras. O lançamento será regional, com possibilidade de expansão futura.

## Decisão

Lançar primeiro em uma região delimitada e modelar os dados sem pressupor que todas as imobiliárias pertençam a uma única cidade.

## Alternativas consideradas

- **Lançamento nacional:** maior alcance potencial, mas exige mais esforço comercial e oferta inicial de imóveis.
- **Portal regional:** facilita prospecção e validação local, mas limita inicialmente o alcance.
- **SaaS de gestão imobiliária:** escopo diferente, com ferramentas operacionais mais amplas para as imobiliárias.

## Consequências

- O catálogo deve permitir filtros por cidade e bairro, mesmo que a primeira região seja pequena.
- O domínio não deve codificar a cidade inicial em nomes de tabelas, regras ou rotas.
- A expansão para novas regiões deve ser possível por dados e configuração, sem reescrever o núcleo.
- O sucesso comercial depende de conseguir imóveis e tráfego na região; a tecnologia, sozinha, não resolve o efeito de rede.
