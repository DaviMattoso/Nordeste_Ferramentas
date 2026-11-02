# Modelo inicial do banco

Banco aplicado: `nf_aluguel`. Motor: MariaDB 10.4.32, InnoDB, `utf8mb4_unicode_ci`.

Conferido em 02/11/2026 pelo cliente `mysql` e pelo MCP: 45 tabelas, 89 chaves estrangeiras e 94 restrições `CHECK`. Não há clientes, produtos, unidades, contratos nem usuários. Há 6 papéis e 28 permissões.

## Identificadores

Cada registro importante tem `id` numérico automático. O código visto pelo operador é outra coluna, única: `customer_code`, `sku`, `asset_code`, `reservation_number`, `quote_number`, `rental_number`, `maintenance_number` e `receipt_number`.

`sequence_counters` guarda o último número usado. A chave pode incluir o ano, como `rental-2026`, porque vários códigos da especificação reiniciam por ano. A formatação `ALU-2026-000341` fica na aplicação.

Datas são `DATETIME` no relógio do servidor MariaDB. O fuso da operação ainda não foi escolhido.

## Grupos

| Grupo | Tabelas |
| --- | --- |
| Controle | `schema_migrations`, `sequence_counters`, `settings` |
| Acesso | `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `audit_logs` |
| Catálogo | `categories`, `brands`, `products`, `product_prices`, `product_accessories`, `product_photos` |
| Patrimônio | `assets`, `asset_photos`, `asset_status_history` |
| Armazém | `warehouses`, `storage_locations`, `stock_movements`, `inventory_counts`, `inventory_count_items`, `suppliers` |
| Comercial | `customers`, `customer_addresses`, `customer_documents`, `quotes`, `quote_items`, `reservations`, `reservation_items` |
| Contrato | `rentals`, `rental_items`, `rental_extensions` |
| Operação | `inspections`, `inspection_items`, `inspection_photos`, `damage_reports`, `maintenance_orders`, `maintenance_parts`, `notes` |
| Financeiro | `receivables`, `payments`, `payment_allocations`, `deposits` |

`notifications` não foi criada. Entra quando houver alertas de verdade.

## Relações que não podem se perder

- produto pertence a uma categoria e pode ter uma marca;
- unidade pertence a um produto e aponta para armazém, posição e fornecedor;
- reserva, orçamento e contrato apontam para cliente, produto e, quando a peça é física, para a unidade;
- o item do contrato copia descrição, tipo de tarifa e valor;
- inspeção, dano e manutenção apontam para a unidade e, quando existem, para o contrato;
- parcela, pagamento, rateio e caução apontam para o contrato ou para o título, com exclusão restrita.

Cliente bloqueado, unidade em manutenção e conflito de período são regras da aplicação em cima dessas tabelas. O banco já impede valor monetário negativo, quantidade zero, devolução anterior à retirada, estorno sem pagamento de origem e caução usada acima do valor recebido.

## Estados gravados

Unidade: `AVAILABLE`, `RESERVED`, `PICKING`, `RENTED`, `RETURN_INSPECTION`, `MAINTENANCE`, `DAMAGED`, `QUARANTINE`, `LOST`, `RETIRED`.

Contrato: `DRAFT`, `CONFIRMED`, `READY_FOR_PICKUP`, `ACTIVE`, `OVERDUE`, `RETURNING`, `CLOSED`, `CANCELLED`. A reserva fica na tabela `reservations`, então o contrato não tem um status `RESERVED` duplicado.

A unidade não recebe status padrão. Um insert incompleto não pode nascer disponível.

## O que o banco não garante sozinho

- duas pessoas reservando a mesma unidade no mesmo período;
- cálculo de atraso, dano e imposto;
- versão anterior de um orçamento já substituído;
- imutabilidade da auditoria contra quem tem privilégio de `DELETE` no MariaDB. A aplicação não pode oferecer essa exclusão.

Movimentação de estoque usa os tipos `PURCHASE_IN`, `TRANSFER`, `PICKING`, `RENTAL_OUT`, `PHYSICAL_RETURN`, `MAINTENANCE_OUT`, `MAINTENANCE_RETURN`, `INVENTORY_ADJUSTMENT`, `RETIREMENT`, `CONSUMABLE_IN`, `CONSUMABLE_OUT` e `REVERSAL`. Correção é um movimento `REVERSAL`, não um delete.

## Dados iniciais

Só papéis e permissões. O administrador recebe as 28 chaves. Atendimento recebe 10, armazém 5, financeiro 6, manutenção 3 e consulta 5. A lista exata está na migração `20261102-0100-create-identity-and-audit.sql`.
