# ADR-0002: Respeitar as restrições de hospedagem e runtime

- **Status:** Accepted
- **Data:** 2026-10-10

## Contexto

A aplicação será hospedada em ambiente no qual o deploy é feito por FTP e a versão máxima disponível de PHP é 8.2. O banco disponível será MySQL. O proprietário já implantou projetos Symfony nesse tipo de hospedagem e conhece o processo de preparar dependências localmente.

## Decisão

A arquitetura e as dependências devem ser compatíveis com PHP 8.2 e deploy por FTP, sem depender de Composer, Docker, workers permanentes ou comandos administrativos disponíveis somente no servidor.

## Alternativas consideradas

- **Hospedagem com SSH/Composer e serviços gerenciados:** simplifica operações, mas não é a restrição atual.
- **Aplicação dependente de processos residentes ou infraestrutura dedicada:** não é adequada sem confirmar suporte do provedor.
- **Preparar dependências localmente e enviar artefato por FTP:** compatível com a restrição conhecida, exigindo processo cuidadoso de build e deploy.

## Consequências

- A versão do Symfony e cada dependência devem suportar PHP 8.2.
- Recursos que exigem cron, filas, workers ou extensões específicas não podem ser assumidos; devem ser verificados antes de serem introduzidos.
- Segredos não devem ser commitados nem expostos em diretórios públicos.
- O deploy deve excluir arquivos locais desnecessários e evitar sobrescrever uploads ou configuração de produção.
- A existência de FTP não garante SFTP, SSH, cron, acesso a logs ou execução de migrations; essas capacidades precisam ser confirmadas.
