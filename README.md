# Nordeste Ferramentas

O primeiro projeto de um desenvolvedor júnior, feito para um amigo.

A Nordeste Ferramentas é uma empresa de aluguel e compra de ferramentas de construção. O pedido foi um site institucional e um blog. O sistema de aluguel também faz parte da ideia, mas fica para um plano futuro. Tudo foi feito para a web e funciona de forma responsiva.

## Telas

Prints de tela cheia do site institucional e do blog. Coloque as imagens nestes caminhos antes de publicar:

![Site institucional da Nordeste Ferramentas](docs/site-principal.png)

![Blog da Nordeste Ferramentas](docs/blog.png)

## Stack Tecnológica
Site Institucional

<p align="left">
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" />
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" />
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" />
  <img src="https://img.shields.io/badge/Font_Awesome-528DD7?style=for-the-badge&logo=fontawesome&logoColor=white" />
  <img src="https://img.shields.io/badge/Inter-Font-000000?style=for-the-badge" />
</p>

Site desenvolvido com HTML, CSS e JavaScript puro, sem utilização de frameworks.

Blog

<p align="left">
  <img src="https://img.shields.io/badge/PHP_8-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white" />
  <img src="https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white" />
</p>

Blog desenvolvido em PHP 8, com banco de dados MariaDB/MySQL e consultas utilizando mysqli. Durante o desenvolvimento, utiliza Apache através do XAMPP.

Integração entre os sistemas

<p align="left">
  <img src="https://img.shields.io/badge/API-JSON-000000?style=for-the-badge&logo=json&logoColor=white" />
  <img src="https://img.shields.io/badge/HTTP-API-00599C?style=for-the-badge&logo=fastapi&logoColor=white" />
</p>

O site institucional consome a API pública:

nf_blog/api/latest-posts.php

A API retorna os posts mais recentes em formato JSON, permitindo que o site institucional exiba automaticamente os conteúdos publicados no blog.
Configuração e Ambiente

<p align="left">
  <img src="https://img.shields.io/badge/.env-Environment-ecd53f?style=for-the-badge&logo=dotenv&logoColor=black" />
  <img src="https://img.shields.io/badge/Development-Local-2EA44F?style=for-the-badge&logo=visualstudiocode&logoColor=white" />
</p>

    Variáveis de ambiente: URLs e configurações do banco de dados são definidas pelas variáveis NF_*.

    Fallback local: valores padrão são utilizados durante o desenvolvimento.

    Modelo de configuração: disponível no arquivo .env.example.

## Arquitetura

O repositório tem duas partes públicas, e as duas conversam pelo mesmo banco do blog.

A raiz (`index.html`) só redireciona para o site institucional, em `site_principal/`. Essa parte é uma página estática: apresentação da empresa, contato e uma vitrine com os três posts mais recentes. O `script.js` chama a API, confere a resposta e monta os cards com texto puro, sem interpretar HTML vindo de fora. O botão do Portal de Locação abre um aviso: o sistema de aluguel ainda não tem endereço próprio.

O blog fica em `nf_blog/` e é renderizado no servidor.

- As páginas públicas cobrem a home, a listagem, o post, a categoria, a busca, sobre, serviços, contato, cadastro e login.
- A área administrativa faz o cadastro, a edição e a exclusão de posts, categorias e usuários.
- `config/` concentra o que as páginas compartilham: endereço e credenciais, conexão com o MySQL, sessão, papéis de acesso (`admin` e `author`) e proteção CSRF dos formulários que alteram dados.
- A senha é guardada só como hash. A sessão não leva a senha.
- A API lê os três posts mais novos, junto com a categoria, e devolve título, resumo, data, imagem e o link do post. A vitrine do site institucional usa exatamente essa resposta.

O banco `nf_blog` tem as tabelas `users`, `categories` e `posts`. A estrutura para importar está em `nf_blog/database/database.sql`.

As variáveis `NF_ALUGUEL_*`, no `.env.example`, já separam o futuro sistema de aluguel do banco do blog. Esse sistema ainda não está neste repositório.

## Blog, API e testes

O blog é a parte mais complexa do projeto: tem API, banco de dados, login e painel administrativo. Por isso também existe um registro dos testes em `nf_blog/database/testes-nf-blog.pdf`, junto com checklists manuais de autenticação, cadastro, posts, categorias, usuários e preparação para produção.

No fim, deu certo. Isso não significa que não possa melhorar.

## Como rodar

1. Coloque a pasta do projeto no `htdocs` do XAMPP e suba o Apache e o MySQL.
2. Importe `nf_blog/database/database.sql` no phpMyAdmin. Isso cria o banco `nf_blog`.
3. Abra `http://localhost/Nordeste_Ferramentas/`.

##
(Obrigado, Rodrigo, por confiar em mim para fazer este projeto. E desculpa pela demora ❤️)
