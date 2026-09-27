# Preparação para produção

- Exigir HTTPS e definir `NF_ROOT_URL` com a URL pública HTTPS real, mantendo a barra final.
- Definir `NF_DB_HOST`, `NF_DB_USER`, `NF_DB_PASS` e `NF_DB_NAME` no ambiente do servidor. Usar um usuário MySQL de privilégio mínimo e manter credenciais fora do Git.
- Configurar o PHP de produção com `display_errors = Off`, `log_errors = On` e `expose_php = Off` no painel da hospedagem ou no `php.ini` aplicável.
- Manter o `.htaccess` distribuído com o projeto: ele desabilita listagem de diretórios, bloqueia áreas internas e arquivos ocultos, protege uploads contra scripts e adiciona headers básicos quando os módulos estão disponíveis.
- Habilitar HSTS somente depois que HTTPS estiver confirmado em todo o domínio e subdomínios aplicáveis.
- Antes da exposição pública de longo prazo, considerar limitação de tentativas de login por IP/identificador usando proxy, servidor web ou armazenamento compartilhado.
