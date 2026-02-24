const postsContainer = document.querySelector(".posts");

function renderPosts() {
    const publishedPosts = posts.filter(post => post.status === "published");

    postsContainer.innerHTML = publishedPosts.map(post => `
        <article class="post-card">
            <img src="${post.coverImage}" alt="${post.title}">
            <h2>${post.title}</h2>
            <p>${post.metaDescription}</p>
            <a href="post.html?slug=${post.slug}">Ler mais</a>
        </article>
    `).join("");
}

renderPosts();