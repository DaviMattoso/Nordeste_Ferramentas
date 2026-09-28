/**
 * Alterna a navegação do NF Blog em telas pequenas.
 *
 * O CSS mantém a lista recolhida no layout móvel; estes eventos exibem o menu
 * e trocam os botões de abrir/fechar sem interferir na navegação desktop.
 */
const navItems = document.querySelector(".nav__items");
const openNavBtn = document.querySelector("#open__nav-btn");
const closeNavBtn = document.querySelector("#close__nav-btn");

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

openNavBtn.addEventListener("click", openNav);
closeNavBtn.addEventListener("click", closeNav);
