<?php
/**
 * Navegação pública compartilhada pelo blog e pelo painel.
 *
 * $blogBase ajusta os caminhos relativos quando a página está em admin/.
 * $hideAuthNav omite login/perfil em telas de autenticação.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

$blogBase = $blogBase ?? '';
$hideAuthNav = $hideAuthNav ?? false;
$dashboardHref = $dashboardHref ?? ($blogBase . 'admin/dashboard.php');
?>
        <nav>
            <div class="container nav__container">
                <a href="<?= authEscape($blogBase) ?>index.php" class="nav__logo">
                    <img
                        src="<?= authEscape($blogBase) ?>Images/logo.png"
                        alt="Logo NFB"
                        class="nav__logo-image"
                    />
                    <span>NF BLOG</span>
                </a>
                <ul class="nav__items">
                    <li><a href="<?= authEscape($blogBase) ?>blog.php">Posts</a></li>
                    <li><a href="<?= authEscape($blogBase) ?>about.php">Sobre</a></li>
                    <li><a href="<?= authEscape($blogBase) ?>services.php">Serviços</a></li>
                    <li><a href="<?= authEscape($blogBase) ?>contact.php">Contato</a></li>
                    <?php if (!$hideAuthNav): ?>
                    <?php if (!isLoggedIn()): ?>
                    <li><a href="<?= authEscape($blogBase) ?>signin.php">Signin</a></li>
                    <?php else: ?>
                    <li class="nav__profile">
                        <div class="avatar">
                            <img src="<?= authEscape($blogBase . authAvatar()) ?>" alt="<?= authEscape((string) ($_SESSION['username'] ?? '')) ?>" />
                        </div>
                        <ul>
                            <li><a href="<?= authEscape($dashboardHref) ?>">Dashboard</a></li>
                            <li>
                                <form action="<?= authEscape($blogBase) ?>logout.php" method="POST" class="logout__form">
                                    <?= csrfField() ?>
                                    <button type="submit" class="logout__button">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <button type="button" id="open__nav-btn" aria-label="Abrir menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button type="button" id="close__nav-btn" aria-label="Fechar menu">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>
        </nav>
