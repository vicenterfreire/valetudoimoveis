# ADR 0006 — Destaques editoriais e escopo de monetização

- **Status:** Aceito para o MVP (destaques editoriais); monetização ainda pendente.
- **Contexto:** O portal precisa de uma seção de destaques, mas não há plano/preço/provedor de cobrança aprovado no checklist.
- **Decisão:** Os destaques são editoriais, gratuitos e concedidos manualmente por um administrador global. Cada concessão tem período explícito, máximo de 90 dias, motivo e responsável. A página inicial mostra no máximo seis destaques vigentes e elegíveis.
- **Elegibilidade pública:** o imóvel precisa estar publicado, aprovado na moderação, vinculado a uma imobiliária ativa e dentro do período de destaque. O destaque é armazenado independentemente do status comercial/moderação para preservar histórico, mas a elegibilidade é reavaliada em cada consulta pública.
- **Fora desta decisão:** planos/assinaturas, preços, limites comerciais, provedor de pagamento, webhooks e cobrança automatizada. Não implementar até que o escopo comercial seja confirmado.
- **Consequências:** não é necessária tarefa agendada para expiração, pois a consulta filtra pelo horário atual. A migration `Version20261023120000` deve ser aplicada antes do uso.
