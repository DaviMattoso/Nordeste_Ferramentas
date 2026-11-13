<?php
/**
 * Página pública de contato do NF Blog.
 * Reúne os canais da empresa, o formulário que abre o WhatsApp e o mapa;
 * a sessão personaliza a navegação e o banco alimenta as categorias do rodapé.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/category-utils.php';

$publicCategories = publicCategories($connection);
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>NF Blog</title>

        <link rel="icon" href="./Images/favicon.ico" />

        <link rel="stylesheet" href="./css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>" />

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
    </head>

    <body>

        <!-- Navegação pública com acesso condicional ao perfil autenticado. -->
        <?php require __DIR__ . '/partials/public-nav.php'; ?>

        <!-- Canais de atendimento e formulário visual de contato. -->
        <section class="contact">
            <div class="container contact__container">
                <div class="contact__left">
                    <h2>Entre em contato</h2>
                    <p>
                        Ficou com alguma dúvida, tem uma sugestão ou deseja
                        entrar em contato com nossa equipe? Preencha o
                        formulário ao lado ou utilize um dos nossos canais de
                        atendimento.
                    </p>
                    <div class="contact__info">
                        <article>
                            <i class="fa-solid fa-location-dot"></i>
                            <span
                                >Av. Barão de Souza Leão, 1496 / Recife-PE</span
                            >
                        </article>
                        <article>
                            <i class="fa-solid fa-phone"></i>
                            <span>(81) 99560-7222</span>
                        </article>
                        <article>
                            <i class="fa-solid fa-envelope"></i>
                            <span>contato@nordesteferramentas.com</span>
                        </article>
                        <article>
                            <i class="fa-solid fa-clock"></i>
                            <span
                                >Segunda a Sexta: 8h às 18h / Sábado: 8h às
                                12h</span
                            >
                        </article>
                    </div>
                </div>

                <div class="contact__right">
                    <!--
                        Não existe endpoint de contato no servidor. O formulário
                        envia para esta própria página e o contact.js abre o WhatsApp.
                    -->
                    <form
                        id="blog-contact-form"
                        class="contact__form"
                        action="contact.php"
                        method="post"
                        data-whatsapp="5581995607222"
                    >
                        <input type="text" name="nome" placeholder="Seu nome" autocomplete="name" required />
                        <input type="email" name="email" placeholder="Seu e-mail" autocomplete="email" required />
                        <input type="text" name="assunto" placeholder="Assunto" />
                        <textarea
                            name="mensagem"
                            rows="8"
                            placeholder="Digite sua mensagem..."
                            required
                        ></textarea>
                        <button class="btn" type="submit">
                            Enviar mensagem
                        </button>
                        <p id="blog-contact-status" role="status"></p>
                    </form>
                </div>
            </div>
        </section>

        <!-- Mapa externo incorporado com carregamento adiado. -->
        <section class="contact__map">
            <div class="container">
                <h2>Onde estamos ?</h2>

                <div class="map">
            <iframe
                title="Localização da Nordeste Ferramentas no Google Maps"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3949.691938339674!2d-34.91611942410925!3d-8.132811881422867!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x7ab1e313787f57f%3A0xb59209daaa482f3e!2sR.%20Bar%C3%A3o%20de%20Souza%20Le%C3%A3o%2C%201496%20-%20Boa%20Viagem%2C%20Recife%20-%20PE%2C%2051030-300%2C%20Brasil!5e0!3m2!1spt-BR!2spe!4v1784494716898!5m2!1spt-BR!2spe"
                        width="600"
                        height="450"
                        style="border: 0"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                    >
                    </iframe>
                </div>
            </div>
        </section>

        <!-- Rodapé público alimentado pelas categorias atuais do banco. -->
        <?php require __DIR__ . '/partials/public-footer.php'; ?>
        <script src="./js/contact.js"></script>
    </body>
</html>
