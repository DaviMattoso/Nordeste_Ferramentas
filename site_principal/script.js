/**
 * Controla as interações da página institucional.
 *
 * Integra os posts recentes do NF Blog, o formulário de contato, o cabeçalho
 * durante a rolagem e os diálogos usados por recursos ainda indisponíveis.
 */
document.addEventListener("DOMContentLoaded", function () {
    let lastScrollTop = 0;
    const header = document.querySelector(".header");

    /* ==================================================
       INTEGRAÇÃO COM O NF BLOG
       O endpoint vem de `data-endpoint` no HTML e retorna o JSON usado para
       substituir o estado de carregamento pelos três cards mais recentes.
       ================================================== */
    const latestPostsContainer = document.querySelector("#latest-posts");

    /**
     * Cria um link de post aberto em nova aba sem conceder acesso à janela de origem.
     */
    function createPostLink(url, label) {
        const link = document.createElement("a");
        link.href = url;
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.setAttribute("aria-label", label);
        return link;
    }

    /**
     * Converte um objeto retornado pela API em um card completo do blog.
     * Todo texto recebido é atribuído por `textContent`, sem interpretar HTML externo.
     */
    function createBlogCard(post) {
        const card = document.createElement("article");
        card.className = "blog_card";

        const imageBox = document.createElement("div");
        imageBox.className = "blog_img";

        const imageLink = createPostLink(
            post.post_url,
            `Ler o post: ${post.title}`,
        );
        const image = document.createElement("img");
        image.src = post.thumbnail_url;
        image.alt = post.title;
        imageLink.appendChild(image);
        imageBox.appendChild(imageLink);

        const tag = document.createElement("div");
        tag.className = "blog_tag";

        const category = document.createElement("span");
        category.className = "blog_category";
        category.textContent = post.category;

        const title = document.createElement("h2");
        const titleLink = createPostLink(post.post_url, post.title);
        titleLink.textContent = post.title;
        title.appendChild(titleLink);

        const excerpt = document.createElement("p");
        excerpt.textContent = post.excerpt;

        const icons = document.createElement("div");
        icons.className = "blog_icon";

        const date = document.createElement("span");
        date.className = "blog_date";

        const calendar = document.createElement("i");
        calendar.className = "fa-solid fa-calendar";
        calendar.setAttribute("aria-hidden", "true");

        const time = document.createElement("time");
        time.dateTime = post.created_at;
        time.textContent = post.display_date.split(" - ")[0];
        date.append(calendar, time);

        const readMore = createPostLink(
            post.post_url,
            `Ler o post: ${post.title}`,
        );
        readMore.className = "blog_read-more";
        readMore.append("Ler post");

        const arrow = document.createElement("i");
        arrow.className = "fa-solid fa-arrow-right";
        arrow.setAttribute("aria-hidden", "true");
        readMore.appendChild(arrow);

        icons.append(date, readMore);
        tag.append(category, title, excerpt, icons);
        card.append(imageBox, tag);

        return card;
    }

    /**
     * Busca os posts recentes, valida os campos consumidos pela interface e atualiza a vitrine.
     * Em falhas HTTP, JSON inválido ou indisponibilidade da API, preserva uma mensagem legível.
     */
    async function loadLatestPosts() {
        if (!latestPostsContainer) {
            return;
        }

        const status = latestPostsContainer.querySelector(".blog-status");

        try {
            const response = await fetch(latestPostsContainer.dataset.endpoint, {
                headers: { Accept: "application/json" },
                cache: "no-store",
            });

            if (!response.ok) {
                throw new Error(`Falha ao carregar posts: ${response.status}`);
            }

            const data = await response.json();
            const posts = Array.isArray(data.posts) ? data.posts.slice(0, 3) : [];
            const validPosts = posts.filter(function (post) {
                return (
                    post &&
                    typeof post.title === "string" &&
                    typeof post.excerpt === "string" &&
                    typeof post.thumbnail_url === "string" &&
                    typeof post.category === "string" &&
                    typeof post.created_at === "string" &&
                    typeof post.display_date === "string" &&
                    typeof post.post_url === "string"
                );
            });

            if (validPosts.length === 0) {
                status.textContent = "Ainda não existem posts publicados.";
                return;
            }

            latestPostsContainer.replaceChildren(
                ...validPosts.map(createBlogCard),
            );
        } catch (error) {
            status.textContent =
                "Não foi possível carregar os posts neste momento.";
            status.dataset.state = "error";
        }
    }

    loadLatestPosts();

    /* ==================================================
       COMPORTAMENTO DO CABEÇALHO
       Após 150 px, oculta o cabeçalho ao descer e o restaura ao subir.
       ================================================== */
    window.addEventListener("scroll", function () {
        const currentScroll = window.pageYOffset;

        if (currentScroll > lastScrollTop && currentScroll > 150) {
            header.style.transform = "translateY(-100%)";
        } else {
            header.style.transform = "translateY(0)";
        }

        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });

    /* ==================================================
       ENVIO DO FORMULÁRIO DE CONTATO
       Sem endpoint configurado, prepara a mensagem no WhatsApp. Quando houver
       uma API definida no HTML, envia os mesmos campos por `fetch`.
       ================================================== */
    const contactForm = document.querySelector("#contato-form");
    const formStatus = document.querySelector("#form-status");

    contactForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (!contactForm.reportValidity()) {
            return;
        }

        const formData = new FormData(contactForm);
        const endpoint = contactForm.dataset.endpoint.trim();
        const submitButton = contactForm.querySelector('button[type="submit"]');

        formStatus.textContent = "";
        delete formStatus.dataset.state;

        if (!endpoint) {
            const subjectSelect = contactForm.querySelector("#assunto");
            const subject = subjectSelect.options[subjectSelect.selectedIndex].text;
            const message = [
                `Olá! Meu nome é ${formData.get("nome")}.`,
                `E-mail: ${formData.get("email")}`,
                `Telefone: ${formData.get("telefone") || "Não informado"}`,
                `Assunto: ${subject}`,
                "",
                "Mensagem:",
                formData.get("mensagem"),
            ].join("\n");
            const whatsappUrl = `https://wa.me/${contactForm.dataset.whatsapp}?text=${encodeURIComponent(message)}`;
            const whatsappWindow = window.open(
                whatsappUrl,
                "_blank",
            );

            if (whatsappWindow) {
                whatsappWindow.opener = null;
            } else {
                window.location.href = whatsappUrl;
            }

            formStatus.textContent = "Mensagem preparada no WhatsApp.";
            formStatus.dataset.state = "success";
            return;
        }

        submitButton.disabled = true;
        formStatus.textContent = "Enviando mensagem...";

        try {
            const response = await fetch(endpoint, {
                method: contactForm.method,
                headers: { Accept: "application/json" },
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`Falha no envio: ${response.status}`);
            }

            contactForm.reset();
            formStatus.textContent = "Mensagem enviada com sucesso.";
            formStatus.dataset.state = "success";
        } catch (error) {
            console.error("Não foi possível enviar o formulário.", error);
            formStatus.textContent =
                "Não foi possível enviar. Tente novamente em instantes.";
            formStatus.dataset.state = "error";
        } finally {
            submitButton.disabled = false;
        }
    });

    /* ==================================================
       DIÁLOGOS DE DISPONIBILIDADE
       Associa cada gatilho ao `dialog` indicado em `data-dialog-target` e
       mantém um fallback para navegadores sem `showModal`/`close`.
       ================================================== */
    const dialogTriggers = document.querySelectorAll("[data-dialog-target]");
    const dialogs = document.querySelectorAll(".availability-dialog");

    dialogTriggers.forEach(function (trigger) {
        trigger.addEventListener("click", function (event) {
            const dialog = document.querySelector(
                `#${trigger.dataset.dialogTarget}`,
            );

            if (!dialog) {
                return;
            }

            event.preventDefault();

            if (typeof dialog.showModal === "function") {
                dialog.showModal();
            } else {
                dialog.setAttribute("open", "");
            }
        });
    });

    dialogs.forEach(function (dialog) {
        const closeButton = dialog.querySelector("[data-dialog-close]");

        closeButton.addEventListener("click", function () {
            if (typeof dialog.close === "function") {
                dialog.close();
            } else {
                dialog.removeAttribute("open");
            }
        });

        dialog.addEventListener("click", function (event) {
            if (event.target === dialog) {
                if (typeof dialog.close === "function") {
                    dialog.close();
                } else {
                    dialog.removeAttribute("open");
                }
            }
        });
    });
});
