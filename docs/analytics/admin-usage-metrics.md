# Definições dos relatórios administrativos

## Escopo e filtros

- A tela é exclusiva de administradores globais (`ROLE_ADMIN`).
- O período usa datas inclusivas no formulário; no banco, a consulta aplica `>= início` e `< dia seguinte ao fim`.
- O intervalo máximo é de 366 dias.
- O filtro de imobiliária afeta contagens de usuários, imóveis e séries temporais. As contagens de imobiliárias por status permanecem globais para não confundir um subconjunto com o total do portal.
- Os relatórios exibem agregados e não listam nomes, e-mails, telefones ou outros dados de pessoas. A série mensal preenche meses sem atividade com zero para evitar lacunas ambíguas.

## Definições

- **Imobiliárias ativas, pendentes e bloqueadas:** quantidade atual de registros em cada status. Rejeitadas existem no domínio, mas não são incluídas nos três cartões solicitados.
- **Usuários ativos/inativos:** quantidade atual de contas por `is_active`, com filtro opcional de imobiliária.
- **Imóveis por status:** estado comercial atual (`draft`, `published`, `paused`, `sold`, `rented`, `archived`), com filtro opcional de imobiliária.
- **Status de moderação:** estado atual da revisão (`pending`, `approved`, `rejected`, `changes_requested`), com filtro opcional de imobiliária.
- **Anúncios cadastrados por mês:** contagem de imóveis pela data `created_at` dentro do período; não representa a quantidade de imóveis atualmente publicados.
- **Visualizações:** eventos `view` gravados pela instrumentação existente. A aplicação tenta limitar repetições do mesmo evento por sessão/imóvel a uma a cada 30 minutos.
- **Cliques de contato:** eventos `contact` gravados quando o visitante aciona o contato por um dos canais suportados. São cliques registrados, não prova de conversa concluída ou negócio fechado.

## Retenção e privacidade

- `ContactEvent` persiste imobiliária, imóvel, tipo de evento, canal opcional e data/hora; não armazena IP, e-mail ou identificador de visitante. Como os eventos continuam ligados aos IDs de imobiliária/imóvel, não devem ser descritos como anonimizados de forma irreversível.
- O comando `app:contact-events:purge` usa 365 dias como padrão e aceita valores entre 30 e 3650 dias.
- Como o ambiente de hospedagem pode ser apenas FTP, não se presume que o comando esteja agendado. A hospedagem precisa permitir cron/tarefa agendada ou o responsável deverá executar a limpeza manualmente de forma periódica.
- Exportações contêm apenas agregados e são geradas sob demanda, com `Cache-Control: no-store, private`. Textos provenientes do banco são neutralizados contra início de fórmula CSV.

## Limitações conhecidas

- Não há eventos históricos de anúncios; a evolução mensal usa `created_at` dos registros existentes.
- Não são deduplicados visitantes por IP/cookie global, nem há rastreamento entre dispositivos. A limitação de 30 minutos é baseada na sessão.
- A validação de runtime das consultas SQL e da renderização Twig exige dependências instaladas e banco de teste.
