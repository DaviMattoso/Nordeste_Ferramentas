# Verificação do CRUD administrativo de categorias no XAMPP

## Preparação

- Inicie Apache e MySQL no XAMPP e selecione o banco local `nf_blog` no phpMyAdmin.
- Execute uma vez o SQL abaixo. Ele não apaga nem modifica a tabela `users`.
- Entre com uma conta admin e use títulos de teste próprios.
- Para testar validação no servidor, remova temporariamente `required` e `maxlength` pelo inspetor do navegador.

```sql
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY categories_title_unique (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## CREATE e READ

| Teste | Resultado esperado |
| --- | --- |
| Criar categoria válida | Redireciona com HTTP 303; mensagem de sucesso aparece uma vez |
| Criar com descrição vazia | Registro criado com `description IS NULL` |
| Título vazio ou só com espaços | Erro; nenhum registro criado |
| Título com mais de 150 caracteres | Erro; nenhum registro criado |
| Título já cadastrado | Mensagem “Categoria já existe.”; nenhum registro novo |
| Listar categorias | Título e descrição vêm do MySQL, em ordem de ID |
| Conferir categorias fictícias da tabela antiga | Não aparecem como linhas, salvo se foram cadastradas no banco |

## UPDATE

| Teste | Resultado esperado |
| --- | --- |
| Editar título | Novo título aparece na listagem |
| Editar descrição | Nova descrição aparece na listagem |
| Salvar sem mudar o próprio título | Edição aceita |
| Usar título de outra categoria | Erro de duplicidade; valores preenchidos permanecem |
| Abrir `edit-category.php?id=0`, texto ou ID inexistente | Redireciona à listagem com mensagem apropriada |

## DELETE e permissões

| Teste | Resultado esperado |
| --- | --- |
| Excluir categoria de teste pelo botão | Registro removido; mensagem aparece uma vez |
| Abrir `delete-category.php` via GET | Nenhuma exclusão |
| Enviar ID inválido ou inexistente por POST | Nenhuma exclusão; mensagem de erro |
| Entrar como author e abrir add/manage/edit/delete diretamente | Acesso negado em todas as páginas |

## Conferência no phpMyAdmin

```sql
SELECT id, title, description, created_at, updated_at
FROM categories ORDER BY id ASC;

SHOW INDEX FROM categories;
```

Confirme o índice UNIQUE em `title`, a atualização automática de `updated_at` ao alterar dados e a ausência dos registros excluídos. Exclua somente as categorias de teste pela interface. Não há relação com posts nesta etapa; `posts.category_id` deverá ser ligado a `categories.id` quando a tabela de posts for implementada. Proteção CSRF permanece pendente para revisão posterior.
