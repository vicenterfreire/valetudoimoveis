# ADR-0011: Armazenar imagens como arquivos e manter referências no banco

- **Status:** Proposed
- **Data:** 2026-10-10

## Contexto

Cada imóvel pode ter diversas fotos. Armazenar o conteúdo binário diretamente nas tabelas complicaria backups, consultas e entrega de imagens.

## Decisão proposta

Armazenar arquivos no sistema de arquivos da hospedagem e manter no MySQL somente o caminho relativo e metadados. Se houver diretório fora do webroot, preferi-lo; caso contrário, usar diretório público com validação rigorosa e impedir execução de scripts.

## Alternativas consideradas

- **BLOBs no banco:** simplifica consistência transacional, mas aumenta tamanho e custo de backup e não é ideal para entrega de mídia.
- **Arquivos no hosting:** simples e compatível com hospedagem compartilhada, mas exige backups coordenados e gerenciamento de arquivos órfãos.
- **Object storage externo:** melhor separação e escalabilidade, mas adiciona serviço, credenciais e custos.

## Consequências

- Validar MIME/type real, extensão permitida, tamanho e dimensões; não confiar apenas no nome ou Content-Type enviado.
- Gerar nomes aleatórios, limitar quantidade/tamanho e evitar execução de PHP no diretório de upload.
- Guardar caminhos relativos, nunca caminhos absolutos do servidor.
- Definir limites reais após confirmar o plano de hospedagem.
- Esta decisão depende de verificar se o provedor permite armazenamento fora do webroot e como realiza backup.
