# Verificação do cadastro público

Implementação limitada ao cadastro. Login e sessões ainda não foram implementados.
Pendência de segurança: adicionar proteção CSRF antes da publicação.

## Preparação

- Iniciar Apache e MySQL no XAMPP e servir a pasta `nf_blog`.
- Usar o banco existente, sem executar novamente `database.sql`.
- Confirmar PHP com `mysqli` e `fileinfo`, e permissão de escrita em `Images/avatars`.
- Para avatares de até 2 MB, configurar `upload_max_filesize` com pelo menos `2M` e `post_max_size` maior que `2M`, para acomodar o formulário inteiro.
- Executar `php -l nf_blog/signup.php` e `php -l nf_blog/signin.php` na raiz do repositório (ou usar o caminho completo do PHP do XAMPP).

## Cenários a executar

Usar username/email de teste diferentes dos usuários reais. Para verificar a validação no servidor, desativar temporariamente a validação HTML com `novalidate` pelo inspetor do navegador.

| Cenário | Resultado esperado |
| --- | --- |
| Cadastro normal com dados inéditos e senha de 8 ou mais caracteres | Redirecionamento HTTP 303 para `signin.php?registered=1` e mensagem de conta criada |
| Senhas diferentes | Erro de confirmação, sem INSERT |
| Email inválido | Erro de email, sem INSERT |
| Username repetido | Erro de username, sem INSERT |
| Email repetido | Erro de email, sem INSERT |
| Sem avatar | Cadastro com `avatar IS NULL` |
| JPG, PNG ou WebP válido até 2 MB | Caminho `Images/avatars/<nome aleatório>.<extensão>` no banco e arquivo no diretório |
| Texto renomeado para `.jpg`, SVG ou arquivo maior que 2 MB | Erro de avatar, sem INSERT e sem novo arquivo |
| Campos vazios | Erro de obrigatoriedade, sem INSERT |
| Avatar válido com outro campo inválido ou duplicado | Sem novo arquivo no diretório |
| Nome contendo aspas e marcação HTML | Valor preservado como texto, sem executar HTML |
| POST com `role=admin` | Registro criado como `author` |

Após erros, textos devem permanecer preenchidos; senhas e arquivo precisam ser selecionados novamente. Abrir `signin.php` sem o parâmetro não deve mostrar mensagem de sucesso. O parâmetro é somente informativo, sem autenticação ou sessão.

## Conferência no MySQL

Consultar o usuário de teste pelo username, substituindo o exemplo:

```sql
SELECT id, first_name, last_name, username, email, password, avatar,
       role, created_at, updated_at
FROM users
WHERE username = 'nf_teste_cadastro';

SHOW INDEX FROM users;
```

Conferir `role = 'author'`, datas geradas pelo banco e os índices UNIQUE de username/email. A senha deve conter somente o hash gerado por `password_hash`, diferente da senha enviada (no bcrypt, normalmente começa com `$2y$`). Não compartilhar a senha nem o hash em relatórios públicos.

## Verificação nesta implementação

Revisão estática do fluxo e `git diff --check` realizados. Não foi localizado executável PHP nem serviço MySQL acessível neste ambiente: lint PHP, testes HTTP e confirmação de registros/hashes no MySQL permanecem pendentes no XAMPP.
