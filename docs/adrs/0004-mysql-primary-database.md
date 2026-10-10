# ADR-0004: Usar MySQL como banco relacional principal

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

A hospedagem disponibiliza MySQL, e o sistema precisa armazenar imobiliárias, usuários, imóveis, imagens referenciadas, estados de publicação e histórico administrativo.

## Decisão

Usar MySQL com InnoDB como banco relacional principal, com chaves estrangeiras, índices e constraints para preservar integridade.

## Alternativas consideradas

- **PostgreSQL:** possui recursos avançados, incluindo RLS nativa, mas não corresponde ao banco escolhido/disponível.
- **MySQL/InnoDB:** atende ao modelo relacional e à hospedagem atual.
- **Dados em arquivos:** inadequado para relações, filtros e atualizações concorrentes do catálogo.

## Consequências

- A integridade não deve depender apenas de validações de formulário; usar FKs, constraints e transações quando aplicável.
- Índices devem apoiar as consultas reais do catálogo, como status, localização, tipo de anúncio, faixa de preço e agência.
- RLS nativa equivalente à do PostgreSQL não será presumida no MySQL.
- A versão exata do MySQL/MariaDB oferecida pelo provedor deve ser confirmada antes de definir migrations e funcionalidades específicas.
