# Tarefa 10: Acabamento e verificação final

**Status:** concluída

## Objetivo

Deixar o aplicativo pronto para uso de verdade.

## Onde ler na especificação

- Seção "Testes e critérios de aceite" em `docs/ESPECIFICACAO.md`
- Seção "Segurança e execução local" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Acessibilidade: foco visível, teclado, contraste, rótulos
- [x] Estados de carregamento, vazio e erro em todas as telas
- [x] Revisão dos textos em português do Brasil
- [x] README com uso diário, importação, backup e restauração
- [x] Teste de desempenho em escala grande com as metas da especificação
- [x] Checklist dos 10 critérios de aceite

## Como conferir

- [x] Todos os critérios de aceite marcados
- [x] `composer test`, `npm test` e `zapvault:verify` passam
- [x] Funciona com a internet desligada

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

Decisões em `docs/DECISOES.md`, seção "Tarefa 10".

### Critérios de aceite

1. [x] **Importar .txt e .zip com mídia, com nome, prévia e data corretos.** Testes do importador (`tests/Feature/Import`) e conferência pela tela na tarefa 08; as 12 conversas de demonstração passam pelo mesmo importador.
2. [x] **Reimportar não cria mensagem nova.** Testes de duplicidade (`ImportFlowTest`, `ImportCommandsTest`) e conferência na tarefa 08.
3. [x] **Buscar contato e frase, com ou sem acento, em qualquer caixa, e chegar à mensagem destacada.** Testes de busca (Pest e Vitest com os mesmos casos) e conferência no navegador nas tarefas 05 e 06.
4. [x] **Imagens, vídeos, áudios (1x, 1,5x, 2x), PDFs e documentos, com download.** Conferido na tarefa 07; mídia com `Range` coberta em `MediaApiTest`.
5. [x] **Nenhum caminho para enviar mensagem.** Não há campo de escrita nem endpoint de envio; a API só lê o acervo, renomeia ou exclui conversas e importa arquivos.
6. [x] **Tema claro e escuro, uma coluna em tela estreita, tudo em português.** Capturas nas duas larguras e nos dois temas; textos revisados (sem travessões, sem inglês, inclusive nas mensagens de erro do framework).
7. [x] **Sem internet, tudo funciona.** Navegação no Chrome com pedidos externos bloqueados: zero pedidos para fora de `127.0.0.1`.
8. [x] **Backup restaurado em pasta vazia mantém conversas e anexos.** `BackupRestoreTest` (contagens e hashes idênticos) e conferência manual na tarefa 09 com `zapvault:verify --hashes`.
9. [x] **Telas conferem com o protótipo**, com os ajustes da especificação e as decisões registradas (incluindo o leve ajuste de contraste do tema claro).
10. [x] **`composer test`, `npm test` e `zapvault:verify` passam.** 239 testes no backend, 76 no frontend; `zapvault:verify --hashes` no acervo de demonstração sem inconsistências.

### Desempenho (escala grande)

| Medida | Resultado | Meta |
|---|---|---|
| Lista de conversas | 1 ms | 300 ms |
| Busca por palavra comum | 368 ms | 500 ms |
| Busca por nome de conversa | 0 ms | 150 ms |
| Abrir conversa de 100 mil mensagens | 1 ms | 400 ms |

Medido com `php artisan test --group=performance` (1,5 milhão de mensagens), dentro do processo.
