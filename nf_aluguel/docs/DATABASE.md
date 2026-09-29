# Modelo de dados planejado

## Estado atual

Este documento é apenas uma referência arquitetural. **Nenhuma tabela, migration, seed ou comando SQL foi criado ou executado para o Sistema de Aluguel.** Os nomes abaixo são provisórios e deverão ser revisados antes da implementação.

O módulo usará futuramente o mesmo MySQL 8.0 do projeto Nordeste Ferramentas. A integração deverá preservar integralmente as tabelas e os dados já utilizados pelo NF Blog.

## Entidades previstas

- `users`: operadores e administradores do sistema interno;
- `customers`: clientes com CPF, RG, e-mail e celular obrigatórios;
- `customer_addresses`: endereços do cliente, inclusive locais de entrega;
- `categories`: classificação dos produtos;
- `products`: modelos comerciais disponíveis para locação;
- `assets`: unidades físicas identificadas por patrimônio;
- `stock_movements`: histórico de entradas, saídas e ajustes de estoque;
- `reservations`: cabeçalho e estado das reservas;
- `reservation_items`: produtos e quantidades reservadas;
- `rentals`: contrato, cliente, modalidade e período do aluguel;
- `rental_items`: itens do aluguel e patrimônios efetivamente entregues;
- `deliveries`: retirada ou entrega em obra e seu acompanhamento;
- `returns`: devolução de um aluguel;
- `inspections`: inspeções de saída e retorno das unidades físicas;
- `damage_reports`: danos identificados e seu vínculo com inspeções e patrimônios;
- `maintenance_orders`: manutenções por patrimônio;
- `audit_logs`: registro das ações sensíveis;
- `settings`: configurações operacionais do módulo.

## Relações conceituais

```text
customers ──< customer_addresses
customers ──< reservations ──< reservation_items >── products
customers ──< rentals ──< rental_items >── assets >── products
rentals ──< deliveries
rentals ──< returns ──< inspections ──< damage_reports
assets ──< inspections
assets ──< maintenance_orders
products ──< stock_movements
users ──< audit_logs
```

A cardinalidade e os vínculos definitivos dependem do levantamento dos fluxos operacionais.

## Regra de produto e patrimônio

`products` descreve o tipo/modelo da ferramenta. `assets` descreve cada unidade física individual. Um produto pode ter vários patrimônios, mas cada patrimônio terá código, status, histórico, aluguel, inspeção e manutenção próprios.

Uma reserva pode inicialmente registrar produto e quantidade. Antes da entrega, o aluguel deverá associar as unidades físicas efetivamente separadas. Esse desenho evita tratar três furadeiras idênticas como um único objeto operacional.

## Decisões que afetam o modelo

- uma única operação e um único estoque;
- nenhuma modelagem de filiais neste momento;
- modalidades diária, semanal e mensal escolhidas pelo operador;
- nenhuma caução;
- retirada ou entrega em obra;
- nenhuma medição por horímetro;
- nenhuma manutenção baseada em horas;
- nenhuma calibração;
- desconto, estorno, baixa e desbloqueio de cliente restritos ao perfil `ADMIN`;
- pagamentos fora do modelo atual.

## Integração com o banco existente

O banco do NF Blog já contém `users`, `categories` e `posts`. Os nomes preliminares `users` e `categories` deste módulo colidem semanticamente com tabelas existentes. Antes de qualquer migration será necessário decidir, com base em requisitos de identidade e catálogo, se haverá compartilhamento controlado ou nomes específicos para o aluguel.

Até essa decisão, nenhuma chave estrangeira, alteração de tabela ou reaproveitamento deve ser presumido.

## Requisitos para a futura implementação

- migrations pequenas, revisáveis e reversíveis quando possível;
- InnoDB, `utf8mb4` e chaves estrangeiras explícitas;
- índices definidos pelos fluxos reais de consulta;
- prepared statements via PDO;
- documentos pessoais protegidos e exibidos com mascaramento quando adequado;
- trilha de auditoria para operações administrativas sensíveis;
- backups testados antes de qualquer alteração em produção.
