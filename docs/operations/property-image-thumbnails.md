# Miniaturas de fotos de imóveis — implantação

A migration `Version20261023150000` adiciona `property_images.thumbnail_path` como coluna nullable. A coluna é nullable de propósito: imagens cadastradas antes da atualização continuam funcionando pelo fallback para `file_path`.

## Antes de publicar

1. Faça backup do banco e confirme que a tabela `property_images` existe.
2. Em homologação, aplique a migration com Doctrine ou execute o SQL equivalente:

```sql
ALTER TABLE property_images ADD thumbnail_path VARCHAR(255) DEFAULT NULL;
```

3. Verifique que a coluna foi criada antes de permitir novos uploads. O uploader grava o caminho da miniatura no registro e vai falhar se a migration ainda não tiver sido aplicada.
4. Confirme que a extensão GD está habilitada e que o PHP suporta os formatos de imagem que serão aceitos (JPG, PNG e WEBP).
5. Faça upload de uma foto e confirme que os dois arquivos são criados em `public/uploads/properties/<id>/`: arquivo original otimizado e arquivo com prefixo `thumb_`.
6. Confira a listagem, os cartões, a galeria, a remoção e o comando `app:uploads:purge-orphans` em modo de simulação.

Não remova a coluna no rollback antes de decidir como preservar os caminhos de miniaturas já criados. A reversão da migration remove os caminhos armazenados, mas não apaga automaticamente os arquivos físicos; esses arquivos poderão ser tratados pela rotina de órfãos após a janela de segurança.

## Hospedagem FTP sem CLI

Se o provedor não permitir executar `php bin/console doctrine:migrations:migrate`, aplique o SQL acima no gerenciador de banco após backup. Não faça a alteração diretamente em produção sem homologação, e não execute o SQL se a coluna já existir.
