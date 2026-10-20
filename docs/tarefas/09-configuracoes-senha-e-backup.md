# Tarefa 09: Configurações, senha e backup

**Status:** pendente

## Objetivo

Fechar as funções de manutenção e proteção.

## Onde ler na especificação

- Seção "Telas, rotas e componentes" em `docs/ESPECIFICACAO.md`
- Seção "Segurança e execução local" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [ ] Tela de configurações: nomes, tema, pasta de dados (somente orientação), backup
- [ ] Backup consistente em `.zip` com progresso, e `zapvault:backup` e `zapvault:restore`
- [ ] Senha opcional com mínimo de 6 caracteres, tela de desbloqueio, limite de tentativas e "Bloquear agora"
- [ ] Menu ⋯ na conversa para renomear e excluir

## Como conferir

- [ ] Backup restaurado em pasta vazia mantém todas as conversas e anexos (`zapvault:verify`)
- [ ] Com a senha ativa, nada abre sem desbloquear

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

(Espaço para o Claude Code registrar o que foi decidido ou ficou pendente.)
