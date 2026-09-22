# Verificação do CRUD administrativo de usuários no XAMPP

## Preparação

- Inicie Apache e MySQL e use o banco `nf_blog` existente. Não reimporte `database.sql`.
- Confira `mysqli`, `fileinfo`, escrita em `Images/avatars/`, `upload_max_filesize >= 2M` e `post_max_size > 2M`.
- Entre com uma conta admin. Crie contas de teste com usernames e emails próprios; não use contas reais para exclusão.
- Anote os IDs de teste e confira os resultados no phpMyAdmin. Não registre senhas reais neste arquivo.
- Para testar validações do servidor, use o inspetor do navegador para remover temporariamente `required`, `minlength` ou opções do select.
- Execute `php -l` em `config/auth.php`, `config/flash.php`, `config/user-utils.php`, `signup.php`, `admin/add-user.php`, `admin/manage-users.php`, `admin/edit-user.php`, `admin/delete-user.php` e `admin/dashboard.php` com o PHP do XAMPP.

## CREATE e READ

| Teste | Resultado esperado |
| --- | --- |
| Criar author | Registro real com `role = 'author'`; mensagem de sucesso uma vez |
| Criar outro admin | Registro real com `role = 'admin'` |
| Username repetido | Erro, sem novo registro |
| Email repetido | Erro, sem novo registro |
| Senhas diferentes | Erro, sem novo registro |
| JPG, PNG ou WebP válido até 2 MB | Caminho aleatório `Images/avatars/...` no banco e arquivo no disco |
| Arquivo de texto renomeado, SVG ou imagem acima de 2 MB | Erro, sem registro ou arquivo novo |
| Criar sem avatar | `avatar IS NULL` |
| Abrir Gerenciar Usuários | Nomes, usernames e Admin vindos do banco, ordenados por ID |
| Conferir nomes fictícios antigos | Não aparecem, a menos que existam como registros reais no banco |

## UPDATE

| Teste | Resultado esperado |
| --- | --- |
| Editar nome, username e email separadamente | Valores alterados no banco e na listagem |
| Alterar author para admin e admin para author | Role alterado quando permitido |
| Editar sem nova senha | Hash anterior permanece igual |
| Informar nova senha e confirmação | Hash muda; novo login funciona |
| Trocar avatar | Novo arquivo e caminho; avatar gerenciado antigo removido |
| Manter avatar sem enviar outro | Caminho e arquivo permanecem |
| Username ou email de outro usuário | Erro; nenhuma alteração |
| Abrir `edit-user.php?id=0`, texto ou ID inexistente | Redirecionamento à listagem com erro |
| Editar próprio username e avatar | Cabeçalho e sessão mostram os novos dados sem novo login |
| Rebaixar o último admin | Bloqueado com mensagem |
| Rebaixar a própria conta quando há outro admin | Vai ao dashboard e perde acesso ao CRUD |

## DELETE e permissões

| Teste | Resultado esperado |
| --- | --- |
| Excluir author | Registro removido; avatar gerenciado removido |
| Excluir admin quando há outro admin | Registro removido |
| Excluir própria conta | Bloqueado com mensagem |
| Excluir último admin | Bloqueado com mensagem |
| Abrir `delete-user.php` via GET | Nenhuma exclusão |
| Enviar ID inválido ou inexistente por POST | Nenhuma exclusão; mensagem de erro |
| Entrar como author e abrir URLs de criar, listar, editar ou excluir | Acesso negado |
| Enviar `role` fora de `author`/`admin` por POST | Alteração bloqueada |
| Excluir ou rebaixar admin em outra sessão e tentar usar o CRUD nela | Acesso revogado na próxima requisição protegida |

## Conferência no banco

```sql
SELECT id, first_name, last_name, username, email, role, avatar, created_at, updated_at
FROM users ORDER BY id ASC;
```

Confira os hashes no banco sem copiá-los para relatórios. Depois dos testes, exclua somente as contas de teste pela interface. Proteção CSRF continua pendente para revisão futura, conforme o escopo desta etapa.
