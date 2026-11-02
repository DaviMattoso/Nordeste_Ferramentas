# Progresso

Atualizado em 02/11/2026, ao final da fundação.

## Implementado

- Pasta `nf_aluguel` com conexão, página inicial, CSS, bloqueio HTTP, migrações e esta documentação.
- Regras `.cursor/rules/70` a `75`, com o mapa do projeto apontando para o módulo novo.
- Banco `nf_aluguel` criado no MariaDB local.
- Cinco migrações aplicadas e registradas em `schema_migrations`.
- Papéis e permissões iniciais. Nenhum usuário, cliente, produto ou contrato.

## Verificado agora

Pelo cliente `mysql` do XAMPP, como `cursor_nf` em `127.0.0.1`:

- 45 tabelas InnoDB em `utf8mb4_unicode_ci`;
- 89 chaves estrangeiras e 94 restrições `CHECK`;
- papéis com 28, 10, 5, 6, 3 e 5 permissões, na ordem administrador, atendimento, armazém, financeiro, manutenção e consulta;
- nome de marca vazio rejeitado pela restrição `brands_name_check`;
- valor de reposição negativo rejeitado por `products_replacement_check`;
- exclusão de armazém com posição vinculada rejeitada com o erro 1451;
- contagem final de marcas, categorias, produtos, armazéns e posições igual a zero depois desses testes;
- `users`, `customers`, `products`, `assets`, `settings` e `rentals` continuam vazios;
- privilégios de `cursor_nf` em `nf_aluguel.*`: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX e REFERENCES.

Pelo MCP MariaDB, em chamadas separadas do cliente:

- `list_databases` passou a mostrar `nf_aluguel`;
- `list_tables` listou as 45 tabelas;
- `get_table_schema_with_relations` em `assets` mostrou as FKs para produto, armazém, posição e fornecedor, com `ON DELETE RESTRICT`.

O MCP não executou as migrações. A ferramenta de SQL disponível para ele está publicada como leitura. A escrita foi feita pelo cliente `mysql`.

`php -l` não acusou erro em `index.php`, `config/constants.php` e `config/database.php`. O PHP 8.2.12 do XAMPP tem `mysqli`, `mysqlnd` e `fileinfo`.

O banco `nf_blog` não recebeu comando desta tarefa. As tabelas dele continuam `categories`, `posts` e `users`. Não houve backup porque nenhuma estrutura existente foi alterada: `nf_aluguel` nasceu vazio.

Não confie neste arquivo como prova no futuro. A próxima mudança precisa repetir a conferência.

## Verificado no navegador

O Apache do XAMPP não estava instalado como serviço. O processo foi reiniciado para carregar o alias `/aluguel/`. Depois disso:

- `http://127.0.0.1/aluguel/` respondeu 200, com o título "Base do aluguel preparada";
- a mesma página foi vista no navegador em desktop e em viewport de 390 px, com o texto quebrando dentro da coluna;
- `config/`, `database/`, `docs/` e `uploads/` responderam 403;
- `css/style.css` respondeu 200;
- `http://127.0.0.1/blog/` continuou respondendo 200.

## Ainda não exercitado

Login, cadastro, concorrência e os diálogos do site institucional não fazem parte desta entrega.

## Próximo passo

Etapa 2 do `plano.md`: criar o primeiro administrador, login, logout, sessão, CSRF e uma checagem real de permissão.
