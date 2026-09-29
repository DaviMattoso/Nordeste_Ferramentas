# Sistema de Aluguel — Nordeste Ferramentas

Sistema interno planejado para gerenciar clientes, produtos, unidades físicas, estoque, reservas, aluguéis, devoluções, entregas, inspeções, danos e manutenções da Nordeste Ferramentas.

## Status atual

**Arquitetura inicial criada. Funcionalidades ainda não implementadas.**

Neste estágio não existem CRUDs, autenticação completa, dashboard operacional, migrations, tabelas ou integrações financeiras. Os arquivos executáveis exibem somente páginas introdutórias.

## Stack

- PHP;
- MySQL 8.0;
- PDO planejado para acesso ao banco;
- HTML, CSS e JavaScript puro;
- servidor Apache compatível com `.htaccess` no ambiente atual.

Nenhum framework ou dependência adicional foi incluído.

## Estrutura

```text
nf_aluguel/
├── api/          Endpoints futuros organizados por domínio
├── assets/       CSS, JavaScript, imagens e ícones
├── config/       Configurações gerais, banco e caminhos
├── database/     Espaço reservado para migrations e seeds
├── docs/         Documentação técnica do módulo
├── future/       Funcionalidades explicitamente fora do escopo atual
├── includes/     Componentes compartilhados e pontos de segurança
├── modules/      Áreas funcionais futuras
└── storage/      Logs, exportações e uploads gerados em execução
```

Consulte [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) para o detalhamento dos diretórios e do fluxo planejado.

## Módulos planejados

- Dashboard;
- clientes;
- categorias;
- produtos;
- patrimônio;
- estoque;
- reservas;
- aluguéis;
- devoluções;
- entregas;
- inspeções;
- danos;
- manutenção;
- relatórios;
- configurações.

As pastas em `api/` e `modules/` são apenas reservas arquiteturais. Nenhum endpoint ou fluxo de negócio foi implementado.

## Banco de dados

O sistema utilizará futuramente o mesmo banco MySQL do restante do projeto. A configuração em `config/database.php` segue as variáveis `NF_DB_*` já usadas pelo NF Blog e prepara opções seguras para PDO, mas não abre uma conexão ao ser incluída.

Não há SQL, migrations ou seeds executáveis neste módulo. Os nomes e relacionamentos planejados estão documentados em [`docs/DATABASE.md`](docs/DATABASE.md) e deverão ser revisados antes da primeira migration.

## Decisões de produto e arquitetura

1. O sistema será inicialmente apenas interno.
2. Haverá uma única operação e um único estoque.
3. Múltiplas filiais não serão modeladas nesta fase.
4. Os aluguéis poderão usar as modalidades diária, semanal e mensal.
5. O operador escolherá a modalidade de cada aluguel.
6. Não haverá caução.
7. Pagamentos ficam para uma etapa futura.
8. Haverá suporte futuro para retirada e entrega em obra.
9. O cliente deverá possuir CPF, RG, e-mail e celular obrigatórios.
10. Horímetro, manutenção baseada em horas e calibração não serão implementados.
11. Somente o perfil `ADMIN` poderá futuramente conceder desconto, realizar estorno ou baixa e desbloquear cliente.

### Produto e patrimônio

`PRODUTO` e `PATRIMÔNIO` são conceitos distintos:

- produto descreve o modelo comercial, por exemplo, “Furadeira Bosch GSB 13 RE”;
- patrimônio identifica cada unidade física, por exemplo, `PAT-000001`;
- cada patrimônio terá status, histórico, aluguéis e manutenções próprios.

Estoque comercial e disponibilidade física não deverão apagar essa separação.

## Segurança planejada

A evolução do módulo deverá manter:

- PDO com prepared statements;
- validação server-side e escape de HTML;
- proteção CSRF;
- sessão própria, cookies seguros e controle de acesso;
- validação de MIME, tamanho e destino de uploads;
- logs sem dados sensíveis;
- credenciais somente em variáveis do ambiente;
- bloqueio HTTP de diretórios internos.

O `.htaccess` fornece uma proteção inicial para Apache. Outros servidores deverão receber regras equivalentes antes da publicação.

## Funcionalidades futuras

Após a validação do modelo de dados, as próximas fases poderão incluir autenticação, permissões, cadastros, movimentação de estoque, reservas, contratos de aluguel, devoluções, logística, inspeções, danos, manutenções e relatórios. Pagamentos permanecem isolados em `future/payments/` e fora do escopo atual.
