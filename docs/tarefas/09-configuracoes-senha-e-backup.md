# Tarefa 09: Configurações, senha e backup

**Status:** concluída

## Objetivo

Fechar as funções de manutenção e proteção.

## Onde ler na especificação

- Seção "Telas, rotas e componentes" em `docs/ESPECIFICACAO.md`
- Seção "Segurança e execução local" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Tela de configurações: nomes, tema, pasta de dados (somente orientação), backup
- [x] Backup consistente em `.zip` com progresso, e `zapvault:backup` e `zapvault:restore`
- [x] Senha opcional com mínimo de 6 caracteres, tela de desbloqueio, limite de tentativas e "Bloquear agora"
- [x] Menu ⋯ na conversa para renomear e excluir

## Como conferir

- [x] Backup restaurado em pasta vazia mantém todas as conversas e anexos (`zapvault:verify`)
- [x] Com a senha ativa, nada abre sem desbloquear

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md`, seção "Tarefa 09".
- Conferido no Chrome numa pasta de dados temporária: nomes, tema, backup pela tela (com progresso), senha (ativar, bloquear, tentativas erradas com espera, desbloquear, desativar), renomear e excluir conversa. O backup gerado pela tela foi restaurado com `zapvault:restore` em pasta vazia e o `zapvault:verify --hashes` não apontou nada.
- A restauração é só pelo terminal, como na especificação.
