# Plano de conclusão — Portal imobiliário Casa & Costa

> **Objetivo:** concluir as funcionalidades do catálogo público, do painel da imobiliária e do painel administrativo, preservando a experiência visual elegante, responsiva e intuitiva já estabelecida.
>
> **Importante:** esta revisão se baseia no código-fonte disponibilizado e na análise estática dos arquivos acessíveis. Como não foram disponibilizados todos os arquivos de configuração, migrations e testes, alguns itens precisam ser confirmados antes de serem considerados concluídos.

## Legenda

- [x] Implementação identificada no código analisado.
- [ ] Implementação pendente ou que precisa ser revisada.
- **P0:** prioridade crítica, segurança ou consistência.
- **P1:** requisito central para concluir o produto.
- **P2:** melhoria ou funcionalidade dependente de decisão de produto.

---

# 1. Diagnóstico geral

| Área                  | Situação                  | Principais lacunas                                                             |
| --------------------- | ------------------------- | ------------------------------------------------------------------------------ |
| Catálogo público      | Implementado com refinamentos pendentes | Busca, paginação, ordenação, filtros de quartos/banheiros/vagas/área e preço; homologação visual ainda pendente |
| Painel da imobiliária | Parcialmente implementado | Perfil público editável, organização de fotos, métricas e gestão de contatos   |
| Painel administrativo | Parcialmente implementado | Aprovação de imobiliárias, usuários globais, moderação e relatórios            |
| Destaques             | Pendente                  | Regras de destaque, expiração e exibição pública                               |
| Planos e assinaturas  | Pendente/condicional      | Decisão sobre monetização, planos, pagamentos e limites                        |
| Segurança             | Revisão necessária        | Contas inativas, isolamento de dados, uploads e autorização                    |
| Interface             | Base existente            | Testes de responsividade, acessibilidade e consistência entre telas            |
| Testes e deploy       | Não confirmados           | Suíte automatizada, migrations, validação do schema e estratégia de publicação |

## Estratégia geral

A recomendação é **evoluir a implementação existente, sem reescrever o sistema desnecessariamente**.

### Atualização do lote de catálogo (2026-10-23)

- Adicionados filtros públicos de área mínima/máxima e quantidade mínima de banheiros, com validação no backend e preservação na paginação/ordenação.
- Adicionados índices de banco para listagem pública por status/data e ordenação por preço; a migration precisa ser aplicada em homologação antes de produção.
- Confirmada no código a persistência de eventos analíticos de visualização/contato mediante consentimento; permanece necessária a aplicação da migration correspondente.
- Conforme solicitado, os testes automatizados não foram executados neste lote. A seção T3 de testes continua pendente; não confundir este trabalho de catálogo com aprovação da suíte.

Ordem recomendada:

1. Revisar segurança, autenticação, permissões e banco de dados.
2. Completar o catálogo público.
3. Completar o painel da imobiliária.
4. Completar o painel administrativo.
5. Implementar destaques e monetização somente conforme as decisões de produto.
6. Executar testes de regressão, homologação e publicação.

---

# 2. A — Catálogo público

## A1. Listagem de imóveis com fotos

- [x] Exibir imóveis publicados na página inicial.
- [x] Exibir cards com preço, finalidade, tipo e localização.
- [x] Exibir a primeira foto disponível ou uma imagem alternativa.
- [x] **P1 — Implementar paginação real.** O catálogo oferece navegação entre páginas, total de resultados e preservação dos filtros.
- [x] **P1 — Implementar ordenação.** O catálogo oferece mais recentes, menor preço e maior preço.
- [x] **P1 — Permitir escolher uma foto de capa** para cada imóvel; a foto selecionada passa a ocupar a primeira posição da galeria.
- [x] **P1 — Garantir ordenação estável das fotos**, usando `sortOrder` e `id` como desempate e persistindo a capa como primeira posição.
- [x] **P1 — Melhorar os estados vazios.** Quando não houver resultados, a página explica o cenário e oferece a opção de limpar filtros.
- [x] **P1 — Tratar imagens quebradas** com fallback visual consistente no catálogo.
- [x] **P2 — Otimizar imagens.** O upload valida o conteúdo real, rejeita dimensões excessivas, redimensiona para no máximo 2400 px no maior lado e comprime JPG/PNG/WEBP usando GD; o servidor precisa ter a extensão GD habilitada.
- [x] **P2 — Aplicar carregamento tardio** às imagens do catálogo e da galeria de detalhes.
- [x] **P2 — Implementar SEO básico:** títulos, descrições, URLs canônicas, Open Graph e sitemap dinâmico para a página inicial e imóveis públicos.

### Critérios de aceite

- A listagem funciona com poucos ou muitos imóveis.
- A navegação entre páginas mantém filtros e ordenação.
- Nenhum imóvel indisponível aparece indevidamente no catálogo.
- A ausência ou falha de uma foto não quebra o layout.\n- As imagens enviadas são validadas pelo conteúdo real e otimizadas antes de serem armazenadas.

## A2. Busca e filtros

- [x] Filtrar por cidade.
- [x] Filtrar por bairro.
- [x] Filtrar por finalidade: venda ou aluguel.
- [x] Filtrar por tipo de imóvel.
- [x] Filtrar por preço mínimo e máximo.
- [x] **P0 — Validar todos os parâmetros no backend.** Campos de texto têm limite de tamanho; enums são validados; quantidades aceitam apenas inteiros entre 0 e 20; parâmetros em formato inesperado são normalizados sem gerar consultas inválidas.
- [x] **P1 — Adicionar filtro por quantidade de quartos.** Filtra pela quantidade mínima informada.
- [x] **P1 — Adicionar filtro por quantidade de vagas.** Filtra pela quantidade mínima informada.
- [x] **P1 — Rejeitar ou normalizar preços negativos e valores inválidos.** Preços devem ser numéricos, não negativos, com até duas casas decimais e dentro do limite suportado.
- [x] **P1 — Validar a relação entre preço mínimo e máximo.** A faixa inconsistente é sinalizada e não é aplicada à consulta.
- [x] **P1 — Preservar filtros durante paginação e ordenação.** Parâmetros normalizados são mantidos nos links de navegação.
- [x] **P1 — Mostrar quantidade de resultados.** A listagem informa o total encontrado.
- [x] **P1 — Mostrar filtros ativos e permitir removê-los individualmente.** Cada filtro ativo tem ação de remoção.
- [x] **P1 — Adicionar ação para limpar todos os filtros.**
- [x] **P1 — Criar uma experiência mobile com filtros recolhíveis e acessíveis.** O formulário usa `details`/`summary`, rótulos associados e feedback de validação.
- [x] **P2 — Adicionar filtros por área e banheiros.** O catálogo aceita área mínima/máxima (0 a 1.000.000 m², até duas casas decimais) e quantidade mínima de banheiros (0 a 20), com validação no servidor, combinação com os filtros existentes e chips para remoção individual.
- [x] **P2 — Adicionar índices para consultas públicas frequentes.** Migration `Version20261023140000` adiciona índices compostos para visibilidade/ordenação por publicação e ordenação por preço. O impacto real de desempenho ainda depende de medição com volume representativo.

### Critérios de aceite

- Os filtros podem ser combinados.
- Valores inválidos não causam erros nem consultas inesperadas.
- A URL representa os filtros aplicados, quando apropriado.
- O comportamento é consistente em desktop e celular.

## A3. Página individual do imóvel

- [x] Exibir detalhes do imóvel.
- [x] Exibir descrição, preço, finalidade, tipo, localização e características.
- [x] Exibir galeria de fotos.
- [x] Exibir informações básicas da imobiliária.
- [x] **P0 — Revisar o uso do slug.** A rota recebe um slug, mas a consulta identificada utiliza o ID. Corrigir a resolução da URL, a validação e o comportamento de 404 ou redirecionamento canônico.
- [x] **P1 — Exibir condomínio e IPTU** quando houver valores cadastrados, deixando clara a periodicidade.
- [x] **P1 — Garantir que anúncios pausados, arquivados, vendidos ou alugados não sejam exibidos como disponíveis.**
- [x] **P1 — Impedir a exibição de imóveis de imobiliárias bloqueadas.**
- [x] **P1 — Melhorar a galeria:** miniaturas, navegação por teclado, foco visível e experiência mobile.
- [x] **P1 — Definir e aplicar a política de privacidade do endereço.** A rua e o número devem seguir a regra de exposição estabelecida pelo produto.
- [x] **P2 — Adicionar imóveis semelhantes**, considerando cidade, bairro e tipo.
- [x] **P2 — Adicionar metadados para compartilhamento em redes sociais.**

### Critérios de aceite

- Cada imóvel possui URL pública coerente.
- IDs ou slugs inexistentes retornam resposta apropriada.
- Os dados exibidos respeitam as regras de privacidade e disponibilidade.
- A galeria funciona por toque, mouse e teclado.

## A4. Contato via WhatsApp

- [x] Gerar link para WhatsApp com mensagem pré-preenchida sobre o imóvel.
- [x] **P0 — Normalizar e validar o número de telefone.** Números brasileiros locais de 10/11 dígitos recebem o código 55; o destino aceita apenas dígitos e rejeita formatos inválidos.
- [x] **P1 — Criar alternativa de contato** por telefone e e-mail quando o WhatsApp não estiver disponível.
- [x] **P1 — Definir como registrar cliques de contato** para fins operacionais, usando log estruturado do Symfony.
- [x] **P1 — Associar o evento ao imóvel e à imobiliária corretos**, registrando somente os IDs e o canal.
- [x] **P1 — Evitar registros duplicados** da mesma sessão/imóvel/canal durante uma janela de 30 minutos.
- [x] **P1 — Informar ao usuário sobre o tratamento de dados**, explicando que cliques são registrados e que o conteúdo da conversa não é armazenado.
- [ ] **P2 — Avaliar formulário interno de interesse**, caso seja necessário acompanhar contatos que não iniciam pelo WhatsApp.

### Critérios de aceite

- O link abre uma conversa válida no WhatsApp.
- A mensagem identifica corretamente o imóvel.
- A ausência de um telefone não gera link quebrado.
- Os eventos registrados não expõem dados pessoais desnecessários.
- [x] **P1 — Persistir os eventos em banco para relatórios analíticos.** `ContactEvent`, `ContactEventRepository`, `ContactAnalytics` e a migration `Version20261022100000` registram visualizações e contatos quando o visitante consentiu com métricas opcionais. Não são armazenados IPs nem conteúdo da conversa; a entrega dos eventos depende da migration aplicada no banco.

## A5. Responsividade e acessibilidade

- [x] Existe uma base visual e CSS responsivo.
- [x] Há ajustes de layout para telas menores.
- [ ] **P1 — Testar larguras de 320, 375, 768, 1024 pixels e desktop.**
- [x] **P1 — Revisar cards, filtros, formulários, galeria, tabelas e navegação em celular (revisão estática do CSS/templates).**
- [x] **P1 — Associar labels a todos os campos.** Busca administrativa e motivo de bloqueio receberam labels explícitos; formulários Symfony usam `form_row`.
- [x] **P1 — Implementar mensagens de erro acessíveis e relacionadas aos campos correspondentes.** Os filtros inválidos referenciam o resumo de erros via `aria-describedby`; a validação visual e o alerta permanecem.
- [x] **P1 — Revisar contraste, foco visível, navegação por teclado e tamanho dos alvos de toque.** Foco visível reforçado, botões com altura mínima existente de 43 px e regiões de tabelas navegáveis por teclado.
- [x] **P1 — Tornar tabelas densas utilizáveis em celular.** As tabelas administrativas usam rolagem horizontal contida, região nomeada e foco por teclado; não se ocultam colunas/dados.
- [ ] **P2 — Consolidar estilos inline em componentes e tokens reutilizáveis.** Parcial: foram adicionadas regras compartilhadas, mas ainda existem estilos inline antigos a consolidar.
- [x] **P2 — Respeitar a preferência por movimento reduzido do sistema operacional.** `prefers-reduced-motion` reduz transições e desativa rolagem suave.

### Critérios de aceite

- Não há overflow horizontal involuntário.
- Os fluxos críticos funcionam por teclado.
- A interface mantém legibilidade e hierarquia visual em todas as larguras.
- A identidade visual existente é preservada.

---

# 3. B — Painel da imobiliária

## B1. Cadastro e edição de imóveis

- [x] Criar imóveis como rascunho.
- [x] Editar informações de imóveis.
- [x] Associar o imóvel à imobiliária do usuário autenticado.
- [x] Cadastrar descrição, finalidade, tipo, preço, taxas, área, quartos, banheiros, vagas e localização.
- [x] **P0 — Auditar autorização nas rotas de gerenciamento de imóveis.** As rotas de edição, imagens, capa, remoção e status verificam a associação com a imobiliária por `AgencyAccess`; o administrador global mantém o bypass administrativo previsto.
- [x] **P1 — Validar todos os campos no servidor.** Formulário Symfony com constraints para texto, enums, preço, taxas, área, quantidades, UF e CEP.
- [x] **P1 — Impedir valores negativos para preço, taxas, área e características numéricas.**
- [x] **P1 — Validar UF, CEP, limites de texto e campos obrigatórios.**
- [x] **P1 — Gerar slug único globalmente e tratar colisões na aplicação.** A constraint de unicidade no banco ainda precisa de migration e deve ser validada em implantação.
- [x] **P1 — Atualizar `updatedAt` nas operações de edição e mudança de status.** Outras operações de imagem não alteram os campos do imóvel; podem ser incluídas em auditoria posterior se desejado.
- [ ] **P1 — Criar testes de criação, edição, dados inválidos e isolamento entre imobiliárias.** Pendente: pacote sem configuração/testes Symfony completos para executar a suíte.
- [x] **P2 — Organizar o formulário em seções:** informações, características, localização e valores; a seção de fotos fica separada abaixo. Campos permanecem preenchidos quando a validação falha.
- [x] **P2 — Melhorar a apresentação dos erros sem perder os dados já preenchidos.** `form_row` mantém os erros junto aos campos e Symfony preserva os valores enviados em caso de falha de validação.

### Critérios de aceite

- Os campos são validados no backend.
- Funcionários só acessam os imóveis autorizados.
- Slugs não entram em conflito.
- Erros são apresentados de maneira clara.

## B2. Upload e organização de fotos

- [x] Upload múltiplo de imagens.
- [x] Verificação de MIME e limite de tamanho no serviço de upload.
- [x] Nome de arquivo aleatório e diretório por imóvel.
- [x] Remoção de fotos com CSRF e verificação de vínculo com o imóvel.
- [x] **P0 — Validar o conteúdo real da imagem**, não apenas o MIME informado pelo cliente. O serviço lê os metadados reais e tenta decodificar o conteúdo antes de gravar.
- [x] **P0 — Definir limites de dimensões, tamanho total do lote e quantidade máxima de fotos.**
- [x] **P0 — Tratar falhas parciais de upload e persistência** para não deixar registros e arquivos inconsistentes. Erros por arquivo são reportados individualmente e arquivos recém-criados são removidos se o flush falhar.
- [x] **P1 — Permitir reordenar as fotos.** Controles acessíveis de subir/descer.
- [x] **P1 — Permitir selecionar a foto de capa.** A rota protegida por CSRF move a imagem escolhida para a primeira posição, que define a capa.
- [x] **P1 — Persistir a ordem das fotos de forma confiável.** A ordem é normalizada e persistida no campo `sortOrder`.
- [x] **P1 — Permitir texto alternativo para acessibilidade.** Campo editável com limite de 180 caracteres.
- [x] **P1 — Exibir progresso percentual em tempo real e erros por arquivo.** O navegador apresenta progresso de envio via `XMLHttpRequest`; o servidor continua reportando resultados individuais por arquivo por meio das mensagens da sessão. A confirmação visual deve ser revisada no navegador real.
- [x] **P1 — Gerar miniaturas e otimizar imagens.** Novos uploads geram uma imagem principal de até 2400 px e miniatura de até 480 px; a persistência do caminho é opcional para manter compatibilidade com fotos antigas. Exige a extensão GD no servidor.
- [x] **P1 — Adicionar proteção básica ao diretório de uploads para hosting Apache compatível.** Desativa listagem e bloqueia execução/acesso a extensões de script; confirmar as regras efetivas no provedor antes de produção.
- [x] **P1 — Definir uma rotina segura para detectar e remover arquivos órfãos.** O comando `app:uploads:purge-orphans` opera em simulação por padrão, exige idade mínima de 24 horas e reconhece arquivos originais, miniaturas e logos; usar `--execute` somente após revisar a lista.
- [x] **P2 — Alternativa acessível ao drag-and-drop.** Reordenação por botões de subir/descer, utilizável por teclado; drag-and-drop não foi adicionado.

### Critérios de aceite

- Arquivos inválidos são rejeitados.
- A falha de um arquivo não corrompe os demais.
- A ordem e a capa persistem após recarregar a página.
- A remoção não permite acessar arquivos de outro imóvel.

## B3. Preço, descrição e disponibilidade

- [x] Cadastrar preço e descrição.
- [x] Existem estados de rascunho, publicado, pausado, vendido, alugado e arquivado.
- [x] Existem ações para publicar, pausar, marcar como vendido/alugado e arquivar.
- [x] **P0 — Implementar uma máquina de estados explícita.** Validar quais transições são permitidas e quais papéis podem executá-las.
- [x] **P0 — Validar requisitos antes da publicação**, incluindo preço, descrição, localização e quantidade mínima de fotos, conforme a regra de negócio.
- [x] **P1 — Separar estado comercial de estado de moderação.**
- [x] **P1 — Registrar histórico de status:** usuário responsável, data, status anterior, novo status e motivo, quando aplicável.
- [x] **P1 — Definir o comportamento de `publishedAt`** ao pausar e republicar.
- [x] **P1 — Exibir nomes de status em português**, sem expor os enums internos.
- [x] **P1 — Implementar confirmações contextuais para ações importantes.**
- [x] **P1 — Mostrar mensagens de sucesso somente após a persistência da alteração.**

### Critérios de aceite

- Transições inválidas são rejeitadas no backend.
- O anúncio não fica público quando está pausado ou bloqueado.
- O histórico permite entender quem alterou o status.
- Os estados são apresentados de forma clara e consistente.

## B4. Perfil público da imobiliária

- [x] A entidade `Agency` possui campos para nome, slug, descrição, e-mail, telefone, WhatsApp, site, logo e cidade/UF.
- [x] **P0 — Criar rota pública do perfil por slug.**
- [x] **P1 — Criar tela para a imobiliária editar o próprio perfil.**
- [x] **P1 — Garantir que o usuário só edite os dados da própria imobiliária.**
- [x] **P1 — Implementar upload, substituição e remoção de logo com validação segura.**
- [x] **P1 — Exibir somente imóveis publicados de imobiliárias ativas.**
- [x] **P1 — Validar e renderizar com segurança e-mail, telefone, WhatsApp e site.**
- [x] **P1 — Garantir slug único e URL canônica.**
- [x] **P1 — Definir quais dados cadastrais são privados.**
- [x] **P2 — Adicionar metadados de SEO e compartilhamento.**

### Critérios de aceite

- Cada imobiliária possui uma página pública.
- O perfil exibe os anúncios disponíveis daquela imobiliária.
- Alterações no painel refletem no perfil público.
- Informações privadas não são expostas.

## B5. Contatos e métricas básicas

- [x] **P0 — Definir formalmente o que conta como contato.** Clique de contato significa envio válido do botão de WhatsApp pelo portal; não confirma mensagem enviada. Visualizações de páginas de imóveis também são registradas. Links diretos `tel:` e `mailto:` ainda não são medidos.
- [x] **P1 — Criar persistência para eventos de contato e analytics.** Entidade `ContactEvent`, repositório e migration própria.
- [x] **P1 — Relacionar os eventos à imobiliária e ao imóvel.** A entidade valida a associação entre ambos e possui chaves estrangeiras.
- [x] **P1 — Criar dashboard por período.** Filtro de datas inclusivo, período padrão de 30 dias e limite máximo de 366 dias.
- [x] **P1 — Exibir visualizações, cliques de contato e quantidade de anúncios por status.** A tela distingue métricas por período do inventário atual por status.
- [ ] **P1 — Criar lista de contatos recebidos caso exista formulário interno.** Não há formulário interno nesta versão; por isso, não há leads com dados pessoais a listar.
- [x] **P1 — Implementar isolamento das consultas por imobiliária.** O escopo é derivado do usuário autenticado via `AgencyAccess`, sem aceitar um ID de imobiliária do cliente.
- [x] **P1 — Definir política de retenção e privacidade dos eventos.** Armazenamento minimizado, sem IP ou dados do visitante; retenção padrão de 365 dias documentada e comando de limpeza disponível para agendamento no hosting.
- [x] **P1 — Evitar contagens duplicadas e abuso automatizado.** Deduplicação por sessão, imóvel e tipo/canal em janela de 30 minutos. Isso reduz repetição acidental, mas não bloqueia bots sofisticados.
- [x] **P2 — Permitir exportação CSV.** Exportação por período, UTF-8 com BOM e neutralização de células potencialmente interpretadas como fórmulas.
- [x] **P2 — Documentar precisamente o significado de cada métrica.** Definições, limitações, privacidade, isolamento e retenção em `docs/analytics/contact-metrics.md`.

### Critérios de aceite

- [x] Uma imobiliária não consegue consultar métricas de outra, conforme o escopo explícito das consultas.
- [x] Os números são calculados de acordo com regras documentadas.
- [x] Os filtros por período funcionam no controller, incluindo validação e limite de intervalo.
- [x] A interface distingue métricas registradas de estimativas e não apresenta telefone/e-mail não medidos como zero contatos reais.

## B6. Equipe e permissões

- [x] Cadastro e edição de funcionários.
- [x] Alteração de senha e ativação/desativação de usuários.
- [x] Gerenciamento de membros no contexto da imobiliária.
- [x] **P0 — Bloquear autenticação de contas inativas.** `App\Security\UserChecker` rejeita contas desativadas antes da autenticação.
- [x] **P0 — Bloquear autenticação de usuários de imobiliárias não ativas.** Usuários sem imobiliária ativa não autenticam; administradores globais não dependem do status de uma imobiliária.
- [x] **P1 — Impedir promoção de funcionários a administrador.** O formulário de equipe não permite editar papéis; criação define `ROLE_AGENCY_STAFF`; gestores, administradores e o próprio usuário não podem ser alterados por esse fluxo.
- [ ] **P1 — Confirmar alteração de e-mail e recuperar senha com fluxo seguro.** O código de tokens de uso único, hash SHA-256, expiração de 1 hora, CSRF e confirmação está implementado. A conclusão operacional depende de configurar `MAILER_DSN` e `MAILER_FROM` reais; o `.env` atual usa `null://null`, que descarta mensagens, e o envio não foi testado em runtime.
- [x] **P1 — Registrar operações sensíveis da equipe.** `UserAuditLog` registra criação, edição, ativação/desativação, solicitação/confirmação de alteração de e-mail e redefinição de senha, sem armazenar senhas nem tokens em claro.
- [ ] **P2 — Avaliar convite de funcionários por e-mail.** Cadastro de funcionário ainda é feito pelo gestor; não há convite com ativação inicial por link.

### Verificações e pendências do tópico

- Sintaxe PHP estática e integridade do ZIP foram verificadas nesta entrega.
- O pacote desta entrega inclui `vendor/` e permite lint/console Symfony. Não foram executados testes de integração, autenticação ou envio real de e-mail; o mailer segue `null://null`.
- Aplicar a migration `Version20261023100000` antes de usar tokens de conta e auditoria em um banco existente.

---

## C1. Aprovação e bloqueio de imobiliárias

- [x] Listagem, pesquisa e edição de imobiliárias.
- [x] Bloqueio e reativação, com validação das transições permitidas.
- [x] Histórico persistido de alterações de status com ator, data, estado anterior, novo estado e motivo.
- [x] **P0 — Implementar status de aprovação.** Estados `pending`, `active`, `blocked` e `rejected`; novos cadastros administrativos começam como pendentes.
- [x] **P0 — Impedir ativação automática de novos cadastros.** Imobiliárias pendentes não entram no catálogo e seus usuários não autenticam até a aprovação.
- [x] **P1 — Criar fila de análise.** O painel administrativo permite filtrar imobiliárias por status, inclusive pendentes. A listagem agora tem paginação navegável de 25 registros e preserva os filtros.
- [x] **P1 — Implementar aprovação e rejeição com motivo.** A rejeição exige motivo; a aprovação e as demais mudanças são ações POST protegidas por CSRF.
- [x] **P1 — Registrar ator, data, estado anterior, novo estado e motivo.** A entidade `AgencyStatusHistory` recebe os dados em cada transição, incluindo o registro inicial pendente.
- [x] **P1 — Exibir motivo e data do bloqueio/decisão no painel.**
- [x] **P1 — Garantir que o bloqueio retire os anúncios do catálogo.** As consultas públicas filtram somente imobiliárias `active`.
- [x] **P1 — Impedir ações no painel por usuários de imobiliárias bloqueadas.** `UserChecker` bloqueia novos logins e `AgencyAccess` revalida o status nas operações do painel.
- [ ] **P2 — Implementar notificações por e-mail.** Não foi adicionado envio de decisões administrativas. Além disso, o `.env` usa `MAILER_DSN=null://null`; é necessário configurar e testar o provedor real antes de habilitar notificações.

### Critérios de aceite

- O administrador consegue aprovar, rejeitar, bloquear e reativar respeitando as transições permitidas.
- O histórico registra as mudanças com ator, data, estados e motivo.
- A decisão afeta a visibilidade pública e as permissões.
- Notificações por e-mail permanecem pendentes até haver configuração real de envio.

---

## C2. Gerenciamento global de usuários e permissões

- [x] Existe entidade `User` com papéis.
- [x] Existe gerenciamento de funcionários no contexto da imobiliária.
- [x] Foi identificado comando Symfony para criar o administrador inicial.
- [x] **P0 — Criar painel global de usuários.** `UserAdminController` fornece uma listagem administrativa protegida por `ROLE_ADMIN`.
- [x] **P0 — Criar o primeiro gestor de uma imobiliária pelo painel administrativo.** Rota própria com validação, CSRF, e-mail único, papel fixo `ROLE_AGENCY_MANAGER`, senha inicial informada e confirmada pelo administrador, hash de senha e auditoria; o administrador compartilha as credenciais manualmente por canal seguro. A imobiliária continua sem acesso até aprovação.
- [x] **P0 — Permitir pesquisar, visualizar, ativar e desativar usuários.** A lista mostra identidade, vínculo e status; filtros e ações de ativação/desativação são implementados no servidor.
- [x] **P0 — Proteger o último administrador ativo contra remoção ou desativação acidental.** `UserManagementPolicy` bloqueia a desativação ou perda de papel do último administrador global ativo; a própria conta não pode ser alterada pelo painel.
- [x] **P0 — Revisar `security.yaml`, regras de acesso e hierarquia de papéis.** Regras explícitas protegem `/admin`, equipe e painel de imobiliária; o dashboard continua acessível a usuários autenticados para redirecionar administradores.
- [x] **P1 — Centralizar autorização em voters, políticas ou serviços consistentes.** `UserManagementPolicy` centraliza as verificações de alvo e proteção do último administrador, junto com `IsGranted` e checagem explícita do administrador.
- [x] **P1 — Registrar auditoria de alterações de papéis e associações.** `UserAuditLogger` registra papéis antigos/novos e IDs de imobiliária sem incluir segredos.
- [ ] **P1 — Implementar recuperação de senha segura.** O fluxo seguro foi criado no lote B6, com token de uso único, hash e expiração; ainda depende de `MAILER_DSN` e `MAILER_FROM` reais, e o envio não foi validado em runtime. O `.env` usa `null://null`.
- [x] **P1 — Confirmar CSRF em todas as operações de escrita.** As ações de status e permissões usam POST e token CSRF individual por usuário.
- [x] **P1 — Garantir que ações de alteração não sejam executadas por GET.** Alterações de status e permissões aceitam somente POST.
- [x] **P2 — Adicionar filtros por papel, status e imobiliária.** A listagem oferece filtros por nome/e-mail, papel, status e vínculo com imobiliária. A listagem agora tem paginação navegável de 25 registros, contagem total e preservação dos filtros.

### Critérios de aceite

- Apenas administradores autorizados acessam o painel global.
- Nenhum funcionário consegue alterar privilégios indevidamente.
- Contas desativadas não conseguem autenticar.
- Mudanças críticas são auditáveis.

### Verificações e pendências do tópico

- Sintaxe PHP dos arquivos de `src/` e migrations validada com `php -l`.
- A listagem de usuários e imobiliárias usa paginação de 25 registros; filtros e contagem são aplicados no repositório. O filtro de papel usa correspondência no JSON `roles`, coerente com o banco MySQL/MariaDB previsto.
- Foram executados lint de PHP/Twig/container e listagem de rotas com o `vendor/` incluído; ainda não houve validação das consultas contra um banco real nem testes HTTP.
- Recuperação de senha e notificações de decisão continuam dependentes de `MAILER_DSN`/`MAILER_FROM` reais e de validação de entrega; não estão sendo declaradas como concluídas em produção.

## C3. Moderação de anúncios

- [x] **P0 — Criar painel global de imóveis.** `PropertyAdminController` lista anúncios e oferece página individual de revisão.
- [x] **P1 — Implementar paginação navegável na fila de moderação.** Página de 25 anúncios, contagem total, filtros preservados e consulta sem join da coleção de fotos para não duplicar linhas.
- [x] **P0 — Adicionar filtros por imobiliária, status, cidade, data e tipo.** Também há busca por título/nome da imobiliária e filtro por estado de moderação.
- [x] **P0 — Separar estado de moderação de estado comercial.** A entidade `Property` agora mapeia `moderation_status` para `PropertyModerationStatus`; a migration `Version20261021110000` já existia no projeto e foi conectada ao modelo.
- [x] **P1 — Criar página de revisão com fotos, descrição, imobiliária e histórico.** A tela administrativa apresenta dados do anúncio, galeria e histórico comercial/de moderação.
- [x] **P1 — Implementar aprovar, rejeitar e solicitar ajustes.** As ações administrativas são POST e passam por `PropertyStatusManager`.
- [x] **P1 — Exigir motivo ao rejeitar ou solicitar ajustes.** O servidor valida motivo obrigatório de até 2000 caracteres para essas decisões.
- [x] **P1 — Impedir que anúncios reprovados voltem ao catálogo por simples publicação da imobiliária.** Publicar exige moderação aprovada; as consultas públicas, páginas de detalhe, similares e sitemap filtram anúncios não aprovados.
- [x] **P1 — Registrar auditoria de todas as decisões.** `PropertyStatusHistory` persiste ator, data, status anterior/novo e motivo nas transições de moderação; transições comerciais do painel de imobiliária também passam pelo serviço de status.
- [x] **P1 — Exibir o resultado da moderação no painel da imobiliária.** O painel mostra o estado e o motivo/orientação da decisão mais recente, com ação para solicitar nova análise após rejeição ou pedido de ajustes. Alterações de dados e fotos em anúncios já aprovados retornam a moderação para pendente e exigem nova revisão antes de voltarem ao catálogo.
- [ ] **P2 — Avaliar denúncia de anúncios e regras contra conteúdo enganoso ou duplicado.** Ainda não há fluxo de denúncia nem detecção de duplicidade; permanece como melhoria futura.

### Critérios de aceite

- O administrador consegue revisar um anúncio sem alterar diretamente o banco.
- Cada decisão fica registrada.
- A imobiliária consegue entender e corrigir os motivos de rejeição.
- O estado de moderação não é confundido com disponibilidade comercial.

### Verificações e pendências do tópico

- Sintaxe PHP validada com `php -l` para `src/` e migrations.
- A migration de moderação já existia; não foi criada migration nova para C3. Em bancos existentes, confirme que `Version20261021110000` foi aplicada.
- O pacote inclui `vendor/`; Twig/container/rotas foram validados estaticamente, mas o fluxo de moderação e as consultas Doctrine ainda precisam de integração com banco MySQL/MariaDB.
- A fila de moderação agora usa paginação navegável de 25 anúncios. A consulta da lista não faz join da coleção de fotos, evitando que múltiplas imagens consumam slots da paginação.

## C4. Destaque de imóveis

- [x] **P2 — Decidir se os destaques serão gratuitos, editoriais ou monetizados.** — definido como destaque editorial gratuito e concedido manualmente pelo administrador no MVP; nenhuma cobrança é inferida.
- [x] **P2 — Criar persistência do destaque com data de início e fim.** — entidade `PropertyHighlight`, migration `Version20261023120000`, período de vigência e remoção sem apagar o histórico.
- [x] **P2 — Registrar quem concedeu o destaque e por qual motivo.** — responsável, motivo, datas e responsável/motivo da remoção são persistidos.
- [x] **P2 — Criar ações administrativas para destacar e remover destaque.** — ações POST com CSRF, validação de elegibilidade e motivo obrigatório.
- [x] **P2 — Criar seção de imóveis destacados no catálogo.** — seção na página inicial usando os mesmos cartões acessíveis do catálogo.
- [x] **P2 — Definir limite e ordenação dos destaques.** — até 6 imóveis; ordem pela concessão mais recente; cada período limitado a 90 dias.
- [x] **P2 — Se monetizado, validar pagamento ou assinatura antes da exibição.** — não aplicável nesta versão: os destaques são editoriais e gratuitos, sem promessa de pagamento automático.
- [x] **P2 — Manter destaque independente da aprovação e da disponibilidade do anúncio.** — o registro de destaque é independente; a consulta pública só exibe imóveis publicados, aprovados, vinculados a imobiliária ativa e dentro do período vigente.

### Critérios de aceite

- Somente anúncios elegíveis podem receber destaque.
- Destaques expiram conforme a regra definida.
- Um anúncio bloqueado ou indisponível não permanece em destaque público.

### Verificações e limitações

- A sintaxe PHP foi validada para os arquivos do projeto; veja o relatório da entrega.
- A migration `Version20261023120000` deve ser aplicada em homologação antes de usar a funcionalidade.
- O pacote inclui `vendor/`; a integração das consultas Doctrine e a renderização com dados reais ainda precisam de banco de teste/homologação.

## C5. Planos e assinaturas — fora de escopo do MVP

> **Decisão resolvida:** o ADR-0009, aceito em 2026-10-10, define que a cobrança mensal será feita manualmente e fora do portal no MVP. Assim, não serão implementados checkout, assinatura automática, conciliação financeira nem gestão de planos dentro do sistema nesta fase. O administrador continuará controlando o acesso pela situação da imobiliária. A decisão não impede uma futura fase de billing, que exigirá novo ADR e definição comercial.

- [x] **P2 — Definir se a cobrança será integrada ao portal no MVP.** — decidido que será manual e externa ao portal; ver `docs/adrs/0009-manual-recurring-billing.md`.
- [ ] **P2 — Definir planos, preços, periodicidade e benefícios.** — fora de escopo enquanto a cobrança for manual e externa.
- [ ] **P2 — Definir limites por plano**, como quantidade de anúncios ativos e número de usuários — fora de escopo nesta fase.
- [ ] **P2 — Criar estados de assinatura, entidades, migrations e telas de cobrança.** — não aplicável ao MVP conforme ADR-0009.
- [ ] **P2 — Escolher provedor, implementar webhooks e testar pagamentos.** — não aplicável sem aprovação de integração automatizada.
- [x] **P2 — Não armazenar dados de cartão diretamente no portal.** — não existe coleta ou persistência de dados de cartão no escopo atual.

### Critérios de aceite da decisão de escopo

- A cobrança manual não é apresentada como pagamento registrado ou conciliado pelo sistema.
- O status da imobiliária continua sendo controlado administrativamente, sem automatismo financeiro.
- Uma futura integração exige decisão comercial e técnica explícita; os itens de implementação acima permanecem desmarcados até lá.

## C6. Relatórios de utilização

- [x] **P1 — Definir métricas operacionais e de produto.** — definições registradas em `docs/analytics/admin-usage-metrics.md`; contagens atuais são distintas de eventos acumulados.
- [x] **P1 — Exibir quantidade de imobiliárias ativas, pendentes e bloqueadas.** — contagem global por status atual.
- [x] **P1 — Exibir usuários e imóveis por status.** — usuários ativos/inativos; imóveis por status comercial e status de moderação.
- [x] **P1 — Exibir evolução de anúncios e contatos por período.** — agregações mensais limitadas ao período solicitado.
- [x] **P1 — Implementar consultas agregadas eficientes.** — SQL agregado no banco, sem carregar todas as entidades na memória.
- [x] **P1 — Criar filtros por período e, quando pertinente, por imobiliária.** — período inclusivo de até 366 dias; filtro de imobiliária nas métricas de usuários, imóveis e evolução.
- [x] **P1 — Restringir relatórios ao perfil administrativo autorizado.** — rota protegida por `ROLE_ADMIN` e regra global de acesso.
- [x] **P2 — Implementar exportação CSV.** — exporta indicadores agregados com cabeçalho UTF-8 e resposta privada sem cache.
- [x] **P2 — Proteger exports contra formula injection.** — células textuais não confiáveis recebem prefixo de segurança quando começam com caracteres interpretados como fórmula.
- [x] **P2 — Definir retenção e anonimização dos eventos.** — retenção padrão de 365 dias pelo comando existente; eventos guardam apenas IDs relacionais, tipo/canal e data, sem IP ou identificadores do visitante. A execução periódica ainda depende de agendamento manual no ambiente de hospedagem.

### Critérios de aceite

- As métricas e suas limitações estão documentadas.
- As consultas usam agregações no banco e não carregam todos os registros em memória.
- Usuários sem `ROLE_ADMIN` não podem acessar os relatórios globais.
- Testes de integração com MySQL e renderização Twig com dados reais continuam pendentes até configurar um banco de teste/homologação.

---

# 5. Segurança e consistência

## S1. Autenticação e autorização

- [x] **P0 — Revisar `config/packages/security.yaml`.** Provider Doctrine e hasher por `auto` foram mantidos; regras públicas e administrativas foram explicitadas.
- [x] **P0 — Confirmar provider Doctrine, password hasher, login, logout e regras de acesso.** Logout agora usa POST e CSRF do firewall.
- [x] **P0 — Implementar ou confirmar bloqueio de usuários inativos durante a autenticação.** `UserChecker` valida pré-login e pós-autenticação/restauração da sessão.
- [x] **P0 — Impedir autenticação ou operações privilegiadas por membros de imobiliárias bloqueadas.** A validação de conta/imobiliária é repetida no `UserChecker`; `AgencyAccess` e autorização por controller permanecem aplicados nas operações de imobiliária.
- [ ] **P0 — Auditar todos os controllers para prevenir acesso a registros de outras imobiliárias.** A revisão estática foi feita, mas a cobertura completa exige testes de integração alterando IDs e usuários entre imobiliárias.
- [ ] **P0 — Revisar todas as ações de escrita para método HTTP adequado, CSRF e autorização.** Logout e operações encontradas foram revisados; os fluxos restantes precisam de testes de regressão por rota/formulário.
- [ ] **P1 — Adicionar limitação de tentativas de login**, conforme suporte da infraestrutura. `symfony/rate-limiter` não está declarado no lock; não foi adicionada uma proteção improvisada por sessão.
- [ ] **P1 — Evitar registrar senhas, tokens ou dados pessoais desnecessários em logs.** Não foi executada auditoria completa dos logs em runtime.
- [ ] **P1 — Revisar HTTPS, cookies de sessão, cabeçalhos HTTP e tratamento de erros.** Cookies seguros e cabeçalhos básicos foram configurados; HTTPS/proxy e tratamento de erros precisam de validação no host real.

## S2. Banco de dados e migrations

- [x] **P0 — Obter e revisar todas as migrations.** Todas as migrations versionadas foram inspecionadas estaticamente.
- [ ] **P0 — Comparar o schema real com as entidades Doctrine.** Não havia banco de produção/homologação disponível para comparação efetiva.
- [x] **P0 — Confirmar constraints e índices**, incluindo e-mails únicos, slugs, foreign keys e relacionamentos de fotos. Revisão estática confirmou email e slug de imobiliária únicos, FKs e cascatas; slugs de imóveis são descritivos e não globais. Foram adicionados índices ausentes no histórico de migrations.
- [x] **P1 — Criar migrations para as funcionalidades novas.** Migration `Version20261023130000` adiciona os índices declarados na entidade `Property`.
- [x] **P1 — Definir comportamento para exclusão de usuários, imobiliárias e imóveis.** Política documentada em `docs/operations/database-backup-and-restore.md`; bloqueio/desativação preferidos para usuários e imobiliárias.
- [x] **P1 — Definir estratégia de backup do banco e das fotos.** Procedimento operacional documentado, incluindo cópia externa e cuidados com dados pessoais.
- [ ] **P1 — Testar restauração de backup.** Requer banco e ambiente de homologação reais; nenhum restore foi executado nesta entrega.
- [ ] **P2 — Criar dados de demonstração sem credenciais padrão em produção.** O comando existente cria administrador de forma interativa, sem senha padrão; conjunto de dados demonstrativos não foi criado.

> Não aplicar atualizações automáticas do schema em produção sem revisar previamente o impacto e ter um backup.

## S3. Upload e arquivos

- [x] **P0 — Validar o conteúdo real dos arquivos enviados.** Imagens de anúncios e logos são verificadas por conteúdo/MIME, dimensões, limite de pixels, tamanho e decodificação GD; são reprocessadas e gravadas com nome aleatório e extensão gerada pelo servidor.
- [x] **P0 — Garantir autorização antes de ler, substituir ou remover arquivos.** As operações de gerenciamento exigem papel, associação ativa à imobiliária, propriedade do imóvel e CSRF. Os arquivos públicos do catálogo continuam legíveis por URL por definição; documentos privados não devem ser colocados em `public/uploads`.
- [ ] **P0 — Impedir execução de scripts na pasta de uploads, conforme o servidor permitir.** Regras `.htaccess` reforçadas para Apache (incluindo extensões `php` com sufixos numéricos), cabeçalho `nosniff` e regras de front controller em `public/.htaccess`; continua pendente confirmar Apache/mod_rewrite/AllowOverride ou configurar regras equivalentes em Nginx/IIS e testar no host real.
- [x] **P1 — Tratar falhas de gravação e remoção.** Falhas de processamento/gravação removem arquivos parciais; exclusões de registro e arquivo seguem ordem segura, e falha de remoção física gera aviso sem ocultar o estado do banco.
- [x] **P1 — Criar rotina de limpeza de arquivos órfãos.** `app:uploads:purge-orphans` faz simulação por padrão, considera arquivos com pelo menos 24 horas, confere referências no banco e só apaga padrões de nomes gerados pelo sistema quando usado com `--execute`.
- [x] **P1 — Definir limites de tamanho, dimensões e quantidade.** Fotos: até 8 MB cada, 10 por lote, 40 MB por lote, 30 por imóvel e limite de 40 milhões de pixels; logos: até 5 MB e 20 milhões de pixels. A hospedagem precisa ter limites PHP/servidor compatíveis com os lotes aceitos.

## S4. Privacidade e auditoria

- [x] **P0 — Definir quais dados de imobiliárias, usuários e imóveis são privados.** Inventário em `docs/privacy/data-inventory-and-retention.md`; dados de autenticação, rascunhos, históricos e auditoria são privados, enquanto fotos e campos aprovados do catálogo são públicos.
- [x] **P1 — Registrar operações administrativas sensíveis.** `UserAuditLogger` registra alterações de status/permissões de usuários, moderação e destaques de imóveis, aprovação/bloqueio/reativação de imobiliárias e alteração de logos/fotos, sem copiar motivos livres ou conteúdo de formulários para os metadados.
- [x] **P1 — Evitar exposição de dados pessoais em métricas e logs.** Métricas opcionais só são persistidas após consentimento; eventos não guardam IP/user-agent/conteúdo de mensagem, e falhas de analytics registram classe da exceção em vez do objeto completo.
- [ ] **P1 — Definir retenção de eventos e contatos.** Política-alvo de 365 dias para eventos analíticos e 730 dias para auditoria, com comandos de limpeza implementados. A execução periódica ainda depende de CLI/agendamento disponível e verificado na hospedagem.
- [ ] **P1 — Revisar avisos de privacidade e consentimento conforme o tratamento adotado.** Página `/privacidade`, escolha de métricas e inventário foram implementados; revisão jurídica, validação do responsável pelo portal e confirmação do comportamento no host real permanecem necessárias.

---


## S5. Implantação por FTP e configuração de produção

- [x] **P0 — Documentar variáveis obrigatórias de produção.** `.env` usa `APP_ENV=prod`/`APP_DEBUG=0` por padrão; `.env.prod.example` lista `APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`, `MAILER_FROM` e `DEFAULT_URI` sem credenciais reais. `APP_SECRET` permanece vazio até ser configurado no host.
- [x] **P0 — Documentar o procedimento de implantação FTP.** `docs/operations/ftp-deployment.md` orienta document root `public/`, segredos, banco, SMTP, permissões e pós-publicação.
- [x] **P0 — Não incluir segredo de desenvolvimento no ZIP de entrega.** `.env.dev` é excluído do pacote distribuído; o `.gitignore` também impede que seja adicionado ao repositório.
- [x] **P0 — Criar regras Apache de front controller e proteção básica.** `public/.htaccess` contém rewrite para Symfony, bloqueio de arquivos ocultos/sensíveis e cabeçalhos básicos.
- [ ] **P0 — Validar a configuração no host real.** Confirmar document root, HTTPS, `mod_rewrite`/`AllowOverride` ou regras equivalentes de Nginx/IIS, não execução de scripts em uploads, SMTP real e rotas amigáveis. A validação estática do pacote não substitui a homologação.

### Critérios de aceite

- A raiz pública do domínio é exclusivamente `public/`.
- Nenhum segredo de produção está no ZIP versionado ou exposto por HTTP.
- E-mails transacionais são entregues por um transporte real.
- Rotas Symfony e proteção de uploads são verificadas no servidor contratado.


# 6. Testes automatizados

O pacote analisado não permitiu confirmar a existência de uma suíte de testes completa. Antes de implementar funcionalidades novas, verificar as dependências e o ambiente de teste.

## T1. Preparação

- [x] **P0 — Recuperar `composer.json` e `composer.lock`.** Arquivos existentes foram conferidos; PHPUnit 11.5, Symfony BrowserKit e o cliente de testes constam nas dependências de desenvolvimento.
- [x] **P0 — Configurar ambiente de testes.** Configuração PHPUnit padronizada em `phpunit.xml.dist`, script `composer test` e `.env.test` isolado de qualquer banco configurado em `.env`.
- [ ] **P0 — Executar os testes existentes e registrar falhas iniciais.** O `vendor.zip` foi fornecido e extraído, mas PHPUnit não inicia neste executor: faltam as extensões PHP `dom`, `mbstring` e `xmlwriter`; também não há drivers PDO disponíveis. O bloqueio está documentado em `docs/testing/automated-tests.md`.
- [x] **P1 — Criar dados de teste isolados e previsíveis.** Testes unitários criam entidades em memória, sem fixtures persistidas nem acesso a dados reais.

## T2. Segurança

- [ ] **P0 — Testar acesso anônimo às rotas privadas.** Foram adicionados testes HTTP para áreas administrativa, equipe e imóveis; falta executá-los num PHP com as extensões exigidas pelo PHPUnit.
- [ ] **P0 — Testar funcionário, responsável e administrador.** Foram adicionados testes unitários das regras de acesso de funcionário/responsável e administrador global; falta execução e cobertura HTTP autenticada.
- [ ] **P0 — Testar usuário inativo e imobiliária bloqueada.** Testes unitários adicionados para conta inativa, usuário sem imobiliária e imobiliária bloqueada; falta execução.
- [ ] **P0 — Testar acesso cruzado entre imobiliárias alterando IDs.** Teste unitário adicionado para negar acesso a imóvel de outra imobiliária; teste HTTP com IDs reais e banco isolado ainda pendente.
- [ ] **P1 — Testar CSRF nas operações de escrita.** Ainda sem teste funcional executado para tokens válidos/inválidos.
- [ ] **P1 — Testar proteção contra alteração indevida de papéis.** Adicionados testes unitários de `UserManagementPolicy` para impedir autoalteração e remoção do último administrador ativo; teste HTTP da alteração de papel e execução da suíte continuam pendentes.

## T3. Catálogo e anúncios

- [ ] **P1 — Testar filtros e combinações de filtros.**
- [ ] **P1 — Testar paginação e ordenação.**
- [ ] **P1 — Testar parâmetros inválidos.**
- [ ] **P1 — Testar visibilidade de cada estado do imóvel.**
- [ ] **P1 — Testar slug válido, inexistente e conflito de slug.**
- [ ] **P1 — Testar galeria e fallback de fotos.**
- [ ] **P1 — Testar links de contato.**

## T4. Painéis

- [ ] **P1 — Testar criação, edição e transições de status de imóveis.**
- [ ] **P1 — Testar upload inválido, imagem falsa, arquivo grande e falha parcial.**
- [ ] **P1 — Testar edição do perfil público.**
- [ ] **P1 — Testar métricas e isolamento por imobiliária.**
- [ ] **P1 — Testar aprovação e bloqueio de imobiliárias.**
- [ ] **P1 — Testar moderação e histórico de auditoria.**
- [ ] **P2 — Testar destaques e assinaturas, caso sejam implementados.**

## T5. Interface

- [ ] **P1 — Validar desktop e mobile.**
- [ ] **P1 — Validar navegação por teclado.**
- [ ] **P1 — Validar formulários com erro, sucesso e campos vazios.**
- [ ] **P1 — Validar mensagens de permissão negada.**
- [ ] **P2 — Adicionar testes de regressão visual, se houver infraestrutura adequada.**

---

# 7. Plano de execução por fases

## Fase 0 — Diagnóstico técnico e segurança

- [ ] Obter o projeto completo, incluindo `composer.json`, `composer.lock`, `config/`, migrations e testes.
- [ ] Confirmar versões de PHP e Symfony.
- [ ] Confirmar schema real do banco.
- [ ] Executar validações de sintaxe PHP, Twig, container e rotas.
- [ ] Revisar autenticação, autorização e isolamento entre imobiliárias.
- [ ] Corrigir transições de status e validação de upload.
- [ ] Definir regras de aprovação, privacidade e disponibilidade.

**Critério de saída:** configuração e banco confirmados; regras de acesso validadas; operações críticas protegidas.

## Fase 1 — Catálogo público

- [ ] Adicionar filtros de quartos e vagas.
- [ ] Implementar paginação, ordenação e preservação de filtros.
- [ ] Corrigir slug e URLs canônicas.
- [ ] Aplicar regras de visibilidade dos anúncios.
- [ ] Implementar foto de capa e melhorar galeria.
- [ ] Validar links de WhatsApp e fallback de contato.
- [ ] Revisar responsividade e acessibilidade.

**Critério de saída:** o visitante consegue encontrar, consultar e contatar a imobiliária sobre um imóvel com uma experiência consistente em desktop e celular.

## Fase 2 — Painel da imobiliária

- [x] Completar validação dos formulários de imóveis.
- [x] Implementar ordenação e capa das fotos.
- [x] Implementar máquina de estados e histórico.
- [x] Criar edição do perfil público e do logo.
- [x] Implementar métricas de visualização e contato.
- [x] Confirmar regras de equipe e autenticação de contas inativas por revisão estática de `AgencyUserController`, `AgencyAccess` e `UserChecker`; testes automatizados continuam adiados.

**Critério de saída:** a imobiliária administra seus dados e anúncios com segurança e possui informações básicas de desempenho.

## Fase 3 — Painel administrativo

- [x] Implementar aprovação e rejeição de imobiliárias — inclui fila filtrável, histórico, ações protegidas por CSRF e paginação. Notificações por e-mail ficam pendentes de configuração real do mailer.
- [x] Criar gerenciamento global de usuários — inclui filtros, paginação, auditoria, proteção do último administrador e ações POST com CSRF. A recuperação de senha exige configurar/testar o mailer no ambiente de implantação.
- [x] Implementar moderação de anúncios — fluxo de revisão, decisões e histórico existentes; denúncia e detecção de duplicidade permanecem como melhorias futuras.
- [x] Implementar relatórios de utilização — consultas agregadas, filtros e exportação CSV implementados; validar com banco real antes de produção.
- [x] Definir se destaques entram nesta versão — decisão registrada no ADR-0006: destaques editoriais gratuitos e manuais; cobrança dentro do portal fora do escopo do MVP.

**Critério de saída:** o administrador consegue operar a plataforma sem depender de alterações manuais no banco.

## Fase 4 — Monetização, se aprovada

- [ ] Definir planos, preços e limites.
- [ ] Modelar planos, assinaturas e histórico.
- [ ] Escolher provedor de pagamento.
- [ ] Implementar webhooks seguros e idempotentes.
- [ ] Aplicar limites por plano.
- [ ] Criar painel de cobrança e testes de ciclo de vida.

**Critério de saída:** benefícios e limites correspondem ao estado real da assinatura. Se a monetização não for necessária agora, registrar como fora do escopo.

## Fase 5 — Homologação e deploy

- [ ] Executar a suíte automatizada.
- [ ] Revisar manualmente todos os fluxos críticos.
- [ ] Testar layouts e acessibilidade.
- [ ] Validar migrations em cópia do banco.
- [ ] Fazer backup do banco e dos uploads.
- [ ] Documentar publicação e rollback.
- [ ] Validar o processo compatível com a hospedagem real, inclusive se for FTP sem SSH ou Composer.
- [ ] Executar smoke tests após publicar: login, busca, detalhe, contato, upload, status, aprovação e permissões.

**Critério de saída:** versão testada, publicável e com plano de recuperação.

---

# 8. Diretrizes obrigatórias de UX/UI

Todas as implementações de interface devem seguir estas regras.

- [ ] Preservar a identidade visual já estabelecida no Casa & Costa.
- [ ] Reutilizar tipografia, cores, espaçamentos, cartões e padrões de botões existentes.
- [ ] Reutilizar componentes para formulários, alertas, badges, tabelas e estados vazios.
- [ ] Manter uma ação primária clara por tela.
- [ ] Usar linguagem de negócio em português: “Publicado”, “Pausado”, “Aguardando aprovação”.
- [ ] Não exibir enums internos nem mensagens técnicas ao usuário final.
- [ ] Mostrar sucesso somente depois de confirmar a persistência da ação.
- [ ] Preservar os valores preenchidos quando um formulário falhar.
- [ ] Apresentar erros junto aos campos correspondentes.
- [ ] Pedir confirmação contextual para operações destrutivas ou de impacto público.
- [ ] Fornecer feedback visual para upload, busca, filtros e mudanças de status.
- [ ] Garantir foco visível, navegação por teclado e contraste adequado.
- [ ] Projetar as telas mobile desde o início, em vez de apenas reduzir o layout desktop.
- [ ] Evitar adicionar bibliotecas pesadas sem necessidade.
- [ ] Validar estados de sucesso, erro, vazio, carregamento e acesso negado.
- [ ] Não criar uma interface administrativa genérica e desconectada da identidade do portal.

### Princípio de design

**Elegância não deve prejudicar a clareza.** O usuário deve entender onde está, o que pode fazer, o que aconteceu após uma ação e qual é o próximo passo.

---

# 9. Decisões de produto pendentes

- [ ] O cadastro de imobiliárias será público ou restrito ao administrador?
- [ ] Todo anúncio precisará de aprovação antes de ser publicado?
- [ ] Qual será o limite de fotos por imóvel?
- [ ] Quantas fotos serão exigidas para publicar?
- [ ] Destaques serão gratuitos, editoriais ou monetizados?
- [ ] Planos e assinaturas serão implementados agora ou em uma versão futura?
- [ ] Quais eventos contarão como visualização e contato?
- [ ] Por quanto tempo os eventos e contatos serão armazenados?
- [ ] O contato será exclusivamente externo ou haverá formulário interno?
- [ ] Rua e número poderão ser exibidos publicamente?
- [ ] Quais métricas cada imobiliária poderá consultar?
- [ ] Qual é o ambiente real de produção e quais ferramentas de deploy estão disponíveis?

---

# 10. Definition of Done — definição de concluído

Uma tarefa só poderá ser considerada concluída quando:

- [ ] A implementação segue a arquitetura e as convenções existentes.
- [ ] A interface preserva o padrão visual do portal.
- [ ] A autorização é aplicada no backend.
- [ ] Os dados são validados no servidor.
- [ ] O fluxo funciona em caso de sucesso, erro, dados vazios e acesso negado.
- [ ] Existem testes adequados para regras de negócio e segurança.
- [ ] As migrations foram revisadas e testadas.
- [ ] A interface foi validada em desktop e celular.
- [ ] A navegação por teclado e as mensagens acessíveis foram verificadas.
- [ ] Não há exposição de dados privados, credenciais ou detalhes internos.
- [ ] A documentação operacional foi atualizada.

---

# 11. Resumo de prioridades

## P0 — Segurança e consistência

- Autenticação de contas inativas.
- Bloqueio efetivo de imobiliárias bloqueadas.
- Isolamento dos dados entre imobiliárias.
- Validação robusta de uploads.
- Transições de status válidas.
- Revisão das configurações e do schema real.
- Proteção das rotas administrativas.

## P1 — Requisitos centrais

- Filtros de quartos e vagas.
- Paginação e ordenação do catálogo.
- Correção de slugs e visibilidade pública.
- Organização das fotos e foto de capa.
- Perfil público editável da imobiliária.
- Métricas e contatos.
- Aprovação de imobiliárias.
- Gerenciamento global de usuários.
- Moderação de anúncios.
- Relatórios básicos da plataforma.
- Testes e revisão responsiva.

## P2 — Melhorias e decisões de produto

- SEO avançado e otimização de imagens.
- Exportações.
- Destaques de imóveis.
- Planos e assinaturas.
- Integração com pagamentos.
- Recursos adicionais de analytics.

---

## Conclusão

O portal já possui uma base funcional relevante: catálogo público, detalhes de imóveis, filtros básicos, operações de anúncios e gerenciamento inicial de imobiliárias e funcionários.

O caminho recomendado é **completar os fluxos existentes, reforçar segurança e persistência, cobrir as regras com testes e preservar a experiência visual atual**. Não é necessário reescrever tudo para alcançar os requisitos.

A implementação de destaques e monetização deve depender das decisões comerciais. As funcionalidades centrais e a segurança devem ser concluídas antes dessas expansões.


## Atualização de implantação e onboarding (2026-10-24)

- Adicionado `src/Controller/Admin/AgencyManagerAdminController.php` e formulário administrativo para cadastrar o primeiro gestor de uma imobiliária sem intervenção direta no banco. O administrador define e confirma a senha inicial; a senha é armazenada como hash e as credenciais são compartilhadas manualmente por canal seguro. A conta recebe papel fixo de gestor e auditoria; `UserChecker` continua bloqueando a autenticação até a aprovação. Na listagem administrativa, os links de edição e cadastro do primeiro gestor são apresentados como botões visuais.
- Adicionado `.env.prod.example` e `docs/operations/ftp-deployment.md` com requisitos de produção, proteção de segredos, document root `public/`, SMTP real e orientações para Apache/Nginx/IIS. `.env.dev` é excluído do ZIP de entrega.
- Adicionado `public/.htaccess` para front controller e proteção básica; regras `.htaccess` de uploads foram reforçadas para extensões de script com sufixos numéricos e `nosniff`. **Não marcar a proteção do upload como verificada em produção** até confirmar a tecnologia/configuração do host e testar as regras efetivas.
- O envio de e-mail continua dependente de `MAILER_DSN`/`MAILER_FROM` reais. A configuração `null://null` descarta mensagens; homologação deve validar entrega e definição de senha antes de liberar o onboarding operacional.
