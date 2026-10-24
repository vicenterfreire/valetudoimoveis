# Instruções de desenvolvimento — ValeTudo Imóveis

Este documento reúne as regras de trabalho acordadas para evoluir o projeto ValeTudo Imóveis. Deve ser consultado antes de iniciar cada novo tópico do `planning.md`.

## 1. Trabalhar a partir do estado mais recente

- Sempre utilizar o ZIP/projeto mais recente fornecido ou produzido na conversa como base para a próxima etapa.
- Preservar as funcionalidades e implementações que já existem.
- Antes de alterar um arquivo, inspecionar seu conteúdo atual e entender como ele se integra ao restante do projeto.
- Não reconstruir o projeto do zero nem substituir silenciosamente arquivos por versões simplificadas.
- Respeitar a estrutura, as tecnologias e as decisões existentes no projeto, salvo quando uma mudança real for necessária para concluir o tópico.

## 2. Trabalhar em lotes de dois tópicos consecutivos

- Seguir a ordem do checklist em `planning.md`.
- Quando solicitado, trabalhar nos dois próximos tópicos reais do checklist por lote, sem pular tópicos.
- Não inventar tópicos ausentes. Se a numeração esperada não existir no arquivo, informar a divergência e usar os próximos dois tópicos existentes em ordem, ou pedir esclarecimento se houver ambiguidade relevante.
- Antes de implementar, revisar os dois tópicos e identificar o que já está pronto, o que está parcialmente implementado e o que realmente falta.
- Não duplicar funcionalidades existentes: completar ou corrigir a implementação atual sempre que possível.
- Ao concluir o lote, revisar o checklist e atualizar o estado de cada tópico separadamente.

## 3. Regra obrigatória: não substituir código funcional por comentários ou placeholders

- **Nunca substituir o corpo de uma função ou método já implementado por comentários, pseudocódigo, `TODO`, `NotImplementedException` ou placeholders.**
- Exemplos proibidos: `// ... implementação da função`, `/* implementar depois */` ou retornar valores artificiais apenas para simular uma implementação.
- Manter o código funcional existente. Se uma alteração for necessária, modificar o mínimo possível e preservar os comportamentos válidos.
- Não declarar uma funcionalidade concluída quando há apenas uma interface sem lógica funcional, uma assinatura sem implementação ou um comentário descrevendo o que deveria acontecer.
- Não remover código existente para simplificar a tarefa sem necessidade técnica concreta.

## 4. Qualidade e segurança

- Implementar a lógica real exigida pelo tópico, não apenas marcar os itens no checklist.
- Validar entradas no servidor; não depender exclusivamente da validação feita no navegador.
- Aplicar autorização no servidor para ações protegidas e garantir o isolamento entre imobiliárias (multi-tenant): uma imobiliária não pode acessar ou alterar dados de outra.
- Usar proteção CSRF nos formulários e ações mutáveis quando aplicável.
- Ao lidar com arquivos enviados, validar o conteúdo real, limitar tamanho/dimensões e usar nomes de arquivo seguros.
- Tratar falhas de forma consistente, evitando deixar banco de dados e sistema de arquivos em estados inconsistentes.
- Evitar expor informações internas ou dados pessoais desnecessários nas páginas públicas, logs e exportações.
- Ao criar consultas, filtros, relatórios ou exportações, considerar autorização, limites de período/volume, entradas inválidas e conteúdo potencialmente perigoso.
- Preservar acessibilidade, responsividade e consistência visual da interface.

## 5. Atualizar o `planning.md` com precisão

- Ao terminar cada tópico, editar o `planning.md` incluído no projeto.
- Marcar `[x]` **somente** os itens efetivamente implementados e verificados no código.
- Deixar `[ ]` nos itens incompletos, parcialmente implementados, não testados ou dependentes de infraestrutura/configuração que ainda não existe.
- Se um item estiver parcialmente concluído, registrar claramente o que foi feito e o que permanece pendente; não o apresentar como totalmente concluído.
- Manter os tópicos futuros sem alterações indevidas.
- Não remover requisitos pendentes para fazer o tópico parecer concluído.

## 6. Verificação antes de entregar

Sempre que as ferramentas e o ambiente permitirem:

1. Executar verificações de sintaxe dos arquivos PHP alterados e, idealmente, de todo o diretório `src/`.
2. Verificar a integridade do ZIP gerado.
3. Revisar as diferenças para detectar placeholders, implementações removidas, imports quebrados, rotas duplicadas e inconsistências evidentes.
4. Verificar templates Twig e migrations estaticamente quando possível.
5. Executar testes automatizados e testes de integração somente se houver ambiente e dependências disponíveis.
6. Distinguir claramente as verificações executadas das que não puderam ser feitas.

- Nunca afirmar que a aplicação foi testada em runtime, no navegador ou contra o banco de dados se isso não aconteceu.
- Se faltarem dependências, configuração, banco ou infraestrutura, informar a limitação e listar a validação pendente.

## 7. Entrega obrigatória ao concluir cada tópico

Após implementar o tópico:

- Atualizar o código e o `planning.md`.
- Gerar um ZIP contendo o projeto atualizado e o checklist revisado.
- Validar que o ZIP foi criado e pode ser aberto.
- Disponibilizar o ZIP por link para download.
- Disponibilizar também um link para o `planning.md` atualizado, quando houver um arquivo separado acessível.
- Resumir as mudanças relevantes em português.
- Informar as verificações realizadas, as limitações e os itens que continuam pendentes.
- Indicar qual é o próximo tópico do checklist, sem implementá-lo antes da solicitação.

## Estado do último lote — catálogo público (2026-10-23)

- Implementados filtros por área mínima/máxima e quantidade mínima de banheiros, com validação no servidor, remoção individual de filtros e preservação dos parâmetros de busca.
- Criada migration de índices para as consultas públicas por status/data e preço. A migration não foi aplicada a nenhum banco neste ambiente.
- A persistência analítica de visualizações e contatos já está no código e só registra quando há consentimento explícito de métricas.
- O usuário pediu para priorizar funcionalidades e deixar os testes automatizados para depois. Não iniciar a suíte neste momento nem marcar T1–T5 como concluídos. Faça verificações estáticas necessárias e siga para as funcionalidades pendentes mais relevantes.

## 8. Interface e experiência do usuário

- Manter a interface elegante, intuitiva, responsiva e consistente com o restante do projeto.
- Priorizar textos claros em português, estados vazios úteis, mensagens de sucesso/erro compreensíveis e confirmações para ações destrutivas ou importantes.
- Considerar acessibilidade por teclado, rótulos adequados, foco visível e alternativas acessíveis a interações exclusivamente por mouse.
- Evitar regressões visuais ou funcionais nas telas já concluídas.

## 9. Restrições do ambiente e do projeto

- O projeto é um portal imobiliário regional desenvolvido com Symfony/PHP, destinado a hospedagem com restrições (incluindo FTP, PHP até 8.2 e MySQL, conforme o ambiente descrito).
- Não presumir que Composer, comandos Symfony, workers, tarefas agendadas ou extensões específicas estejam disponíveis no servidor sem confirmação.
- Quando uma funcionalidade depender de migration, extensão PHP, agendamento ou configuração de hospedagem, incluir os artefatos necessários quando cabível e documentar os passos e dependências.
- Evitar introduzir dependências externas pesadas ou incompatíveis sem necessidade clara.
- Não criar ou alterar arquivos de configuração fora do escopo solicitado; quando forem indispensáveis, explicar o motivo e limitar a mudança.

## 10. Princípio de conclusão

Um tópico só pode ser considerado concluído quando:

- a implementação real correspondente existe;
- os requisitos e as verificações possíveis foram revisados;
- o checklist reflete fielmente o estado do código;
- as limitações e pendências estão documentadas;
- o ZIP atualizado foi gerado e validado.

**A prioridade é preservar o trabalho existente, implementar de verdade o que falta e reportar o estado com transparência — não apenas fazer o checklist parecer completo.**

## Estado recente da implementação (2026-10-23)

- S1/S2: logout via POST + CSRF; revalidação de conta e imobiliária no `UserChecker`; flags de cookies e cabeçalhos de segurança; migration para índices; política de exclusão e runbook de backup/restauração documentados. Rate limiting, schema real, testes cruzados e restore real continuam pendentes.
- S3: uploads de imagens e logos são validados e reprocessados; limites por arquivo/lote/imóvel; caminhos de remoção restritos a padrões gerados e diretórios canônicos; ações de upload/remoção protegidas por autorização e CSRF. `.htaccess` precisa ser confirmado no host; não guardar documentos privados em `public/uploads`.
- S3 inclui `app:uploads:purge-orphans`, que apenas simula por padrão, só considera arquivos gerados com pelo menos 24 horas e requer `--execute` para remover. Revisar a lista em homologação antes de usar em produção.
- S4: página `/privacidade`, escolha de consentimento para métricas opcionais, minimização de logs, auditoria de operações administrativas e inventário em `docs/privacy/data-inventory-and-retention.md`. Política-alvo de 365 dias para eventos analíticos e 730 dias para auditoria; comandos existem, mas agendamento depende do host.
- Aviso de privacidade e consentimento requerem revisão do responsável pelo portal/assessoria jurídica; não declarar conformidade LGPD completa apenas por esses artefatos.
- Nunca declarar S3/S4 integralmente validados em runtime: embora o pacote atual inclua `vendor/` e permita lint/console, servidor de hospedagem, banco configurado, regras reais do servidor web e agendamento de retenção ainda precisam ser validados operacionalmente.
- Não executar migrations em produção sem backup e validação prévia em homologação.

## Estado de testes automatizados (2026-10-23)

- `composer.json` e `composer.lock` estão presentes; as dependências de desenvolvimento já declaram PHPUnit 11.5 e Symfony BrowserKit.
- A configuração de PHPUnit foi padronizada em `phpunit.xml.dist`; o comando recomendado é `composer test`.
- `.env.test` usa um banco SQLite de teste separado, evitando apontar testes para o banco declarado em `.env`. Habilite `pdo_sqlite` para executar futuros testes que precisem de banco.
- Foram adicionados testes unitários para `UserChecker`, `AgencyAccess` e `UserManagementPolicy`, além de testes HTTP de acesso anônimo. Eles ainda não foram executados por decisão do usuário de priorizar a conclusão funcional e postergar testes automatizados; o pacote atual inclui `vendor/`, mas a suíte requer ambiente PHP compatível e banco de teste configurado.
- Não marcar T1/T2 como concluídos até instalar dependências, executar os testes, corrigir falhas e complementar cobertura autenticada/CSRF. Consulte `docs/testing/automated-tests.md`.

## Atualização do ambiente de testes (2026-10-23)

- O usuário forneceu `vendor.zip`, que foi extraído no projeto de trabalho. `php bin/console` inicia e o container Symfony pode ser validado.
- Neste executor, `php bin/phpunit --testdox` ainda não inicia porque o PHP CLI não possui `dom`, `mbstring` e `xmlwriter`; também não há drivers PDO disponíveis.
- Validações concluídas neste ambiente: container Symfony, 23 templates Twig, 24 arquivos YAML, mapeamento de 10 entidades e tabela de rotas. Isso não substitui a execução dos testes automatizados nem testes com banco.
- Não marque T1/T2 como concluídos enquanto a suíte não rodar e os testes autenticados/CSRF não forem completados.

## Atualização — painel da imobiliária

- O formulário de imóveis usa seções para informações, características, valores e localização, preservando os campos Symfony e seus erros de validação.
- `PropertyImage` possui `thumbnailPath` nullable. `PropertyImageUploader` gera miniaturas de até 480 px para novos uploads, além do arquivo principal otimizado; `Version20261023150000` cria a coluna. Fotos antigas sem miniatura usam fallback para o arquivo principal.
- Listagens públicas, galeria secundária e painéis usam miniaturas quando disponíveis. A imagem principal da página de detalhes continua em resolução maior.
- A rotina `app:uploads:purge-orphans` considera miniaturas e logos referenciados e mantém simulação como padrão.
- O envio de fotos exibe progresso percentual por XHR. Validar fluxo e flash messages no navegador/hosting; testes automatizados foram adiados a pedido do usuário.
- Antes de produção, revisar e aplicar a migration `Version20261023150000` em homologação com backup; para hosting FTP sem CLI, consultar `docs/operations/property-image-thumbnails.md`. Confirmar GD e formatos de imagem suportados no PHP da hospedagem.

## Atualização de continuidade — C1/C2 e paginação administrativa

Ao continuar o projeto, preserve a paginação implementada nas listas globais de imobiliárias e usuários: 25 registros por página, contagem total e parâmetros de filtro preservados nos links. Os repositórios recebem `page` e `perPage`; não reintroduzir busca com limite fixo seguido de filtragem em memória. A consulta de papel usa `LIKE` sobre o token completo entre aspas no JSON `roles`, assumindo MySQL/MariaDB; valide-a no banco de homologação antes de produção.

O painel administrativo foi considerado funcionalmente implementado na Fase 3, mas ainda não homologado. Notificações de status de imobiliárias e entrega de recuperação de senha não estão concluídas em ambiente real: `.env` usa `MAILER_DSN=null://null`, então configure `MAILER_DSN` e `MAILER_FROM` via ambiente da hospedagem e teste a entrega. Não marcar testes automatizados como executados; o usuário pediu para adiá-los e priorizar funcionalidades. Antes de produção, aplicar migrations em homologação após backup e fazer smoke tests manuais.

## Atualização de continuidade — paginação da moderação C3

A listagem administrativa de imóveis usa paginação de 25 registros, com total de resultados e filtros preservados. `PropertyRepository::findForModeration()` não deve voltar a juntar a coleção `images` na consulta de listagem: a tabela não renderiza imagens, e o join poderia duplicar linhas SQL e prejudicar o limite por página. A página de revisão individual continua carregando as fotos e o histórico.

## Regras adicionais para onboarding e implantação

- Ao criar o primeiro gestor de uma imobiliária, não enviar nem exibir senhas em texto claro. Usar senha aleatória inicial e link de definição de senha com token de uso único e expiração; proteger POST com CSRF, validar e-mail único e registrar auditoria sem dados sensíveis. A agência pendente/bloqueada nunca pode autenticar só porque o gestor já definiu a senha.
- Para deploy FTP, manter `public/` como único document root público, não distribuir `.env.dev` nem segredos e documentar a configuração de `APP_ENV=prod`, `APP_DEBUG=0`, `APP_SECRET`, banco e SMTP. Arquivos `.env.*.example` são modelos, não configurações prontas.
- Regras `.htaccess` não provam proteção efetiva de uploads: confirmar servidor, `mod_rewrite`, `AllowOverride` e testar no host. Para Nginx/IIS, documentar e solicitar regras equivalentes; não marcar o requisito como concluído sem validação real.

## Atualização de continuidade — primeiro gestor da imobiliária (2026-10-24)

O cadastro administrativo do primeiro gestor usa senha inicial definida e confirmada pelo administrador (mínimo de 12 caracteres), hash via serviço oficial do Symfony, validação CSRF, verificação de e-mail duplicado e auditoria. Não deve depender de e-mail de convite para concluir o cadastro. As credenciais devem ser compartilhadas manualmente por canal seguro; a interface orienta a troca da senha após o primeiro acesso, sem afirmar que essa troca é forçada. Na lista de imobiliárias, os links `Editar dados` e `Cadastrar primeiro gestor` devem manter a apresentação de botões por meio das classes CSS existentes.
