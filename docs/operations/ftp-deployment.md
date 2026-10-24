# Implantação por FTP — Casa & Costa

## Antes do envio

1. Configure no painel do provedor o document root do domínio para a pasta `public/` do projeto. **Nunca** use a raiz do repositório como diretório público.
2. Confirme que a hospedagem atende aos requisitos de `composer.json`, em especial PHP 8.2 ou superior, extensões necessárias, PDO MySQL e GD com suporte aos formatos de imagem aceitos.
3. Envie o código e o `vendor/` construído localmente. Não dependa de Composer, CLI, workers ou cron no servidor sem confirmar que o plano os oferece.
4. Não envie `.env.dev`, `.env.local`, `.env.local.php`, credenciais, dumps de banco, arquivos de teste ou arquivos de desenvolvimento. O pacote de entrega não inclui `.env.dev`.
5. O `.env` do pacote usa `APP_ENV=prod` e `APP_DEBUG=0` por padrão para evitar publicar acidentalmente o modo de desenvolvimento. Antes de iniciar, configure variáveis de ambiente no painel do host. Se usar arquivos Dotenv, crie `.env.local` com `APP_ENV=prod`, `APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`, `MAILER_FROM` e `DEFAULT_URI` reais; ou defina `APP_ENV=prod` no ambiente do host e use `.env.prod.local`. Mantenha os arquivos com permissões restritas e fora da raiz pública. Não deixe `APP_SECRET` vazio. Use `.env.prod.example` apenas como modelo; não copie os valores de exemplo sem substituí-los.

## Configuração obrigatória de produção

- `APP_ENV=prod` e `APP_DEBUG=0`.
- `APP_SECRET` forte, aleatório, exclusivo desta instalação e diferente de qualquer segredo de desenvolvimento.
- `DATABASE_URL` com credenciais reais e `serverVersion` correspondente ao MySQL/MariaDB do host.
- `MAILER_DSN` para um transporte SMTP real e `MAILER_FROM` válido. `null://null` descarta e-mails e não pode ser usado se o portal precisa enviar links de definição/recuperação de senha ou confirmação de e-mail.
- `DEFAULT_URI` com a URL HTTPS canônica do portal.
- HTTPS habilitado e redirecionamento HTTP→HTTPS configurado no provedor.

Não coloque credenciais em arquivos públicos nem no repositório. Se o provedor não permite variáveis de ambiente e não permite manter `.env.local`/`.env.prod.local` fora do document root, solicite orientação ao suporte antes de publicar. Para desenvolvimento local, configure `APP_ENV=dev` em `.env.local` ou no ambiente local; não distribua `.env.dev`.

## Apache

O arquivo `public/.htaccess` contém as regras do front controller Symfony, bloqueia acesso a arquivos ocultos e define cabeçalhos de segurança básicos. Confirme que `mod_rewrite` está habilitado e que `AllowOverride` permite as diretivas usadas. As regras de `public/uploads/**/.htaccess` tentam impedir a execução de scripts; valide a resposta no servidor real.

## Nginx / IIS ou Apache sem overrides

Arquivos `.htaccess` não são aplicados nesses cenários. Peça ao provedor regras equivalentes para:

- usar `public/` como raiz pública e encaminhar rotas não existentes para `public/index.php`;
- negar arquivos ocultos e arquivos de configuração/documentação;
- impedir execução de PHP/CGI e outros scripts em `public/uploads/`;
- servir uploads apenas como conteúdo estático e adicionar `X-Content-Type-Options: nosniff`.

Não considere o bloqueio de uploads verificado até testar no host que uma requisição a um arquivo de script de teste em `public/uploads/` é negada/não executada. Não envie um script funcional para esse teste.

## Banco de dados e pós-publicação

- Faça backup antes de migrations e aplique-as deliberadamente em homologação antes de produção.
- Garanta permissões de escrita somente onde necessário (`var/` e pastas de upload).
- Teste login/logout, acesso de administrador e imobiliária, criação de gestor, definição/recuperação de senha, upload e exibição de imagens, URLs amigáveis e páginas de erro.
- Verifique a entrega real de e-mails, expiração e uso único dos links.
- A implantação não está validada só porque o ZIP foi extraído: documente a configuração real do servidor e os resultados dos testes de homologação.
