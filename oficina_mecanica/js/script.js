document.addEventListener('DOMContentLoaded', function () {
    const botoesExcluir = document.querySelectorAll('[data-confirmar-exclusao]');

    botoesExcluir.forEach(function (botao) {
        botao.addEventListener('click', function (evento) {
            const mensagem = botao.getAttribute('data-confirmar-exclusao');
            if (!confirm(mensagem)) {
                evento.preventDefault();
            }
        });
    });
});
