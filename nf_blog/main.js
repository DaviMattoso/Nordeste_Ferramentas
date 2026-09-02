/* ==========================================
   MENU MOBILE
========================================== */

// Seleciona a lista de navegação
const navItems = document.querySelector(".nav__items");

// Seleciona o botão de abrir menu (☰)
const openNavBtn = document.querySelector("#open__nav-btn");

// Seleciona o botão de fechar menu (X)
const closeNavBtn = document.querySelector("#close__nav-btn");

/* ==========================================
   ABRIR MENU MOBILE
========================================== */
const openNav = () => {
    // Exibe a lista de navegação
    navItems.style.display = "flex";
    // Esconde o botão de abrir
    openNavBtn.style.display = "none";
    // Exibe o botão de fechar
    closeNavBtn.style.display = "inline-block";
};

/* ==========================================
   FECHAR MENU MOBILE
========================================== */
const closeNav = () => {
    // Esconde a lista de navegação
    navItems.style.display = "none";
    // Esconde o botão de fechar
    closeNavBtn.style.display = "none";
    // Exibe novamente o botão de abrir
    openNavBtn.style.display = "inline-block";
};

/* ==========================================
   EVENTOS DO MENU MOBILE
========================================== */

// Executa a função openNav ao clicar no botão ☰
openNavBtn.addEventListener("click", openNav);

// Executa a função closeNav ao clicar no botão X
closeNavBtn.addEventListener("click", closeNav);
