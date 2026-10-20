# ZapVault

Arquivo local e somente leitura de conversas exportadas do WhatsApp Business. Roda só no computador do usuário. Interface em português do Brasil, parecida com o WhatsApp Web.

## Fontes de verdade
- docs/ESPECIFICACAO.md: especificação técnica completa (stack, banco, importador, busca, endpoints, roteiro por etapas). Leia antes de qualquer trabalho e siga.
- docs/tarefas/: uma tarefa por etapa do roteiro (00 a 10), com README, status e lista de entregas. É a fila de trabalho.
- docs/prototipo/: protótipo visual aprovado. Reproduza layout, textos, cores e comportamentos. Só é referência visual; não copie o formato de dados em memória nem o runtime dele.
- docs/PRODUTO.md: visão de produto, regras de negócio e requisitos (se existir).
- Se algo da especificação não funcionar ou houver conflito com o protótipo, explique o problema e proponha uma mudança. Não decida sozinho.

## Repositório
- Remoto: https://github.com/AbisaiSan/zapvalt.git, branch main.
- Nunca use push --force, reset --hard em commits já enviados nem reescreva o histórico.
- Nunca faça commit de .env, pasta de dados, arquivos .zip de conversas ou qualquer texto de conversa real.

## Stack
Laravel (versão estável atual), PHP 8.3+, SQLite com FTS5, Inertia 2, Vue 3 (Composition API, TypeScript), Tailwind, Pest, Vitest. Sem starter kit de autenticação.

## Regras que nunca mudam
- O aplicativo não envia mensagens e não escreve nada no WhatsApp. É somente leitura sobre o que foi importado.
- Tudo é local: escuta apenas 127.0.0.1, sem CDN, sem fontes externas, sem analytics, sem chamadas a serviços de terceiros.
- Nunca carregue o arquivo inteiro de uma conversa no navegador. Mensagens vêm em janelas paginadas por cursor, busca e contagens são feitas no servidor.
- Texto de mensagem é sempre exibido como texto, nunca como HTML.
- Caminhos de arquivos vêm do banco e precisam ficar dentro da pasta de dados (ZAPVAULT_DATA_PATH). Valide com realpath.
- Os dados do usuário ficam fora do projeto e fora do Git. Nunca coloque conversa real em código, testes, fábricas, exemplos ou logs. Use somente dados fictícios.
- Logs não podem conter o texto das mensagens.
- Importar o mesmo arquivo duas vezes nunca duplica mensagens.
- Cancelar uma importação não deixa nada para trás.
- Nunca rode migrate:fresh, apague o banco ou a pasta de dados fora do ambiente de testes.
- Não instale pacotes sem necessidade clara. Prefira o que o Laravel já oferece.

## Idioma e estilo
- Todo texto da interface, mensagens de erro, README e mensagens de commit em português do Brasil.
- Código, nomes de classes, variáveis e colunas em inglês, como na especificação.
- Use vírgulas em vez de travessões (em dash) nos textos escritos.
- Acessibilidade: foco visível, navegação por teclado, rótulos nos botões de ícone, contraste suficiente nos dois temas.

## Como trabalhar
- Siga as tarefas de docs/tarefas, uma por vez, na ordem, conforme docs/tarefas/README.md.
- Ao começar uma tarefa, marque o Status como em andamento. Ao terminar, marque como concluída, no arquivo da tarefa e na tabela do README.
- Ao terminar uma tarefa: rode os testes, faça commits pequenos com mensagem clara, envie com git push, resuma em poucas linhas o que ficou pronto e como o usuário confere, e PARE esperando a confirmação antes de começar a próxima.
- Escreva os testes junto com o código. Tarefa com teste falhando não está terminada.
- Se faltar uma informação que muda o resultado, pergunte. Se for detalhe pequeno, escolha o mais simples e registre a decisão em docs/DECISOES.md.
- Mantenha o README com o passo a passo de instalar, rodar, importar, fazer backup e restaurar.

## Comandos
- composer run zapvault: sobe servidor local e worker da fila (uso diário), criado na tarefa 00
- composer run dev: desenvolvimento com recarga automática
- composer test: testes do backend (Pest)
- npm test: testes do frontend (Vitest), configurado na tarefa 00
- npm run build: compila a interface
- php artisan zapvault:import {arquivo}: importa um .txt ou .zip
- php artisan zapvault:demo [--scale=large]: gera dados fictícios
- php artisan zapvault:reindex: reconstrói a busca
- php artisan zapvault:verify: confere a consistência dos dados
- php artisan zapvault:migrate: atualiza o banco guardando antes uma cópia (VACUUM INTO)
- php artisan zapvault:backup e zapvault:restore {arquivo}
