<?php
/**
 * Apresenta os serviços e tipos de conteúdo oferecidos pelo NF Blog.
 * A sessão personaliza a navegação e as categorias do rodapé vêm do banco.
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

        <!-- Apresentação dos serviços, conteúdos, indicadores e chamada para o blog. -->
        <section class="services">
            <div class="container services__container">
                <div class="services__title">
                    <h1>Nossos Serviços</h1>
                    <p>
                        O NF Ferramentas vai além de apresentar produtos. Nosso
                        objetivo é ensinar, orientar e ajudar profissionais e
                        iniciantes a utilizarem as ferramentas corretas com
                        segurança e eficiência.
                    </p>
                </div>

                <div class="services__top">
                    <article>
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        <h3>Ferramentas</h3>
                        <p>
                            Reviews completos, comparativos e recomendações das
                            principais ferramentas do mercado.
                        </p>
                    </article>

                    <article>
                        <i class="fa-solid fa-helmet-safety"></i>
                        <h3>Segurança</h3>
                        <p>
                            Conteúdos voltados para boas práticas, EPIs e
                            prevenção de acidentes durante qualquer tipo de
                            trabalho.
                        </p>
                    </article>

                    <article>
                        <i class="fa-solid fa-house"></i>
                        <h3>Projetos</h3>
                        <p>
                            Tutoriais e projetos passo a passo para colocar a
                            mão na massa com confiança.
                        </p>
                    </article>
                </div>

                <div class="services__middle">
                    <div class="services__image">
                        <img
                            src="./Images/Servicos.png"
                            alt="Serviços do NF Blog"
                        />
                    </div>

                    <div class="services__content">
                        <h2>Conhecimento que faz diferença</h2>
                        <p>
                            Nosso conteúdo é desenvolvido para quem deseja
                            aprender desde o básico até técnicas mais avançadas
                            no universo das ferramentas, construção, marcenaria
                            e manutenção.
                        </p>
                        <div class="services__list">
                            <article>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Reviews imparciais</span>
                            </article>

                            <article>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Guias completos</span>
                            </article>

                            <article>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Dicas de manutenção</span>
                            </article>

                            <article>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Projetos DIY</span>
                            </article>

                            <article>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Segurança no trabalho</span>
                            </article>

                            <article>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Comparativos entre marcas</span>
                            </article>
                        </div>
                    </div>
                </div>

                <div class="services__numbers">
                    <article>
                        <h2>500+</h2>
                        <small>Artigos publicados</small>
                    </article>

                    <article>
                        <h2>120+</h2>
                        <small>Ferramentas avaliadas</small>
                    </article>

                    <article>
                        <h2>50+</h2>
                        <small>Guias completos</small>
                    </article>

                    <article>
                        <h2>1000+</h2>
                        <small>Leitores mensais</small>
                    </article>
                </div>

                <div class="services__cta">
                    <h2>Pronto para aprender mais?</h2>
                    <p>
                        Explore nossos artigos e descubra conteúdos preparados
                        para profissionais, estudantes e apaixonados por
                        ferramentas.
                    </p>

                    <a href="blog.php" class="btn"> Explorar Posts </a>
                </div>
            </div>
        </section>

        <!-- Rodapé público alimentado pelas categorias atuais do banco. -->
        <?php require __DIR__ . '/partials/public-footer.php'; ?>
    </body>
</html>
