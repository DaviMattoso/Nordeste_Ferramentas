# NF Blog — Nordeste Ferramentas

## Sobre o projeto

O NF Blog é a área de conteúdo da Nordeste Ferramentas. O projeto reúne publicações sobre ferramentas, construção, marcenaria, segurança no trabalho, dicas e tutoriais e maquinaria pesada.

## Objetivo

O sistema permite publicar, organizar e gerenciar conteúdos por meio de perfis administrativos e de autores, mantendo uma área pública para leitura e pesquisa.

## Tecnologias

- HTML
- CSS
- JavaScript
- PHP 8.2
- MySQL/MariaDB
- Apache/XAMPP

## Funcionalidades

- Cadastro público de usuários com perfil `author`.
- Login por username ou email, logout e sessões.
- Perfis `admin` e `author`.
- CRUD administrativo de usuários e categorias.
- CRUD de posts para administradores e autores.
- Upload validado de avatar e thumbnail.
- Posts em destaque (`featured`).
- Home, listagem do blog e rodapés com dados dinâmicos.
- Página individual de post e filtro por categoria.
- Busca por título ou conteúdo.
- Categorias públicas dinâmicas.
- Proteção CSRF em operações mutáveis.
- Controle server-side de propriedade dos posts.
- Foreign Keys, índices e restrições de unicidade.
- Prepared statements e escaping de saída contra SQL injection e XSS.
- Hardening básico para Apache.

## Perfis

### Admin

- Gerencia usuários e categorias.
- Cria, edita e exclui qualquer post.
- Visualiza todos os posts no dashboard.

### Author

- Visualiza e gerencia somente os próprios posts.
- Não acessa a gestão de usuários ou categorias.

## Estrutura do banco

O banco `nf_blog` possui três tabelas principais:

- `users`: contas, credenciais em hash, avatar e perfil.
- `categories`: categorias editoriais.
- `posts`: conteúdo, thumbnail, destaque, autor e categoria.

Relacionamento principal:

```text
users ──< posts >── categories
```

As relações de posts com usuários e categorias usam Foreign Keys com atualização em cascata e exclusão restrita.

## Estrutura principal

```text
admin/                  painel e operações administrativas
config/                 conexão, autenticação, CSRF e utilitários
database/               schema e checklists técnicos
Images/avatars/         uploads gerenciados de avatar
Images/posts/           uploads gerenciados de thumbnail
css/                    estilos
js/                     comportamento do menu

index.php               home dinâmica
blog.php                listagem pública
post.php                post individual
category-post.php       posts por categoria
search.php              busca pública
```

## Segurança

- Senhas são tratadas com `password_hash()` e `password_verify()`.
- Consultas com entrada externa usam prepared statements.
- Formulários mutáveis exigem token CSRF.
- Saídas dinâmicas são escapadas para o contexto HTML.
- Uploads validam erro, tamanho, MIME e conteúdo real da imagem.
- Nomes de upload são aleatórios e gerenciados pela aplicação.
- Permissões e propriedade dos posts são verificadas no servidor.
- Foreign Keys protegem vínculos entre autores, categorias e posts.
- O `.htaccess` desabilita listagem de diretórios, bloqueia áreas internas e arquivos ocultos, impede scripts nos diretórios de upload e adiciona headers básicos quando os módulos estão disponíveis.

## Como executar localmente

1. Disponibilize o projeto no Apache do XAMPP de modo que a aplicação responda em `/blog/`.
2. Inicie Apache e MySQL/MariaDB.
3. Importe `database/database.sql` pelo phpMyAdmin ou cliente MySQL.
4. Acesse `http://localhost/blog/`.

O schema não cria contas ou dados de exemplo. Em uma instalação vazia, cadastre a primeira conta e conceda a ela o perfil `admin` diretamente no banco local, de forma controlada, para iniciar a administração. Depois disso, contas administrativas podem ser gerenciadas pela interface.

## Configuração por ambiente

Sem variáveis de ambiente, o projeto mantém os valores locais do XAMPP:

- URL: `http://localhost/blog/`
- host: `localhost`
- usuário: `root`
- senha: vazia
- banco: `nf_blog`

Em produção, configure no ambiente do servidor:

- `NF_ROOT_URL`
- `NF_DB_HOST`
- `NF_DB_USER`
- `NF_DB_PASS`
- `NF_DB_NAME`

`NF_ROOT_URL` deve conter a URL HTTPS pública real. Credenciais de produção não devem ser gravadas no Git.

## Produção

Antes da publicação, consulte `database/production-checklist.md`. Os principais pontos são:

- HTTPS obrigatório.
- Usuário MySQL de privilégio mínimo.
- Credenciais fora do Git.
- `display_errors = Off`.
- `log_errors = On`.
- `expose_php = Off`.
- Confirmação de que o servidor aplica o `.htaccess`.
- HSTS somente após HTTPS estar validado.
- Rate limit de login como melhoria recomendada para exposição pública prolongada.

## Status

**Funcionalmente concluído — aguardando publicação/configuração definitiva de produção e inclusão contínua de conteúdo.**

A integração com `site_principal` será realizada separadamente.
