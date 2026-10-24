# ADR-0016: Relatórios globais agregados e retenção de eventos

- **Status:** Accepted
- **Data:** 2026-10-23

## Contexto

A administração precisa acompanhar o estado do catálogo e a evolução de anúncios e contatos, sem expor dados pessoais desnecessários e sem carregar todas as entidades para a memória.

## Decisão

- Disponibilizar relatórios apenas para `ROLE_ADMIN`.
- Consultar contagens e séries temporais com agregações no MySQL.
- Permitir período de até 366 dias e filtro opcional de imobiliária para usuários, imóveis e séries temporais; manter o total de imobiliárias por status global.
- Exportar apenas agregados em CSV, com neutralização de conteúdo que possa ser interpretado como fórmula e cabeçalhos UTF-8.
- Manter retenção padrão de 365 dias para eventos analíticos, executada pelo comando existente `app:contact-events:purge`; o agendamento depende do ambiente de hospedagem.

## Consequências

- Os números de visualizações e contatos são eventos registrados, não métricas de visitantes únicos nem conversões comerciais confirmadas.
- A evolução de anúncios é derivada da data de criação dos imóveis existentes.
- Testes de integração devem confirmar nomes de colunas, SQL e comportamento em MySQL antes da implantação.
