// Aplica o tema salvo antes de a página aparecer, para não piscar o tema errado.
// A mesma regra está em resources/js/lib/theme.ts.
(function () {
    var theme = 'light';
    try {
        if (localStorage.getItem('zv-theme') === 'dark') theme = 'dark';
    } catch (e) {}
    document.documentElement.setAttribute('data-zv-theme', theme);
})();
