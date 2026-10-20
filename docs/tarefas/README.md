# Tarefas do ZapVault

Cada arquivo desta pasta é uma etapa do roteiro de implementação de `docs/ESPECIFICACAO.md`. Elas são feitas **uma por vez, na ordem**.

## Como o Claude Code deve usar esta pasta

1. Abra esta tabela e escolha a primeira tarefa que não esteja concluída.
2. Leia o arquivo da tarefa e as seções da especificação indicadas nele.
3. Mude o `Status` da tarefa para `em andamento` (no arquivo e na tabela abaixo).
4. Implemente com testes. Marque cada item da lista de entregas conforme ficar pronto.
5. Rode `composer test` e `npm test`.
6. Faça um commit pequeno em português, por exemplo `tarefa 02: importador de .txt e .zip`.
7. Mude o `Status` para `concluída` (no arquivo e na tabela), faça um commit disso e envie com `git push`. Resuma em poucas linhas o que ficou pronto e como conferir, e **pare** até o usuário confirmar. Se o usuário pediu um lote de tarefas (veja abaixo), siga para a próxima tarefa do lote sem parar, e pare só ao fim do lote.

Decisões pequenas tomadas no caminho vão para `docs/DECISOES.md`. Se algo da especificação não funcionar na prática, explique o problema e proponha uma mudança antes de seguir.

## Lotes de execução

São 11 tarefas (00 a 10), agrupadas em 5 lotes. Cada tarefa termina com commit e push. O usuário confere o resultado ao fim de cada lote e só então libera o próximo.

| Lote | Tarefas | O que o usuário confere ao fim |
|---|---|---|
| 1 | 00, 01, 02 | Projeto roda, testes passam e um export de conversa é importado pela linha de comando (`zapvault:import`), sem duplicar ao reimportar |
| 2 | 03, 04 | `zapvault:demo` popula o banco e os endpoints respondem no formato da especificação |
| 3 | 05, 06 | Primeira vez que o aplicativo aparece no navegador: lista, busca e conversa com dados de demonstração, comparando com o protótipo |
| 4 | 07, 08 | Anexos abrem e a importação funciona pela tela, com progresso |
| 5 | 09, 10 | Configurações, senha, backup, acabamento e checklist final de aceite |

Quando o usuário pedir um lote, execute só as tarefas dele, na ordem, sem pedir confirmação entre elas, e pare ao terminar a última. Se uma tarefa falhar ou exigir uma decisão, pare antes de seguir, mesmo no meio do lote.

## Estados possíveis

`pendente`, `em andamento`, `concluída`, `bloqueada` (explique o motivo no arquivo da tarefa).

## Lista

| # | Tarefa | Lote | Status |
|---|---|---|---|
| 00 | [Base do projeto](00-base-do-projeto.md) | 1 | pendente |
| 01 | [Banco e busca](01-banco-e-busca.md) | 1 | pendente |
| 02 | [Importador e linha de comando](02-importador-e-linha-de-comando.md) | 1 | pendente |
| 03 | [Dados de demonstração](03-dados-de-demonstracao.md) | 2 | pendente |
| 04 | [API](04-api.md) | 2 | pendente |
| 05 | [Estrutura da interface e lista de conversas](05-estrutura-da-interface-e-lista.md) | 3 | pendente |
| 06 | [Tela da conversa](06-tela-da-conversa.md) | 3 | pendente |
| 07 | [Mídia e documentos](07-midia-e-documentos.md) | 4 | pendente |
| 08 | [Importação pela interface](08-importacao-pela-interface.md) | 4 | pendente |
| 09 | [Configurações, senha e backup](09-configuracoes-senha-e-backup.md) | 5 | pendente |
| 10 | [Acabamento e verificação final](10-acabamento-e-verificacao-final.md) | 5 | pendente |

## Fora do escopo, sempre

Envio de mensagens, contas de usuário, sincronização em nuvem e qualquer serviço externo. As ideias da fase 2 só entram depois da tarefa 10 e com aprovação do usuário.
