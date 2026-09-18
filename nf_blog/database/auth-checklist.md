# Testes de login e sessão

Não executar database.sql. Usar uma conta já cadastrada no banco existente.

## Executado nesta etapa

- PHP 8.2.12 localizado em `G:\Down\Xampp\exe\php\php.exe`: todos os arquivos PHP do projeto passaram no lint.
- Testes HTTP com servidor PHP local e diretório temporário de sessões isolado: formulário único, mensagens condicionais de cadastro/logout e validação de campos vazios.
- As nove páginas administrativas redirecionam visitantes sem sessão para `../signin.php`.
- Com sessão de teste simulada de um author, as nove páginas respondem normalmente; o username é escapado e signin redireciona para o dashboard.
- Menus das seis páginas públicas com perfil mostram Signin para visitantes e perfil/dashboard/logout para autenticados.
- Sessão persiste entre requisições; logout expira o cookie, destrói a sessão e o cookie antigo não permite voltar ao dashboard.
- Revisão do código: apenas user_id, username, role e avatar são gravados na sessão. Senha e hash não são gravados.

Esses testes de sessão simulada não substituem o teste de credenciais reais com MySQL.

## Executar manualmente no XAMPP

1. Entrar com username e senha corretos: esperar redirect 303 para admin/dashboard.php.
2. Sair e entrar com email e senha corretos: mesmo resultado.
3. Tentar senha incorreta e username inexistente: ambos devem mostrar “Usuário/email ou senha inválidos.”
4. Enviar campos vazios (desabilitar a validação HTML pelo inspetor para testar o servidor): erro de obrigatoriedade.
5. Sem login, abrir cada página de admin diretamente: redirect para signin.php.
6. Após login, acessar dashboard e atualizar: continuar autenticado.
7. Abrir signin.php autenticado: redirect para dashboard.
8. Fazer logout: aparecer “Você saiu da conta.”; reabrir/atualizar dashboard deve exigir login.
9. No inspetor de cookies, conferir que PHPSESSID muda após login. Em localhost HTTP o cookie não exige Secure, mas usa HttpOnly e SameSite=Lax.
10. Inspecionar a sessão localmente: somente user_id, username, role e avatar, sem password/hash. Não publicar conteúdo de cookies ou sessões.
11. Conferir avatar enviado e fallback para quem não tem avatar; revisar aparência no navegador.
12. Conferir mensagem de cadastro em signin.php?registered=1 e ausência de mensagens permanentes.

## Pendências

Proteção CSRF (incluindo logout), limitação de tentativas de login e controle de propriedade dos posts ficam para revisão posterior. Os formulários administrativos continuam estáticos.

## Atualização: permissões admin/author

Os resultados de sessão simulada acima registram a etapa anterior ao controle por role. Agora usuários/categorias são exclusivos de admin. Para os testes manuais acima, visitantes continuam redirecionados ao login; authors só acessam dashboard e posts.

Testes HTTP executados com sessões isoladas de admin e author:

- Visitantes sem sessão são redirecionados ao login nas seis páginas restritas.
- Author é bloqueado em add-user, edit-user, manage-users, add-category, edit-category e manage-categories, sem emissão do HTML dessas páginas.
- Admin acessa todas as seis páginas; ambos acessam dashboard, add-post e edit-post.
- Menu do dashboard oculta usuários/categorias para author e exibe para admin.
- Acesso negado redireciona para dashboard.php?denied=1; aviso flash aparece uma vez. O parâmetro sozinho não gera mensagem.
- Enviar role=admin por GET/POST não concede permissão.
- Lint de auth.php e de todas as páginas administrativas passou.

No XAMPP, repetir esses cenários com login real de cada perfil e conferir o visual. Para testar admin temporariamente, selecionar uma conta de teste em `nf_blog.users` no phpMyAdmin, anotar seu ID e alterar somente seu role. SQL opcional (substituir 123 pelo ID correto):

```sql
UPDATE users SET role = 'admin' WHERE id = 123;
-- Ao terminar o teste, restaurar a conta de teste originalmente author:
UPDATE users SET role = 'author' WHERE id = 123;
```

Executar a promoção e a restauração em momentos separados. Após cada alteração, fazer logout e login novamente, pois o role é carregado na sessão durante o login. Nenhum desses comandos foi executado pela implementação; o cadastro público continua criando author.

Se o username de uma conta coincidir com o email de outra, o login rejeita a identificação ambígua com a mensagem genérica; usar o outro identificador exclusivo da conta.
