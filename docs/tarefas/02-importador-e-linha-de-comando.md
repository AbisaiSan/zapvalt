# Tarefa 02: Importador e linha de comando

**Status:** concluída

## Objetivo

Ler arquivos `.txt` e `.zip` exportados e gravar conversas, mensagens e anexos sem duplicar nada.

## Onde ler na especificação

- Seção "Importador de conversas" em `docs/ESPECIFICACAO.md`
- Seção "Mídia e arquivos" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Leitura de `.txt`, `.zip` e zip dentro de zip, com proteção contra caminhos perigosos e arquivos suspeitos
- [x] Detecção dos três formatos de linha (Android 24h, Android com vírgula e AM/PM, iOS com colchetes) e mensagens de várias linhas
- [x] Classificação de tipo: texto, imagem, vídeo, áudio, documento, figurinha, sistema, chamada, apagada, anexo ausente
- [x] Chave de duplicidade (minuto, remetente, tipo, texto normalizado, mais sufixo `:n`) e inserção em lotes de 1000
- [x] Fluxo em duas fases: rascunho, depois Concluir ou Cancelar
- [x] Sugestão do nome do autor, lista de linhas não reconhecidas, extração de mídia e miniaturas
- [x] Comandos `zapvault:import`, `zapvault:reindex` e `zapvault:verify`
- [x] Testes com arquivos de exemplo sintéticos em `tests/Fixtures/exports/`

## Como conferir

- [x] Todos os testes do parser passam
- [x] Importar o mesmo arquivo duas vezes resulta em 0 mensagens novas
- [x] `php artisan zapvault:verify` não aponta inconsistências

## Observação

Depois desta tarefa, importe um export seu de verdade só na sua máquina, fora do Git, para descobrir formatos não previstos. Linhas não reconhecidas viram novos casos de teste com texto fictício equivalente.

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md` (seção "Tarefa 02").
- O job `AnalyzeImportFile` já existe para a fila; o envio em partes e os endpoints de importação entram na tarefa 08.
- Pendente com o usuário: importar um export real, só na máquina dele, para descobrir formatos não previstos.
