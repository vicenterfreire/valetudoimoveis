# Inventário de dados, auditoria e retenção — Casa & Costa

Revisão: 23/10/2026. Documento operacional do MVP; deve ser revisado pelo responsável pelo portal e assessoria jurídica antes da publicação definitiva. Não substitui análise jurídica de conformidade com a LGPD.

## Dados tratados e finalidade

| Grupo | Exemplos | Finalidade | Visibilidade / acesso | Retenção definida |
|---|---|---|---|---|
| Conta | nome, e-mail, hash de senha, papel, status e imobiliária vinculada | autenticação, suporte e autorização | o titular e administradores autorizados; hash nunca é exibido | enquanto a conta existir e conforme obrigações aplicáveis |
| Imóvel | descrição, preço, localização, atributos, fotos e texto alternativo | catálogo e gestão do anúncio | campos aprovados são públicos; rascunhos e gestão exigem autorização | enquanto necessário à publicação, moderação e histórico operacional |
| Logo da imobiliária | imagem convertida em PNG | identificação pública da imobiliária | público no perfil aprovado | até substituição/remoção e limpeza de órfãos |
| Eventos analíticos | ID de agência/imóvel, tipo de evento, canal e data/hora | métricas agregadas de visualização e clique de contato | painéis autorizados; sem IP, user-agent, e-mail ou conteúdo de mensagem no evento | política de 365 dias; depende de agendamento operacional do comando de limpeza |
| Auditoria | referências internas, ação, status anterior/novo e metadados allowlisted | segurança, responsabilização e investigação de alterações | administração autorizada | padrão de 730 dias; depende de execução periódica de `app:audit-logs:purge` |
| Contato externo | clique que redireciona para WhatsApp | permitir contato com a imobiliária | o conteúdo é tratado pelo serviço externo após redirecionamento | o portal não armazena o conteúdo da conversa; eventos de clique seguem retenção analítica |

## Dados privados e públicos

- Nunca tornar públicos rascunhos, anúncios pausados/arquivados, anúncios não aprovados ou anúncios de imobiliárias inativas.
- E-mail, telefone de login, hash de senha, papéis, status interno, tokens, histórico de moderação e trilhas de auditoria são dados privados.
- O anúncio público pode expor os dados de contato comercial que a imobiliária escolheu publicar, bem como atributos e localização aprovados. A morada exata deve seguir os campos explicitamente publicados pelo anúncio; não incluir dados internos do proprietário do imóvel.
- Os uploads gerados são públicos por URL porque fotos de anúncios e logos são recursos do catálogo. As operações de gerenciamento, substituição e remoção exigem autenticação, autorização da imobiliária e CSRF. Não armazenar documentos pessoais em `public/uploads`.

## Minimização de logs

- Não registrar senhas, tokens de ação, cabeçalhos de autenticação, conteúdo de formulários, texto de mensagens ou motivos livres de moderação em `UserAuditLog`.
- A auditoria registra somente ação, referências internas e metadados allowlisted. Motivos livres ficam no histórico de negócio quando necessários, e não são copiados para a trilha técnica.
- Erros de métricas registram o tipo de evento e a classe da exceção, sem serializar o objeto da exceção, SQL ou dados de conexão.
- Logs de acesso do servidor web podem registrar IP e user-agent conforme configuração do provedor; revisar a política do host e limitar acesso e retenção desses logs separadamente.

## Retenção e comandos

- Eventos analíticos: `php bin/console app:contact-events:purge --retention-days=365`. O comando aceita 30 a 3650 dias.
- Auditoria: `php bin/console app:audit-logs:purge --retention-days=730`. O comando aceita 365 a 3650 dias. A retenção maior preserva trilhas relevantes para investigação.
- Uploads órfãos: `php bin/console app:uploads:purge-orphans --min-age-hours=24` apenas simula; revise a lista. Só depois, execute `php bin/console app:uploads:purge-orphans --min-age-hours=24 --execute`.
- Os comandos não foram agendados automaticamente porque a hospedagem FTP não confirmou suporte a cron/CLI. Enquanto não houver agendamento verificado, os prazos de retenção são políticas definidas, não garantias de exclusão automática.
- Faça backup antes de limpezas em produção; teste os comandos em homologação e mantenha registros de execução sem incluir dados pessoais desnecessários.

## Consentimento de métricas

A primeira visita apresenta uma escolha explícita para permitir ou recusar métricas opcionais. Sem escolha positiva, `ContactAnalytics` não persiste eventos. A escolha é mantida na sessão, pode ser alterada na página `/privacidade` e não bloqueia a navegação ou o redirecionamento de contato. A implementação não usa cookies de publicidade nem ferramentas de terceiros para análise.

## Limitações e revisão necessária

- Validar texto, responsáveis, bases legais, canal para solicitações de titulares, termos e prazos com o responsável pelo portal antes da publicação.
- Confirmar no host real a proteção de uploads. O arquivo `.htaccess` aplica-se somente a Apache com `AllowOverride` habilitado; Nginx, IIS ou hospedagens com overrides desabilitados precisam de regra equivalente no servidor.
- Não declarar conformidade legal completa somente com a existência deste documento.
