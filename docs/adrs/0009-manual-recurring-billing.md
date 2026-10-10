# ADR-0009: Manter a cobrança mensal fora do sistema no MVP

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

O modelo comercial previsto é uma mensalidade por imobiliária, mas a cobrança e o recebimento serão feitos pessoalmente/manual no início.

## Decisão

Não implementar checkout, gateway de pagamento, assinatura automática ou conciliação financeira no MVP. A cobrança ocorrerá fora do sistema.

## Alternativas consideradas

- **Pagamento integrado desde o lançamento:** automatiza cobrança, mas adiciona complexidade, dependências externas e requisitos operacionais.
- **Cobrança manual inicialmente:** permite validar o negócio sem investir em billing antes de haver demanda comprovada.

## Consequências

- O sistema ainda precisa de uma decisão explícita sobre o que acontece quando uma parceria deixa de pagar: bloqueio manual, lembrete ou outro procedimento.
- Não afirmar que o sistema controla pagamentos se ele não registra nem concilia transações.
- O status de acesso da imobiliária pode ser alterado manualmente pelo administrador, independentemente de qualquer status financeiro futuro.
- Se a cobrança for automatizada no futuro, criar novo ADR para gateway, estados de assinatura, webhooks, idempotência e reconciliação.
