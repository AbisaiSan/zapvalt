# Tarefa 03: Dados de demonstração

**Status:** concluída

## Objetivo

Poder preencher o aplicativo com conversas fictícias para desenvolver e testar sem usar dados reais.

## Onde ler na especificação

- Seção "Testes e critérios de aceite" em `docs/ESPECIFICACAO.md`
- Seção "Protótipo e decisões de stack" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Comando `zapvault:demo` com as 12 conversas fictícias do protótipo, incluindo anexos de exemplo
- [x] Opção `--scale=large` gerando cerca de 300 conversas e 1,5 milhão de mensagens
- [x] Comando seguro: recusa rodar sobre dados reais sem confirmação explícita

## Como conferir

- [x] O comando popula o banco e `zapvault:verify` fica limpo
- [x] Os números das conversas de demonstração batem com o protótipo

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md` (seção "Tarefa 03").
- Conferido: 12 conversas e 104 mensagens, na mesma ordem e com as mesmas contagens do protótipo; `zapvault:verify --hashes` limpo.
