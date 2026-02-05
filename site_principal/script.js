// =====================================================
// HEADER FIXO COM EFEITO DE ESCONDER / MOSTRAR NO SCROLL
// =====================================================

// Espera o HTML inteiro carregar antes de executar o JavaScript
document.addEventListener("DOMContentLoaded", function () {

    // Guarda a última posição do scroll
    let lastScrollTop = 0;

    // Seleciona o elemento header no HTML
    const header = document.querySelector('.header');

    // Evento disparado sempre que o usuário rola a página
    window.addEventListener('scroll', function () {

        // Posição atual do scroll vertical da página
        const currentScroll = window.pageYOffset;

        /*
            Se o usuário estiver rolando PARA BAIXO
            e já tiver descido mais de 150px da página:
            → o header será escondido
        */
        if (currentScroll > lastScrollTop && currentScroll > 150) {

            // Move o header para cima, escondendo ele da tela
            header.style.top = "-200px";

        } else {
            /*
                Se o usuário estiver rolando PARA CIMA
                ou estiver próximo do topo da página:
                → o header reaparece
            */

            // Traz o header de volta para o topo
            header.style.top = "0";
        }

        /*
            Atualiza a última posição do scroll
            Evita valores negativos quando estiver no topo
        */
        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });

});