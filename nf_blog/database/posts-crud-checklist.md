# Verificação do CRUD administrativo de posts no XAMPP

## Preparação e banco

- Inicie Apache e MySQL, selecione `nf_blog` no phpMyAdmin e confirme que `users` e `categories` existem.
- Execute uma vez o SQL abaixo. Ele não apaga dados existentes.
- Confirme as duas foreign keys e os três índices com `SHOW CREATE TABLE posts;` e `SHOW INDEX FROM posts;`.
- Confirme que `Images/posts/` permite escrita e que `fileinfo` está ativo. Configure `upload_max_filesize >= 5M` e `post_max_size > 5M`.
- Use as contas de teste ADMIN e AUTHOR em sessões separadas. Não registre senhas nem hashes neste arquivo.

```sql
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    thumbnail VARCHAR(255) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY posts_category_idx (category_id),
    KEY posts_author_idx (author_id),
    KEY posts_featured_idx (is_featured),
    CONSTRAINT posts_category_fk
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT posts_author_fk
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## CREATE

| Teste | Resultado esperado |
| --- | --- |
| ADMIN cria post com categoria e JPG/PNG/WebP | Registro criado com `author_id` do ADMIN, thumbnail em `Images/posts/` e mensagem de sucesso uma vez |
| AUTHOR cria post | Registro criado com `author_id` do AUTHOR |
| Enviar `author_id` manipulado por POST | Ignorado; autor continua sendo o usuário da sessão |
| Criar sem thumbnail | Erro; nenhum registro |
| Enviar arquivo inválido, SVG ou imagem acima de 5 MB | Erro; nenhum registro nem arquivo novo |
| Enviar categoria inexistente ou ID manipulado | Erro; nenhum registro nem arquivo novo |
| Título vazio, acima de 255 caracteres ou conteúdo vazio | Erro; nenhum registro |
| Marcar e desmarcar destaque | `is_featured` fica respectivamente 1 e 0 |
| Tentar criar sem categorias no banco | Bloqueado; ADMIN vê link de cadastro, AUTHOR não |

## READ e duas contas

| Teste | Resultado esperado |
| --- | --- |
| Criar um post com ADMIN e outro com AUTHOR | ADMIN vê ambos no dashboard |
| Abrir dashboard como AUTHOR | Vê somente o próprio post |
| Conferir linhas fictícias antigas | Não aparecem, salvo se existem como posts reais no banco |
| Conferir título e categoria com caracteres especiais | Aparecem como texto, sem executar HTML |

## UPDATE

| Teste | Resultado esperado |
| --- | --- |
| ADMIN edita qualquer post | Alterações salvas |
| AUTHOR edita o próprio post | Alterações salvas |
| AUTHOR abre `edit-post.php?id=ID_DO_ADMIN` | Bloqueado antes de mostrar formulário; flash no dashboard |
| Editar sem nova thumbnail | Thumbnail atual permanece no banco e no disco |
| Trocar thumbnail | Nova imagem salva; antiga gerenciada removida após sucesso |
| Trocar categoria e destaque | Novos valores salvos, `author_id` permanece igual |
| Editar com ID inválido ou inexistente | Redirecionamento ao dashboard com erro |

## DELETE, foreign keys e permissões

| Teste | Resultado esperado |
| --- | --- |
| ADMIN exclui qualquer post | Registro e thumbnail gerenciada removidos |
| AUTHOR exclui o próprio post | Registro e thumbnail gerenciada removidos |
| AUTHOR envia POST com ID de post do ADMIN | Bloqueado; post e arquivo permanecem |
| Abrir `delete-post.php` via GET | Nenhuma exclusão |
| Enviar ID inválido ou inexistente | Nenhuma exclusão; flash de erro |
| Excluir categoria vinculada a post | Bloqueado; mensagem amigável, categoria permanece |
| Excluir usuário vinculado a post | Bloqueado; mensagem amigável, usuário permanece |
| AUTHOR abre URLs administrativas de usuários/categorias | Acesso continua negado |

## Conferência no phpMyAdmin

```sql
SELECT id, title, category_id, author_id, is_featured, thumbnail, created_at, updated_at
FROM posts ORDER BY id ASC;
```

Confira IDs reais, timestamps, índice e caminhos de thumbnail. Para testar validação server-side, remova temporariamente os atributos HTML `required`/`maxlength` no inspetor. Remova somente os posts de teste pela interface. A proteção CSRF e as páginas públicas dinâmicas ficam para etapas futuras.
