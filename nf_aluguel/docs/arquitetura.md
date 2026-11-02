# Arquitetura e arquivos

O aluguel é um terceiro módulo PHP. Ele não inclui o blog nem o institucional.

## Por que a pasta e o banco são separados

`nf_blog/config/database.php` abre `nf_blog` e a sessão do blog conhece só `admin` e `author`. Reusar essa conexão colocaria patrimônio e post no mesmo lugar. A especificação pede o módulo em `nf_aluguel/` e manda confirmar o motor antes da migração. O motor é MariaDB 10.4.32, então o isolamento ficou em outro banco no mesmo servidor, não em schema PostgreSQL.

As credenciais continuam fora do Git, em variáveis de ambiente. O exemplo versionado é `.env.example`. Um arquivo `.env` sozinho não configura o PHP.

## O que existe agora

```text
nf_aluguel/
├── index.php              # confirma a base, sem operação
├── .htaccess              # bloqueia pastas internas
├── config/
│   ├── constants.php      # NF_ALUGUEL_*
│   ├── database.php       # mysqli do banco nf_aluguel
│   └── .htaccess
├── css/style.css
├── database/migrations/   # SQL aplicado
├── docs/                  # este acompanhamento
└── uploads/               # arquivos futuros, sem acesso HTTP
```

Ainda não existem `admin/`, `api/` e `js/`. Essas pastas abrem quando a primeira tela ou endpoint real chegar.

## Proteção no Apache

O XAMPP publica o módulo em `http://localhost/aluguel/`. O alias foi acrescentado em `httpd.conf`, no mesmo padrão de `/blog/`. A pasta do projeto não é a `DocumentRoot` do XAMPP; sem o alias o PHP não responde nesse endereço.

O `.htaccess` nega `config`, `database`, `docs` e `uploads`. Cada uma dessas pastas também tem um bloqueio próprio. Upload não é executado nem aberto por URL direta. Em produção, `uploads/` deve sair da pasta publicada.

O servidor embutido do PHP não lê `.htaccess`.

## O que a página inicial faz

`index.php` abre a conexão e conta as cinco migrações de fundação. Se as cinco estiverem em `schema_migrations`, a página diz que a base está pronta. Ela não lista tabelas, usuários nem credenciais. Quando o login existir, essa página deixa de ser o ponto de entrada operacional.

## Integrações que continuam como estão

- `nf_blog/` não foi alterado.
- `site_principal/` continua com os diálogos de catálogo e portal de locação.
- A API `nf_blog/api/latest-posts.php` não faz parte do aluguel.

A fase de integração liga o institucional ao catálogo público sem copiar posts para dentro do aluguel.
