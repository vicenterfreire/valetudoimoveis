# ADR-0014: Preparar dependências localmente e definir deploy compatível com FTP

- **Status:** Proposed
- **Data:** 2026-10-10

## Contexto

A hospedagem não permite executar Composer diretamente no servidor. O proprietário já conhece o fluxo de implantação de aplicações Symfony por FTP, mas ainda não foram confirmadas as capacidades de SSH, cron e execução de migrations.

## Decisão proposta

Resolver e instalar dependências em ambiente local compatível com PHP 8.2, executar validações e enviar por FTP/SFTP um artefato de deploy controlado. Definir um procedimento separado, documentado e seguro para mudanças de schema.

## Alternativas consideradas

- **Executar Composer no servidor:** não é possível no ambiente atual.
- **Enviar o repositório inteiro sem processo definido:** pode incluir arquivos desnecessários, expor configurações ou deixar deploys inconsistentes.
- **Artefato preparado localmente:** compatível com o hosting, desde que versões e dependências sejam reproduzíveis.

## Consequências

- Não enviar `.env` com segredos de desenvolvimento nem diretórios de cache locais indevidos.
- Confirmar se `vendor/` deve ser incluído no pacote e manter PHP/plataforma de build compatíveis com o servidor.
- Migrations devem ser executadas de forma controlada, com backup e plano de recuperação; não presumir que FTP execute comandos.
- Se não houver shell/console no servidor, definir previamente um procedimento seguro de atualização de schema, evitando endpoints públicos improvisados para executar comandos.
- Documentar configuração, permissões de arquivos, diretório público, cache e rollback.
