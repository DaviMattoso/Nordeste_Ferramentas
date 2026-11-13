<?php
/**
 * Página institucional do NF Blog.
 * A sessão personaliza a navegação e o banco fornece as categorias do rodapé;
 * nenhum conteúdo desta página exige autenticação.
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

        <!-- História, valores e mídia institucional do NF Blog. -->
        <section class="about">

            <div class="container about__container">

                <div class="about__content">
                    <h1>Sobre o NF Blog</h1>

                    <p>
                        O <strong>Nordeste Ferramentas Blog</strong> nasceu da
                        vontade de compartilhar conhecimento com quem vive o dia
                        a dia da construção, da manutenção e do trabalho manual.
                        Assim como o povo nordestino é conhecido pela
                        criatividade, dedicação e pela capacidade de transformar
                        desafios em oportunidades, nosso objetivo é mostrar que,
                        com as ferramentas certas e o conhecimento adequado,
                        qualquer projeto pode sair do papel.
                    </p>
                    <p>
                        Muito mais do que apresentar equipamentos, nossa missão
                        é ensinar. Queremos que este espaço seja uma fonte de
                        aprendizado para profissionais experientes, estudantes e
                        também para quem está dando os primeiros passos nesse
                        universo. Aqui você encontrará conteúdos sobre
                        ferramentas manuais e elétricas, construção civil,
                        marcenaria, segurança no trabalho, dicas práticas,
                        curiosidades e tutoriais desenvolvidos para facilitar o
                        aprendizado de forma simples e acessível.
                    </p>
                    <p>
                        Acreditamos que conhecimento é uma das ferramentas mais
                        valiosas que alguém pode carregar na caixa de
                        ferramentas. Afinal, uma boa ferramenta nas mãos de quem
                        sabe utilizá-la vale muito mais do que a mais cara das
                        máquinas usada sem preparo. E falando em ferramentas...
                        lembre-se: martelo foi feito para bater em prego, não no
                        dedo. Embora quase todo mundo descubra isso pelo menos
                        uma vez na vida.
                    </p>

                    <div class="about__cards">
                        <article class="about__card">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            <h4>Conteúdo Especializado</h4>
                            <small>
                                Publicações voltadas para ferramentas,
                                construção e manutenção.
                            </small>
                        </article>

                        <article class="about__card">
                            <i class="fa-solid fa-book-open"></i>
                            <h4>Aprendizado</h4>
                            <small>
                                Tutoriais simples para quem está começando e
                                materiais completos para profissionais.
                            </small>
                        </article>

                        <article class="about__card">
                            <i class="fa-solid fa-shield-halved"></i>
                            <h4>Segurança</h4>
                            <small>
                                Incentivamos sempre o uso correto de EPIs e boas
                                práticas de trabalho.
                            </small>
                        </article>
                    </div>
                </div>
            </div>

            <div class="about__video">
                <video autoplay muted loop playsinline>
                    <source src="./Images/AboutVDCap.mp4" type="video/mp4" />
                </video>
            </div>
        </section>

        <!-- Rodapé público alimentado pelas categorias atuais do banco. -->
        <?php require __DIR__ . '/partials/public-footer.php'; ?>
    </body>
</html>
