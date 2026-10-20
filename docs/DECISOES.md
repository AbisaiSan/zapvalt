# Decisões

Decisões pequenas tomadas durante a implementação, com o motivo.

## Tarefa 00: base do projeto

- **PHP pelo Homebrew, sem Herd.** A pedido do usuário, o PHP (8.5) e o Composer vieram do Homebrew. A especificação exige 8.3 ou superior.
- **Inertia 2, não 3.** O Inertia 3 já existe, mas a especificação pede o 2. O `inertiajs/inertia-laravel` 2.x é compatível com o Laravel 13; no frontend, `@inertiajs/vue3` 2.3.
- **TypeScript 6.** O TypeScript 7 (versão nativa) não funciona com o `vue-tsc` 3.3, que é o verificador de tipos dos componentes Vue.
- **Pasta de dados.** `ZAPVAULT_DATA_PATH` vazio usa `~/ZapVault-dados`. Caminho relativo é resolvido a partir da raiz do projeto, o que permite aos testes usarem `storage/framework/testing/zapvault-data`, sem tocar no acervo real. A pasta, as subpastas da especificação (`db`, `media`, `thumbs`, `originals`, `backups`, `uploads-tmp`) e o arquivo do banco são criados na inicialização da aplicação, com permissão 700.
- **Banco.** A conexão `sqlite` aponta para `{DATA}/db/zapvault.sqlite`, com `journal_mode=WAL`, `synchronous=NORMAL`, `busy_timeout=10000` e chaves estrangeiras. A conexão `queue` separada fica para a tarefa 01. O antigo `database/database.sqlite` criado pelo instalador do Laravel não é mais usado (está fora do Git).
- **Tema antes da página aparecer.** Como a CSP proíbe scripts inline, o tema salvo em `localStorage` (`zv-theme`) é aplicado por `public/js/theme.js`, carregado no `<head>`. A mesma regra está em `resources/js/lib/theme.ts`, usada pela aplicação.
- **Barra de progresso do Inertia desligada.** Ela injeta `<style>` inline, bloqueado pela CSP (`default-src 'self'`).
- **CSP em desenvolvimento.** Com `npm run dev` ativo, a CSP libera só o servidor local do Vite (scripts, estilos e websocket), porque o Vite injeta estilos inline. Em uso normal, com `npm run build`, vale a política da especificação sem exceções.
- **Página de erro em modo debug.** Com `APP_DEBUG=true`, respostas de erro 500 saem sem CSP, porque a página de erro detalhada do Laravel usa scripts inline. Os demais cabeçalhos continuam.
- **Arquivos estáticos.** O `php artisan serve` entrega arquivos de `public/` sem passar pelo Laravel, então os cabeçalhos de segurança valem para todas as respostas da aplicação (páginas, API, erros), mas não para `.css` e `.js` compilados.
- **`composer run zapvault`.** Usa o `concurrently` (já instalado com o projeto) para subir `php artisan serve --host=127.0.0.1 --port=8000` e `php artisan queue:work`. Antes, apaga um `public/hot` esquecido, para garantir os arquivos compilados. `composer run dev` é o `php artisan dev` do Laravel 13 (servidor em 127.0.0.1, fila e Vite).
- **Fontes do sistema.** Removida a fonte Instrument Sans (baixada da Bunny Fonts) que vinha no projeto novo. A pilha de fontes é a do protótipo.
- **`AGENTS.md` removido** na configuração inicial: trazia instruções genéricas de instalação de PHP via `curl | bash`, contrárias às regras do projeto.
