# ADR-0012: Auditar operações críticas e testar o isolamento entre imobiliárias

- **Status:** Proposed
- **Data:** 2026-10-10

## Contexto

O administrador pode bloquear imobiliárias e gestores podem administrar funcionários. A aplicação compartilha dados entre tenants, portanto erros de autorização podem expor ou alterar dados de outra empresa.

## Decisão proposta

Registrar operações críticas de administração e criar testes automatizados de autorização multi-tenant. A auditoria deve incluir ator, ação, alvo, data/hora e motivo quando aplicável, sem registrar senhas, tokens ou dados sensíveis desnecessários.

## Alternativas consideradas

- **Sem auditoria e apenas testes felizes:** menor esforço inicial, mas dificulta investigação de incidentes e deixa falhas de isolamento passarem.
- **Auditoria seletiva e testes negativos:** esforço moderado e proteção proporcional aos riscos do portal.

## Consequências

- Testar leitura, edição, exclusão, upload e operações de equipe entre duas imobiliárias distintas.
- Testar usuário de imobiliária bloqueada e funcionário desativado.
- Registrar bloqueio/reativação de empresa e alteração de permissões.
- Definir retenção, acesso e proteção dos registros de auditoria.
- Evitar logar dados pessoais ou credenciais sem necessidade.
