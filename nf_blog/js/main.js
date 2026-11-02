/**
 * Alterna a navegação do NF Blog em telas pequenas.
 *
 * O CSS mantém a lista recolhida no layout móvel; estes eventos exibem o menu
 * e trocam os botões de abrir/fechar. Ao voltar para o desktop, os estilos
 * inline são limpos para o CSS reassumir o controle.
 */
const navItems = document.querySelector(".nav__items");
const openNavBtn = document.querySelector("#open__nav-btn");
const closeNavBtn = document.querySelector("#close__nav-btn");

if (navItems && openNavBtn && closeNavBtn) {
    /** Exibe os itens de navegação e apresenta o controle de fechamento. */
    const openNav = () => {
        navItems.style.display = "flex";
        openNavBtn.style.display = "none";
        closeNavBtn.style.display = "inline-block";
    };

    /** Recolhe os itens de navegação e restaura o controle de abertura. */
    const closeNav = () => {
        navItems.style.display = "none";
        closeNavBtn.style.display = "none";
        openNavBtn.style.display = "inline-block";
    };

    /** Remove estilos inline quando o layout deixa de ser o menu móvel. */
    const resetNavForDesktop = () => {
        if (window.innerWidth > 1024) {
            navItems.style.display = "";
            openNavBtn.style.display = "";
            closeNavBtn.style.display = "";
        }
    };

    openNavBtn.addEventListener("click", openNav);
    closeNavBtn.addEventListener("click", closeNav);
    window.addEventListener("resize", resetNavForDesktop);
}
