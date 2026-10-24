# Backup e restauração — Casa & Costa

## Escopo

O backup precisa abranger **o banco MySQL/MariaDB e os arquivos enviados** (fotos de imóveis e logos). Um backup somente do banco não recupera imagens; copiar apenas a pasta de uploads não recupera metadados nem relações.

## Antes de publicar migrations

1. Confirmar qual banco e diretório de uploads são usados pelo ambiente de produção.
2. Criar backup com o painel do provedor de hospedagem ou com ferramenta administrativa aprovada.
3. Guardar uma cópia fora da hospedagem e restringir o acesso aos arquivos de backup.
4. Aplicar e validar migrations primeiro em uma cópia de homologação.
5. Registrar data, versão publicada e procedimento de rollback.

Não executar `doctrine:schema:update --force` em produção. Usar migrations revisadas, em ordem, após backup.

## Backup por linha de comando (quando disponível)

O ambiente atual pode não oferecer shell. Se houver acesso seguro a um cliente MySQL, exporte o banco com `mysqldump` usando credenciais fora do histórico do terminal e copie também o diretório completo de uploads. Não inclua senhas na linha de comando nem versiona-las no repositório.

Exemplo operacional para adaptar no ambiente autorizado:

```bash
mysqldump --single-transaction --routines --triggers --default-character-set=utf8mb4 -h HOST -u USUARIO -p BANCO > casa-costa.sql
```

O arquivo SQL contém dados pessoais e deve ser protegido como dado sensível. A opção `-p` solicita a senha interativamente.

## Ensaio de restauração

1. Criar banco vazio de homologação, separado de produção.
2. Restaurar o dump usando o painel do provedor ou cliente MySQL.
3. Restaurar os uploads no diretório correspondente, preservando nomes e permissões necessárias.
4. Configurar `DATABASE_URL`, `APP_SECRET`, domínio e caminho de uploads para homologação — nunca reutilizar segredos de produção sem necessidade.
5. Executar as migrations pendentes deliberadamente e verificar a tabela de versões.
6. Validar login, catálogo, detalhe do imóvel, fotos, painel da imobiliária, moderação, relatórios e relações com foreign keys.
7. Registrar resultado, duração e falhas; descartar com segurança as cópias temporárias após o ensaio.

**Estado:** este documento define o procedimento, mas nenhum backup ou ensaio de restauração foi executado durante esta entrega, pois não havia banco real nem credenciais do provedor disponíveis.

## Política de exclusão e integridade

- Usuários não podem ser excluídos enquanto estiverem vinculados a uma imobiliária (`RESTRICT`); desativação é a opção operacional preferida. Tokens de conta são removidos em cascata; registros de auditoria preservam o histórico e anulam a referência ao usuário excluído (`SET NULL`).
- Imobiliárias com imóveis ou usuários vinculados não podem ser excluídas (`RESTRICT`). Bloqueio é preferível à exclusão.
- Imóveis removidos fisicamente apagam fotos, histórico de status, eventos analíticos e destaques associados (`CASCADE`). A exclusão física deve ser excepcional, pois remove histórico operacional.
- Fotos dependem obrigatoriamente de um imóvel (`CASCADE`).
- As referências de ator em históricos, destaques e auditoria usam `SET NULL` para preservar os registros quando um usuário é removido.

## Slugs e índices

- `agencies.slug` e `users.email` são únicos no banco.
- `properties` é acessado por ID e slug descritivo; o slug do imóvel não é globalmente único por decisão de implementação atual, pois o ID faz parte da URL.
- `idx_property_agency_status` e `idx_property_location` agora são criados por migration para corresponder aos índices declarados no mapeamento Doctrine.
- Antes de aplicar a migration em produção, confirmar que os índices ainda não foram criados manualmente no banco. Se houver divergência de schema, reconciliar em homologação em vez de executar às cegas.
