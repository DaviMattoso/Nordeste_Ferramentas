# Arquitetura do Sistema de Aluguel

## Objetivo

O módulo `nf_aluguel` será o sistema interno de gerenciamento do ciclo de aluguel de ferramentas da Nordeste Ferramentas. Ele permanece independente do site institucional e do NF Blog, compartilhando futuramente apenas a infraestrutura e o banco MySQL definido para o projeto.

A estrutura atual é um esqueleto: não existem regras de negócio, endpoints ativos, autenticação completa, migrations ou tabelas novas.

## Responsabilidade dos diretórios

| Diretório | Responsabilidade planejada |
| --- | --- |
| `api/` | Endpoints HTTP pequenos, agrupados pelo domínio que atendem. |
| `assets/` | CSS, JavaScript puro, imagens e ícones do módulo. |
| `config/` | Configuração geral, parâmetros do banco e caminhos internos. |
| `database/` | Migrations e seeds futuros, após revisão e aprovação do modelo. |
| `docs/` | Decisões de arquitetura e modelo de dados planejado. |
| `future/` | Assuntos conhecidos, mas explicitamente fora da versão atual. |
| `includes/` | Estrutura compartilhada de página e pontos futuros de segurança. |
| `modules/` | Áreas funcionais da interface e seus fluxos de aplicação. |
| `storage/` | Logs, exportações e uploads produzidos durante a execução. |

Diretórios sem implementação contêm somente `.gitkeep`. Isso preserva a arquitetura no Git sem introduzir código artificial.

## Fluxo geral planejado

```text
Navegador
   ├── páginas PHP ──> includes ──> módulos ──> serviços futuros ──> PDO ──> MySQL compartilhado
   └── JavaScript ──> APIs JSON ──> validação/autorização ────────> PDO ──> MySQL compartilhado
```

As páginas renderizadas pelo servidor comporão a interface com os arquivos de `includes/`. Operações assíncronas poderão chamar endpoints em `api/`. Ambos os caminhos deverão aplicar as mesmas regras de autenticação, autorização, validação e auditoria antes de acessar o banco.

## Separação entre frontend e backend

- O frontend fica nas páginas PHP, nos componentes de `includes/` e nos arquivos de `assets/`.
- O backend será composto pelas validações e regras dos módulos, endpoints em `api/` e acesso ao banco por PDO.
- HTML não deverá montar consultas SQL.
- JavaScript não deverá conter regras que precisem ser confiáveis no servidor.
- Toda entrada deverá ser revalidada no PHP, mesmo quando já validada no navegador.

Não será aplicada uma transformação global do repositório em MVC. A separação evoluirá apenas dentro deste módulo e conforme a necessidade real.

## APIs

Cada subdiretório de `api/` representa um domínio: clientes, produtos, patrimônio, estoque, reservas, aluguéis, devoluções, entregas, manutenção, dashboard e relatórios. Os endpoints futuros deverão:

- aceitar somente métodos HTTP previstos;
- responder JSON consistente;
- validar autenticação, permissão e CSRF quando houver alteração de estado;
- validar os dados no servidor;
- usar prepared statements;
- evitar detalhes internos em mensagens de erro.

Nenhum endpoint foi criado nesta etapa.

## Módulos

As pastas em `modules/` separam as telas e os fluxos por área de negócio. A divisão permite desenvolver cada domínio gradualmente sem modificar o site institucional ou o blog. Código comum só deverá ser extraído quando existir reutilização concreta.

## Produto e patrimônio

A arquitetura adota desde o início a regra `PRODUTO != UNIDADE FÍSICA`.

Um produto descreve o modelo comercial. Um patrimônio representa uma unidade física individual desse produto. Assim, várias unidades podem apontar para o mesmo produto, mas cada uma mantém identificação, status, histórico de aluguel, inspeções, danos e manutenções próprios.

Exemplo:

```text
Produto: Furadeira Bosch GSB 13 RE
├── PAT-000001
├── PAT-000002
└── PAT-000003
```

Reservas e itens de aluguel deverão preservar qual produto foi solicitado e, quando ocorrer a separação, qual patrimônio físico foi entregue.

## Banco compartilhado

`config/database.php` reutiliza conceitualmente as variáveis `NF_DB_HOST`, `NF_DB_PORT`, `NF_DB_NAME`, `NF_DB_USER` e `NF_DB_PASS`. Ele retorna parâmetros e opções para uma futura conexão PDO, sem abrir conexão nem executar comandos.

O banco atual do NF Blog já possui tabelas chamadas `users`, `categories` e `posts`. Como `users` e `categories` também aparecem no modelo preliminar do aluguel, a estratégia de compartilhamento ou separação dessas entidades deverá ser definida antes das migrations. Nenhuma tabela existente deve ser reaproveitada, ampliada ou renomeada sem essa revisão.

## Configuração

- `config/app.php`: nome, ambiente, fuso horário e URL-base do módulo.
- `config/database.php`: parâmetros do MySQL compartilhado e opções de PDO.
- `config/paths.php`: caminhos internos centralizados, independentes do diretório de execução.

Credenciais de produção deverão existir somente no ambiente do servidor.

## Includes

- `header.php`, `sidebar.php` e `footer.php` formam a estrutura visual inicial.
- `auth-check.php` reserva o ponto de autenticação e autorização.
- `flash-messages.php` reserva mensagens temporárias após redirecionamentos.
- `csrf.php` reserva a proteção de formulários e requisições que alterem estado.

A autenticação futura deverá usar uma sessão própria do módulo, sem apagar ou sobrescrever chaves da sessão do NF Blog.

## Uploads, logs e exportações

`storage/` não é conteúdo público. O `.htaccess` bloqueia acesso HTTP a esse diretório em Apache, e `storage/.gitignore` evita versionar dados gerados em execução. Em produção, o servidor deverá aplicar regras equivalentes e permissões mínimas.

Uploads futuros deverão validar tamanho, MIME real e extensão permitida; gerar nomes imprevisíveis; impedir execução; e guardar apenas referências controladas no banco. Logs não deverão registrar senhas, documentos completos, tokens ou credenciais.

## Documentação

- `README.md` apresenta o escopo do módulo e as decisões do produto.
- `docs/ARCHITECTURE.md` registra a organização técnica.
- `docs/DATABASE.md` registra o modelo preliminar, sem SQL executável.
- `database/README.md` define as regras para futuras migrations e seeds.

## Pagamentos futuros

Pagamentos não pertencem à versão atual. O diretório `future/payments/` registra apenas os meios planejados e impede que checkout, gateway, cobrança ou estrutura financeira sejam introduzidos antes da definição do produto.
