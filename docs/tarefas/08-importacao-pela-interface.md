# Tarefa 08: Importação pela interface

**Status:** concluída

## Objetivo

Importar arquivos pela tela, com progresso real e confirmação.

## Onde ler na especificação

- Seção "Telas, rotas e componentes" em `docs/ESPECIFICACAO.md`
- Seção "Importador de conversas" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Arrastar e soltar, envio em partes de 8 MB, fila por arquivo
- [x] Progresso real por arquivo e estado de falha
- [x] Estatísticas: novas, repetidas, anexos encontrados, anexos faltantes
- [x] Conversa que já existe: "Somar à existente" ou "Manter separada"
- [x] Erro com lista de linhas não reconhecidas
- [x] Pergunta "Este é o seu nome nas mensagens?"
- [x] Concluir publica, Cancelar desfaz tudo

## Como conferir

- [x] Importar arquivos de exemplo pela tela dá o mesmo resultado da linha de comando
- [x] Cancelar não deixa nada para trás

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md` (seção "Tarefa 08"). Conferido de ponta a ponta no Chrome: importar e cancelar pela tela.
