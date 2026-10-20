# Tarefa 02: Importador e linha de comando

**Status:** pendente

## Objetivo

Ler arquivos `.txt` e `.zip` exportados e gravar conversas, mensagens e anexos sem duplicar nada.

## Onde ler na especificação

- Seção "Importador de conversas" em `docs/ESPECIFICACAO.md`
- Seção "Mídia e arquivos" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [ ] Leitura de `.txt`, `.zip` e zip dentro de zip, com proteção contra caminhos perigosos e arquivos suspeitos
- [ ] Detecção dos três formatos de linha (Android 24h, Android com vírgula e AM/PM, iOS com colchetes) e mensagens de várias linhas
- [ ] Classificação de tipo: texto, imagem, vídeo, áudio, documento, figurinha, sistema, chamada, apagada, anexo ausente
- [ ] Chave de duplicidade (minuto, remetente, tipo, texto normalizado, mais sufixo `:n`) e inserção em lotes de 1000
- [ ] Fluxo em duas fases: rascunho, depois Concluir ou Cancelar
- [ ] Sugestão do nome do autor, lista de linhas não reconhecidas, extração de mídia e miniaturas
- [ ] Comandos `zapvault:import`, `zapvault:reindex` e `zapvault:verify`
- [ ] Testes com arquivos de exemplo sintéticos em `tests/Fixtures/exports/`

## Como conferir

- [ ] Todos os testes do parser passam
- [ ] Importar o mesmo arquivo duas vezes resulta em 0 mensagens novas
- [ ] `php artisan zapvault:verify` não aponta inconsistências

## Observação

Depois desta tarefa, importe um export seu de verdade só na sua máquina, fora do Git, para descobrir formatos não previstos. Linhas não reconhecidas viram novos casos de teste com texto fictício equivalente.

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

(Espaço para o Claude Code registrar o que foi decidido ou ficou pendente.)
