# ValeTudo Imóveis — Guia de trabalho para agentes de IA

Este arquivo orienta qualquer IA ou pessoa que continue o desenvolvimento do ValeTudo Imóveis. Leia-o antes de editar o projeto.

## Ordem de leitura obrigatória

1. `AGENTS.md` — resumo operacional e estado atual.
2. `instrunctions.md` — regras completas de desenvolvimento acordadas (o nome do arquivo contém esse erro de grafia intencionalmente, pois já faz parte do projeto).
3. `planning.md` — checklist oficial e ordem dos tópicos.
4. O código relacionado ao tópico, suas entidades, repositórios, formulários, templates, rotas, migrations e serviços antes de alterá-los.

Use sempre o ZIP/projeto mais recente. Não substitua o projeto por uma reconstrução parcial.

## Regras que não podem ser violadas

- Preserve funcionalidades já implementadas. Inspecione o conteúdo atual antes de alterar um arquivo.
- Nunca substitua o corpo de uma função funcional por comentários, `TODO`, pseudocódigo, `NotImplementedException`, retornos artificiais ou placeholders.
- Não marque um requisito como concluído só porque existe uma tela ou uma assinatura. A lógica real precisa existir e as verificações possíveis devem ser executadas.
- Marque `[x]` em `planning.md` somente quando o requisito estiver implementado e verificado. Mantenha `[ ]` em requisitos incompletos, parcialmente implementados, não testados ou dependentes de infraestrutura ainda não configurada; documente o motivo.
- Trabalhe em lotes de dois tópicos consecutivos do checklist, respeitando a ordem real. Não invente um tópico que não exista em `planning.md`.
- Aplique autorização no servidor, proteção CSRF nas ações mutáveis, métodos HTTP apropriados e isolamento entre imobiliárias.
- Mantenha a interface em português, responsiva, acessível e consistente com a identidade visual existente.
- Ao finalizar um lote, atualize `planning.md`, revise as diferenças, execute as verificações possíveis, gere e valide um ZIP do projeto e informe limitações sem alegar testes que não foram executados.
- Não faça `git push`. Caso o repositório Git esteja disponível, não faça commit sem solicitação explícita.

## Contexto técnico

- Aplicação Symfony 7.4 / PHP `>=8.2`, Doctrine ORM 3, Twig e MySQL/MariaDB.
- O ambiente de hospedagem é restrito: deployment por FTP, PHP até 8.2 e MySQL. Não presuma Composer, shell, workers, tarefas agendadas ou extensões específicas disponíveis no host.
- Confira `composer.json`, `.env`, `config/packages/`, entidades e migrations antes de introduzir dependências ou requisitos de infraestrutura.
- Nunca publique segredos em arquivos versionados. `APP_SECRET`, credenciais de banco e credenciais SMTP precisam ser configurados fora do código público.
- O `.env` incluído no pacote usa `MAILER_DSN=null://null`. Esse transporte descarta e-mails. Antes de depender de recuperação de senha, confirmação de e-mail ou notificações, configure um `MAILER_DSN` real e `MAILER_FROM` no ambiente de execução e teste a entrega.
- Migrations devem ser revisadas e aplicadas deliberadamente; nunca recomende atualizar o schema automaticamente em produção sem backup.

## Estado da implementação desta entrega

Lote mais recente: refinamentos do **catálogo público** (filtros por área/banheiros e índices para consultas públicas), sobre a base anterior de **S3 — Upload e arquivos** e **S4 — Privacidade e auditoria**. Os itens dependentes de configuração do servidor, agendamento, revisão legal e testes em ambiente real permanecem desmarcados em `planning.md`.

- `PropertyHighlight` mantém histórico de concessões e remoções com período, responsável e motivo; migration `Version20261023120000` cria a tabela correspondente.
- O painel de moderação/administração permite conceder e remover destaque com POST + CSRF. Só imóveis publicados, aprovados e de imobiliárias ativas podem receber destaque; o período máximo é 90 dias.
- A página inicial mostra até seis destaques vigentes, ordenados pela concessão mais recente. Consultas públicas filtram anúncios indisponíveis, não aprovados ou de imobiliárias inativas; expiração é aplicada na consulta, sem depender de tarefa agendada.
- A decisão do MVP é destaque editorial gratuito e controlado manualmente. Nenhum pagamento é necessário para exibição.
- C5 está fora do escopo do MVP conforme a decisão aceita em `docs/adrs/0009-manual-recurring-billing.md`: a cobrança é manual e externa ao portal. Não implementar checkout, planos, preço, provedor ou webhook sem nova decisão comercial.
- C6 adiciona `AdminUsageReportRepository` com consultas agregadas SQL, `UsageReportController` protegido por `ROLE_ADMIN`, filtros por período/imobiliária e exportação CSV com neutralização de fórmula. A tela está em `templates/admin/reports/index.html.twig`; definições em `docs/analytics/admin-usage-metrics.md` e ADR-0016. Não foi criada migration nova.
- S1 altera o logout para POST com CSRF, explicita rotas públicas/administrativas, configura flags de cookie de sessão e cabeçalhos HTTP básicos. `UserChecker` valida conta e imobiliária tanto no pré-login quanto no pós-autenticação/restauração de sessão.
- S2 adiciona `Version20261023130000` para criar `idx_property_agency_status` e `idx_property_location`, e alinha o mapeamento de índices de `AgencyStatusHistory` às migrations existentes.
- O procedimento de backup/restauração e a política de exclusão estão em `docs/operations/database-backup-and-restore.md`; ADR-0017 documenta decisões e pendências.
- A limitação de tentativas de login permanece pendente porque `symfony/rate-limiter` não está declarado em `composer.json`/`composer.lock`. Não simular rate limiting somente com sessão; uma inclusão de dependência deve ser feita como alteração explícita e validada em ambiente com Composer.
- S3 fortalece `PropertyImageUploader` e `AgencyLogoUploader`, adiciona regras `.htaccess` para fotos/logos e inclui `app:uploads:purge-orphans`, seguro por padrão (simulação; `--execute` para apagar). Os uploads públicos continuam públicos por URL, mas o gerenciamento exige autorização; documentos privados não devem ser guardados em `public/uploads`.
- S4 adiciona `/privacidade`, consentimento explícito para métricas opcionais, minimização de logs, auditoria de ações sensíveis, `app:audit-logs:purge` e inventário em `docs/privacy/data-inventory-and-retention.md`. Retenção alvo: 365 dias para analytics e 730 dias para auditoria, mas depende de execução periódica.
- `.htaccess` só protege se o servidor for Apache com `AllowOverride` habilitado. Confirmar configuração real ou adicionar regras equivalentes em Nginx/IIS antes de produção.
- O texto de privacidade e o consentimento precisam de revisão do responsável pelo portal/assessoria jurídica; não declarar conformidade LGPD apenas com esses artefatos.

- T1 padroniza a configuração PHPUnit em `phpunit.xml.dist`, adiciona `composer test`, isola `DATABASE_URL` de teste em `.env.test` e documenta o ambiente em `docs/testing/automated-tests.md`.
- T2 adiciona testes de unidade para `UserChecker`, `AgencyAccess` e `UserManagementPolicy`, além de testes HTTP para redirecionamento de visitantes anônimos. O `vendor.zip` fornecido foi extraído e o console Symfony executa, mas PHPUnit não inicia neste executor porque faltam as extensões PHP `dom`, `mbstring` e `xmlwriter`. Os itens T1/T2 permanecem desmarcados até execução da suíte e complementação dos testes HTTP autenticados/CSRF.

## Atualização do catálogo público (2026-10-23)

- `HomeController` agora valida `minArea`/`maxArea` (0 a 1.000.000 m², até duas casas decimais), a relação entre os limites e o filtro de banheiros (0 a 20), sem remover filtros já existentes.
- `PropertyRepository` aplica os filtros de área e banheiros à consulta pública; os controles e chips de filtros ativos estão em `templates/public/home.html.twig`.
- Migration `Version20261023140000` adiciona índices compostos para listagem pública por status/data e ordenação por preço. Revisar/aplicar a migration em homologação com backup antes de produção; desempenho ainda precisa ser medido com dados reais.
- A persistência de eventos de visualização/contato já existe por meio de `ContactEvent`, `ContactEventRepository`, `ContactAnalytics` e migration `Version20261022100000`, condicionada ao consentimento de métricas.
- O usuário pediu para adiar testes automatizados e priorizar a conclusão funcional. Não executar nem marcar os itens T1–T5 como aprovados sem nova solicitação; registrar apenas verificações estáticas feitas.

## Atualização de implantação e onboarding (2026-10-24)

- O painel administrativo agora permite criar o primeiro `ROLE_AGENCY_MANAGER` para uma imobiliária sem gestor, com CSRF, validação de nome/e-mail, unicidade de e-mail, auditoria e senha aleatória não revelada. O gestor define a senha pelo fluxo de redefinição de token de uso único. Para permitir preparação de acesso durante análise, a definição de senha aceita agência pendente; a autenticação continua bloqueada por `UserChecker` até a agência ficar ativa.
- O fluxo de onboarding depende de SMTP real: `MAILER_DSN=null://null` descarta e-mails. O controller bloqueia a criação quando `MAILER_DSN` usa `null://null`, evitando criar uma conta sem meio de definir a senha; erros reais de transporte ainda precisam ser tratados e a entrega deve ser validada no host.
- `.env` agora usa `APP_ENV=prod` e `APP_DEBUG=0` por padrão; `APP_SECRET` segue vazio deliberadamente até configuração segura. `.env.prod.example` e `docs/operations/ftp-deployment.md` documentam configuração de produção e deploy FTP. Nunca usar a raiz do projeto como document root; use `public/`, segredos únicos e `APP_ENV=prod`/`APP_DEBUG=0`. `.env.dev` não deve ser enviado ao host.
- `public/.htaccess` agora contém regras de front controller, bloqueio básico de arquivos ocultos/sensíveis e cabeçalhos; as regras de uploads foram endurecidas. Isso só tem efeito em Apache com `mod_rewrite`/`AllowOverride` compatíveis. Em Nginx/IIS, configure regras equivalentes com o provedor.
- Não declarar o deploy pronto até verificar SMTP, URL amigável e não execução de scripts em uploads no ambiente real.

## Verificações e limitações conhecidas

- Execute `php -l` sobre todos os arquivos PHP de `src/` e `migrations/` e valide a integridade do ZIP final.
- Esta entrega inclui o `vendor/` fornecido. A validação de consultas Doctrine e testes de integração contra banco ainda exige drivers PDO e um banco de teste configurado; PHPUnit exige `dom`, `mbstring` e `xmlwriter`, ausentes no executor atual.
- As migrations `Version20261023120000` e `Version20261023130000` precisam ser revisadas/aplicadas em homologação antes da produção, sempre com backup; confirme que os índices novos não foram criados manualmente antes de aplicar.
- O logout usa POST + CSRF; a configuração de cookies/cabeçalhos precisa ser verificada por HTTPS no host real.
- O mailer segue como `null://null`; recuperação de senha e confirmação de e-mail dependem de configuração real do ambiente.
- A lista administrativa e a fila de moderação ainda possuem limite de resultados sem paginação navegável.
- Relatórios C6, autenticação e SQL precisam de testes de integração contra MySQL e renderização Twig real. O filtro de imobiliárias carrega até 500 opções iniciais e acrescenta a imobiliária já selecionada quando ela estiver fora desse limite.
- A retenção de eventos tem comando com padrão de 365 dias, mas o agendamento periódico depende do suporte da hospedagem e não foi configurado automaticamente.

## Próximo trabalho

O usuário pediu explicitamente para adiar os testes automatizados e priorizar a conclusão funcional até 2026-10-24. A sequência de trabalho atual deve seguir os painéis funcionais já definidos em `planning.md` (B1/B2 concluídos nesta entrega; prosseguir para painel administrativo e interface), sem marcar itens T1–T5 como aprovados. Antes de editar, releia `AGENTS.md`, `instrunctions.md`, `planning.md` e o código relacionado.

## Atualização — painel da imobiliária

- O formulário de imóveis usa seções para informações, características, valores e localização, preservando os campos Symfony e seus erros de validação.
- `PropertyImage` possui `thumbnailPath` nullable. `PropertyImageUploader` gera miniaturas de até 480 px para novos uploads, além do arquivo principal otimizado; `Version20261023150000` cria a coluna. Fotos antigas sem miniatura usam fallback para o arquivo principal.
- Listagens públicas, galeria secundária e painéis usam miniaturas quando disponíveis. A imagem principal da página de detalhes continua em resolução maior.
- A rotina `app:uploads:purge-orphans` considera miniaturas e logos referenciados e mantém simulação como padrão.
- O envio de fotos exibe progresso percentual por XHR. Validar fluxo e flash messages no navegador/hosting; testes automatizados foram adiados a pedido do usuário.
- Antes de produção, revisar e aplicar a migration `Version20261023150000` em homologação com backup; para hosting FTP sem CLI, consultar `docs/operations/property-image-thumbnails.md`. Confirmar GD e formatos de imagem suportados no PHP da hospedagem.

## Atualização — painel administrativo C1/C2 (2026-10-23)

- `AgencyAdminController`/`AgencyRepository` e `UserAdminController`/`UserRepository` agora oferecem paginação real de 25 registros, contagem total e navegação anterior/próxima, preservando filtros na URL.
- A filtragem global de usuários por papel é feita no banco usando correspondência do token JSON em `roles`, evitando carregar até 1.000 usuários em PHP e filtrar depois. O banco de produção esperado é MySQL/MariaDB; validar as consultas no banco de homologação antes do deploy.
- Os templates administrativos de imobiliárias e usuários exibem contagem, página atual e navegação responsiva. Nenhuma ação administrativa foi convertida para GET; ações mutáveis continuam POST + CSRF.
- Foram executados `php -l` nos arquivos alterados, `lint:twig`, `lint:container` e `debug:router`. Não foram executados testes automatizados nem consultas de integração contra banco real, conforme pedido do usuário.
- Notificações de aprovação/rejeição e entrega de recuperação de senha seguem pendentes até configurar `MAILER_DSN` e `MAILER_FROM` reais e validar entrega. Não declarar essas notificações como testadas.
- A Fase 3 do planejamento foi marcada como funcionalmente implementada; isso não equivale a homologação ou prontidão de produção. Permanecem pendentes a configuração do mailer, aplicação deliberada das migrations e smoke tests na hospedagem.

## Atualização — paginação da moderação C3 (2026-10-23)

- A fila administrativa de anúncios agora tem paginação de 25 registros, contagem total e links que preservam todos os filtros atuais.
- `PropertyRepository::findForModeration()` e `countForModeration()` compartilham a mesma construção de filtros. A consulta da listagem não junta a coleção de imagens porque a tabela não exibe fotos; isso evita duplicação de linhas SQL e páginas incompletas quando um anúncio tem muitas fotos.
- C4 (destaques editoriais) já oferece concessão/remoção com POST + CSRF, motivo, período máximo de 90 dias, elegibilidade e auditoria; a decisão comercial permanece gratuita/manual no MVP.

## Atualização — cadastro do primeiro gestor (2026-10-24)

- O formulário administrativo do primeiro gestor pede senha inicial e confirmação; valida mínimo de 12 caracteres e armazena somente o hash via `UserPasswordHasherInterface`. Não depende do mailer nem envia link de definição de senha. O administrador deve compartilhar as credenciais por canal seguro e orientar a troca da senha após o primeiro acesso.
- A listagem de imobiliárias usa classes `button`/`button secondary` nos links de edição e cadastro do primeiro gestor para apresentá-los visualmente como botões.
- A validação automatizada de ponta a ponta e o fluxo real de login ainda precisam ser confirmados no ambiente da aplicação.
