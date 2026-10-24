# Métricas de contato — definição e privacidade

## Eventos medidos

- **Visualização (`view`)**: abertura da página pública de um imóvel que está publicado e pertence a uma imobiliária ativa. A rota canônica após redirecionamento é contabilizada; páginas de listagem, imóveis semelhantes e páginas de imobiliárias não são visualizações de imóvel para este relatório.
- **Clique de contato (`contact`, canal `whatsapp`)**: envio válido do formulário do botão de WhatsApp, após validar CSRF e confirmar que o imóvel e a imobiliária ainda estão publicamente disponíveis. Isso indica que o portal iniciou o redirecionamento, não que uma mensagem foi enviada ou lida no WhatsApp.
- **Telefone e e-mail**: os links diretos `tel:` e `mailto:` não são medidos nesta versão. Não devem ser interpretados como zero contatos reais.
- **Anúncios por status**: inventário atual, calculado no momento da consulta. Não representa o histórico dos status durante o período escolhido.

## Deduplicação e limitações

Para cada sessão do navegador, imóvel e tipo/canal, o portal registra no máximo um evento a cada 30 minutos. Essa regra reduz repetição acidental e abuso simples, mas não identifica visitantes únicos nem impede usuários com sessões novas, bots ou automação sofisticada. As visualizações são aproximadas e não devem ser usadas como métrica auditada ou faturamento.

O relatório usa intervalo inclusivo para as datas selecionadas. Internamente, a data final é convertida para o início do dia seguinte, de modo que todo o dia final seja incluído. O intervalo máximo é de 366 dias.

## Isolamento

As consultas agregadas sempre filtram pela entidade `Agency` derivada do usuário autenticado via `AgencyAccess`. Não é aceito ID de imobiliária vindo do navegador. O evento também valida, no construtor, que o imóvel pertence à imobiliária registrada no evento.

## Dados armazenados e retenção

Cada evento armazena somente imobiliária, imóvel, tipo de evento, canal (quando aplicável) e data/hora. Não são armazenados IP, agente do navegador, nome, telefone, e-mail de visitante, identificador de usuário ou conteúdo de conversa. A sessão é usada apenas para deduplicação e não é persistida como identificador do evento.

A retenção padrão é de **365 dias**. A rotina `php bin/console app:contact-events:purge` remove registros antigos; ela deve ser agendada pelo operador do hosting (por exemplo, cron diário), pois não é executada automaticamente pelo portal. A opção `--retention-days` aceita valores entre 30 e 3650 dias. Faça backup antes da primeira execução em produção.

## Exportação CSV

A exportação respeita o mesmo filtro de imobiliária e período da tela, inclui métricas agregadas e status atual, usa UTF-8 com BOM para compatibilidade com planilhas e neutraliza células de texto que poderiam ser interpretadas como fórmulas.
