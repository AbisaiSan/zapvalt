# ZapVault: brief e prompt para o Claude Design

Oct 20, 2026 · @Abisai

## Como usar

Cole o prompt principal no Claude Design e, se ele aceitar anexos, anexe este documento como brief de apoio; o prompt já traz o essencial sozinho.

1. Copie o bloco da seção "Prompt principal" e cole no Claude Design.
2. Anexe este documento (ou exporte-o em PDF) para dar os detalhes de telas, cores e dados fictícios.
3. Revise a primeira versão e use os prompts da seção "Prompts de refinamento", um de cada vez.

O protótipo é só visual: dados fictícios, sem servidor. Ele serve para validar o layout antes de programar em Laravel.

## Prompt principal

Cole o bloco abaixo inteiro no Claude Design; ele descreve as 8 telas, o visual e os dados fictícios.

```text
Crie um protótipo navegável de alta fidelidade, em português do Brasil, de um aplicativo web chamado ZapVault. Use apenas dados fictícios e nenhum servidor.

CONTEXTO
O ZapVault é um arquivo pessoal e local de conversas antigas do WhatsApp Business. Serve só para consultar: não envia nem recebe mensagens. O usuário importa conversas exportadas (arquivos .txt e .zip), procura por nome de contato ou grupo ou por qualquer palavra ou frase dentro das mensagens, e abre a conversa completa com fotos, vídeos, áudios e documentos.

REFERÊNCIA VISUAL
Layout em duas colunas inspirado no WhatsApp Web: lista de conversas à esquerda, conversa aberta à direita, balões com horário no canto, separadores de dia e avisos de sistema centralizados. Não use o logotipo nem a marca WhatsApp. Use o nome ZapVault e um ícone simples próprio (cofre ou balão com cadeado).

TELAS (todas navegáveis)
1. Principal: coluna esquerda com cabeçalho (ícone, nome, botões Importar e Configurações), campo de busca e lista de conversas (avatar com iniciais, nome, prévia da última mensagem, data). Coluna direita vazia com o texto "Selecione uma conversa" e um resumo do acervo (quantidade de conversas e de mensagens).
2. Conversa aberta: cabeçalho com nome, período coberto e ícone de lupa. Mensagens do usuário à direita em verde claro, as da outra pessoa à esquerda em branco, horário em cada balão, separador de dia, avisos de sistema centralizados. Em grupos, nome do remetente colorido. Sem campo para digitar e sem botão de enviar: no rodapé, uma faixa discreta "Arquivo somente leitura".
3. Anexos nos balões: imagem em miniatura, áudio com player e duração, vídeo com botão de play, documento com ícone, nome e tamanho, figurinha, e o aviso "mídia não incluída na exportação".
4. Visualizador de imagem em tela cheia, com setas para anterior e próxima, nome do arquivo, data e botão de baixar.
5. Busca global: ao digitar no campo, a lista dá lugar a resultados com duas abas, "Conversas" (por nome) e "Mensagens" (por conteúdo). Cada resultado mostra o trecho com o termo destacado, o nome da conversa e a data. Ao clicar, abre a conversa já rolada até a mensagem, que fica destacada por alguns segundos.
6. Busca dentro da conversa: barra no topo com contador ("3 de 12") e botões anterior e próximo.
7. Importar: área de arrastar e soltar arquivos, lista com status (aguardando, processando com barra de progresso, concluído, erro), resumo por conversa (mensagens novas, repetidas, anexos encontrados e faltantes) e uma pergunta de conferência do nome ("Este é o seu nome nas mensagens?").
8. Configurações: seu nome nos arquivos, tema claro ou escuro, local de armazenamento, botão de backup.

SISTEMA VISUAL
Tema claro e escuro, com alternância. Verde de destaque #00A884. Claro: fundo da conversa #EFEAE2, balão enviado #D9FDD3, balão recebido #FFFFFF, cabeçalhos #F0F2F5, texto #111B21, texto secundário #667781. Escuro: fundo #0B141A, painéis #111B21, cabeçalhos #202C33, balão enviado #005C4B, balão recebido #202C33, texto #E9EDEF, texto secundário #8696A0. Fonte do sistema (Segoe UI, Helvetica Neue, sans-serif), mensagens em 14 px e horários em 11 px, cantos de 8 px nos balões. Tamanho principal 1440 x 900; em telas estreitas, mostrar uma coluna por vez.

DADOS FICTÍCIOS
Crie 12 conversas, 3 delas grupos, com nomes como Maria Souza, João Pereira, Gráfica Rápida, Grupo Projeto Alfa e Grupo Financeiro. Deixe uma conversa longa aberta, com cerca de 30 mensagens em dois dias, incluindo: uma mensagem de várias linhas, um aviso de criptografia, uma foto, um áudio de 0:42, um PDF "Proposta_v3.pdf, 240 KB", uma mensagem apagada e uma chamada de voz perdida. A busca por "proposta" deve retornar resultados em pelo menos 4 conversas, e "PROPOSTA" e "Proposta" devem dar o mesmo resultado.

INTERAÇÕES
Ctrl+K (Cmd+K no Mac) foca a busca. Clicar numa conversa a abre; clicar numa imagem abre o visualizador; Esc fecha. A busca ignora maiúsculas, minúsculas e acentos. Mostre estados de carregamento (esqueletos), de lista vazia e de "nenhum resultado".

ENTREGA
Um protótipo clicável com as 8 telas, tema claro e escuro, pronto para eu pedir ajustes depois.
```

## Brief: telas e estados

O protótipo tem 8 telas, e cada uma precisa mostrar seus estados principais, não só o caso ideal.

| Tela | Componentes | Estados a mostrar |
| --- | --- | --- |
| 1. Principal | Cabeçalho (ícone ZapVault, botões Importar e Configurações), campo de busca, lista de conversas, painel direito com resumo do acervo | Sem conversas importadas (com chamada para importar), com conversas, conversa selecionada em destaque, carregando (esqueletos) |
| 2. Conversa aberta | Cabeçalho (avatar, nome, período coberto, lupa), área de mensagens, faixa "Arquivo somente leitura", botões para ir ao fim e para pular a uma data | Carregando mais mensagens ao rolar para cima, início do histórico alcançado, conversa sem mensagens |
| 3. Anexos no balão | Miniatura de imagem, áudio com player e duração, vídeo com play, documento (ícone, nome, tamanho), figurinha, aviso de mídia faltante | Carregando, erro ao abrir, mídia ausente |
| 4. Visualizador de imagem | Imagem em tela cheia, setas, nome, data, baixar, fechar | Primeira imagem (seta anterior desativada), última imagem |
| 5. Busca global | Campo, abas Conversas e Mensagens, resultados com trecho e termo destacado, contador de resultados | Digitando, nenhum resultado, muitos resultados com rolagem |
| 6. Busca na conversa | Barra com campo, contador ("3 de 12"), anterior, próximo, fechar; termo destacado nas mensagens | Sem ocorrências, ocorrência atual em cor mais forte que as demais |
| 7. Importar | Área de arrastar e soltar, lista de arquivos com status e barra de progresso, resumo por conversa, pergunta de conferência do nome, botão Concluir | Vazio, em andamento, concluído, com erro (linhas não reconhecidas), conversa que já existe (somar ou manter separada) |
| 8. Configurações | Nome (ou nomes) nos arquivos, tema, local de armazenamento, backup, senha opcional | Salvo, senha ativada |

## Brief: sistema visual

O visual segue a lógica de cores e proporções do WhatsApp Web, em dois temas. Os valores são referências aproximadas e podem ser ajustados para garantir contraste mínimo AA.

| Elemento | Tema claro | Tema escuro |
| --- | --- | --- |
| Destaque (botões, contador, realce) | #00A884 | #00A884 |
| Fundo da área de conversa | #EFEAE2 | #0B141A |
| Painel e lista de conversas | #FFFFFF | #111B21 |
| Cabeçalhos e campos de busca | #F0F2F5 | #202C33 |
| Item selecionado na lista | #F0F2F5 | #2A3942 |
| Balão enviado (do usuário) | #D9FDD3 | #005C4B |
| Balão recebido | #FFFFFF | #202C33 |
| Texto principal | #111B21 | #E9EDEF |
| Texto secundário e horários | #667781 | #8696A0 |
| Divisores | #E9EDEF | #222D34 |

**Tipografia e medidas**

- Fonte do sistema (Segoe UI, Helvetica Neue, sans-serif). Mensagens em 14 px, horários em 11 px, nome na lista e no cabeçalho em 16 px, prévia da lista em 14 px.
- Coluna da lista com 30% da largura, entre 320 e 420 px. Item da lista com 72 px de altura e avatar circular de 48 px com iniciais.
- Balões com cantos de 8 px, largura máxima de 65% da coluna de conversa, horário no canto inferior direito.
- Nomes de remetentes em grupo com uma paleta de 8 cores distintas, sempre legíveis nos dois temas.
- Ícones de linha, 24 px. Sombras mínimas; separação por cor e divisores finos.

**Identidade do produto.** Nome ZapVault e um ícone próprio simples (cofre ou balão com cadeado). Sem logotipo nem marca do WhatsApp.

## Brief: dados fictícios

Todo o conteúdo do protótipo é inventado, para que nenhuma conversa real seja usada; os nomes e textos abaixo servem de ponto de partida.

**Lista de conversas (12)**

| Conversa | Tipo | Prévia da última mensagem | Data |
| --- | --- | --- | --- |
| Maria Souza | Individual | Segue o PDF com os valores atualizados. | Hoje 09:21 |
| Grupo Projeto Alfa | Grupo | Carlos: reunião confirmada para quinta | Ontem |
| João Pereira | Individual | Perfeito, obrigado! | Ontem |
| Gráfica Rápida | Individual | Orçamento da proposta em anexo | Segunda |
| Grupo Financeiro | Grupo | Ana: conciliação fechada | Segunda |
| Fernanda Lima | Individual | Pode me ligar amanhã cedo? | 14/10/2026 |
| Transportadora Norte | Individual | Pedido 1842 saiu para entrega | 12/10/2026 |
| Ricardo Alves | Individual | Aceitamos a Proposta | 08/10/2026 |
| Clínica Vida | Individual | Confirmo o horário das 15h | 02/10/2026 |
| Grupo Parceiros | Grupo | Luciana: nova PROPOSTA enviada | 28/09/2026 |
| Paulo Mendes | Individual | Bom dia, tudo bem? | 15/09/2026 |
| +55 98 91234-5678 | Individual (número sem nome salvo) | Olá, vi o anúncio | 03/09/2026 |

**Conversa aberta: Maria Souza (completar até cerca de 30 mensagens, com a mesma variedade)**

| Dia | Hora | Quem | Conteúdo |
| --- | --- | --- | --- |
| 18/10 |  | Sistema | As mensagens e ligações são protegidas com a criptografia de ponta a ponta. |
| 18/10 | 09:14 | Maria | Bom dia! Consegue me mandar a proposta? |
| 18/10 | 09:20 | Usuário | Claro, te envio ainda hoje. |
| 18/10 | 09:21 | Usuário | Segue o PDF (quebra de linha) com os valores atualizados. |
| 18/10 | 09:21 | Usuário | Documento: Proposta\_v3.pdf, 240 KB |
| 18/10 | 09:30 | Maria | Foto com legenda: Esse é o espaço |
| 18/10 | 09:41 | Maria | Áudio de 0:42 |
| 18/10 | 09:50 | Usuário | Ouvi, vou ajustar o prazo. |
| 18/10 | 10:02 | Maria | Essa mensagem foi apagada |
| 18/10 | 10:15 | Sistema | Chamada de voz perdida |
| 19/10 | 08:55 | Maria | PROPOSTA aprovada pelo financeiro! |
| 19/10 | 08:57 | Usuário | Ótima notícia! Vou preparar o contrato. |
| 19/10 | 09:10 | Maria | Vídeo de 0:18 |
| 19/10 | 09:30 | Usuário | Mídia não incluída na exportação |

**Grupo Projeto Alfa.** Mensagens de 3 remetentes (Carlos, Ana, Luciana), cada um com uma cor de nome diferente.

**Busca de exemplo.** "proposta" deve achar resultados em Maria Souza, Gráfica Rápida, Ricardo Alves e Grupo Parceiros, com as grafias "proposta", "Proposta" e "PROPOSTA" tratadas como a mesma palavra.

## Brief: interações

O protótipo precisa deixar claro como a busca e a leitura se comportam, porque são o coração do produto.

- **Atalhos.** Ctrl+K (Cmd+K no Mac) foca o campo de busca. Esc limpa a busca, fecha o visualizador ou fecha a busca na conversa.
- **Busca global.** A partir de 2 caracteres, a lista dá lugar aos resultados, com as abas Conversas e Mensagens e a quantidade de cada uma. O termo aparece destacado em cada trecho, sem diferenciar maiúsculas, minúsculas e acentos.
- **Abrir um resultado.** O clique abre a conversa rolada até a mensagem encontrada, que fica destacada por cerca de 2 segundos.
- **Busca na conversa.** Enter vai à próxima ocorrência e Shift+Enter à anterior; a ocorrência atual tem destaque mais forte que as demais.
- **Rolagem.** Mensagens mais antigas carregam ao rolar para cima, com o aviso "Carregando mensagens anteriores". Quando o usuário está longe do fim, aparece um botão para ir ao fim. Um seletor de data no cabeçalho pula para um dia específico.
- **Imagens.** O clique abre o visualizador; as setas do teclado trocam de imagem e Esc fecha.
- **Áudio.** Tocar e pausar, barra de progresso e velocidades 1x, 1,5x e 2x.
- **Documentos.** O clique abre uma pré-visualização de PDF ou baixa o arquivo.
- **Importar.** Ao arrastar um arquivo sobre a área, ela muda de aparência ("Solte aqui"); cada arquivo mostra seu próprio progresso.
- **Tema.** A alternância entre claro e escuro é imediata e lembra a escolha.
- **Somente leitura.** Nenhuma tela tem campo para digitar mensagem nem botão de enviar.

## Fora do escopo e critérios de pronto

O protótipo é uma maquete clicável, não um sistema: ele valida o layout, e a lógica real fica para o desenvolvimento em Laravel.

**Fora do escopo do protótipo**

- Servidor, banco de dados e leitura real de arquivos.
- Busca, importação e carregamento de mídia de verdade; tudo é simulado com dados fictícios.
- Login, senha e qualquer persistência entre sessões (exceto lembrar o tema, se for simples).
- Qualquer função de envio de mensagem.

**O protótipo está pronto quando**

- As 8 telas são navegáveis a partir da tela principal e voltam a ela.
- Os temas claro e escuro funcionam em todas as telas.
- Os estados listados no brief aparecem (vazio, carregando, sem resultado, erro).
- A busca por "proposta" mostra resultados destacados em 4 conversas e abre a mensagem certa.
- Nenhuma tela mostra campo de digitar mensagem, e o logotipo e a marca do WhatsApp não aparecem.
- O texto fica legível nos dois temas, em 1440 x 900 e em tela estreita.

## Prompts de refinamento

Depois da primeira versão, peça um ajuste por vez; assim fica fácil ver o que cada pedido mudou.

**1. Tema escuro**

```text
Revise o tema escuro: aumente o contraste do texto secundário e confirme que balões, avisos de sistema e destaques de busca ficam legíveis. Mantenha todo o resto igual.
```

**2. Tela estreita**

```text
Crie a versão para tela estreita (390 px de largura): uma coluna por vez, botão de voltar no cabeçalho da conversa e busca em tela cheia. Reaproveite os mesmos componentes e os dois temas.
```

**3. Conversa longa**

```text
Na conversa aberta, adicione o seletor de data para pular a um dia, o botão de ir ao fim com contador de mensagens e o indicador "Carregando mensagens anteriores" ao rolar para cima.
```

**4. Importar em detalhe**

```text
Detalhe a tela Importar com os estados: arrastando um arquivo, processando 3 arquivos com progresso individual, erro com lista de linhas não reconhecidas, e conversa que já existe com as opções "Somar à existente" e "Manter separada".
```

**5. Galeria de mídia (segunda fase)**

```text
Adicione à conversa uma tela "Mídia, links e documentos" com abas Fotos, Documentos e Links, grade de miniaturas e clique que abre a mensagem original na conversa.
```
