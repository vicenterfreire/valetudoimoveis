# ADR-0018 — Privacidade, métricas opcionais e retenção

- **Status:** aceito para o MVP, sujeito à revisão jurídica e operacional.
- **Data:** 2026-10-23.

## Contexto

O portal registra contagens de visualizações/cliques de contato e precisa limitar dados pessoais em logs. A hospedagem tem restrições de FTP e não confirmou execução agendada de comandos.

## Decisão

1. Métricas opcionais exigem escolha positiva na sessão; ausência de escolha ou recusa não persiste eventos analíticos.
2. Eventos analíticos não armazenam IP, user-agent, e-mail nem conteúdo de mensagens. Logs de falha não serializam objetos de exceção.
3. Operações sensíveis de usuários, imobiliárias, moderação, destaques e arquivos geram registros de auditoria com metadados allowlisted.
4. Política-alvo de retenção: 365 dias para eventos analíticos e 730 dias para auditoria. Comandos de limpeza são explícitos; nenhum agendamento é presumido.
5. Arquivos órfãos só podem ser apagados depois de 24 horas e o comando funciona em modo de simulação por padrão.
6. Uploads são públicos por URL apenas porque representam fotos públicas de anúncios ou logos; documentos pessoais não devem ser armazenados nessa pasta.

## Consequências

- Métricas não são coletadas até consentimento explícito.
- O administrador da hospedagem precisa configurar e monitorar os comandos de retenção, se a infraestrutura permitir.
- `.htaccess` protege em Apache quando habilitado; outros servidores precisam de configuração equivalente.
- O aviso público e este inventário precisam de revisão legal e atualização conforme os processos reais do portal.
