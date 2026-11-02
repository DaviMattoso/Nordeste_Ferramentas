# Requisitos do sistema de aluguel

Fonte: especificação versão 1.0, de 28/09/2026. Este arquivo separa o que ela confirma, o que esta etapa decidiu para o MariaDB 10.4 e o que continua em aberto.

## Confirmado

O sistema controla duas coisas distintas:

- o **produto** é o modelo comercial, por exemplo uma furadeira de 750 W;
- a **unidade** (`assets`) é a ferramenta física, com patrimônio, série, local e estado.

Reserva não é contrato. Orçamento não é contrato. Consumível não é patrimônio. Status de tela não é texto livre.

Há seis perfis: administrador, atendimento/balcão, armazém, financeiro, manutenção e consulta/gerência. As 28 chaves de permissão são as da seção 3.1 da especificação.

O operador acompanha cliente, catálogo, patrimônio, armazém, disponibilidade, reserva, orçamento, contrato, inspeção, manutenção, caução, pagamento e relatório. A trilha de auditoria e o livro de movimentação não são apagados.

A integração com o site institucional e com o blog é futura. O catálogo público, se existir, não pode expor custo, cliente, observação interna nem ID técnico.

## Decisões desta fundação

| Assunto | Decisão | Motivo |
| --- | --- | --- |
| Banco | `nf_aluguel`, no mesmo MariaDB | A especificação aceita o mesmo servidor. O MariaDB 10.4 não tem schema. O blog já usa `users` e `categories` com outras regras. |
| Identificador | `BIGINT UNSIGNED` e código humano único | Não há tipo UUID neste MariaDB. A especificação aceita o inteiro longo e exige o código separado. |
| Login compartilhado com o blog | Não nesta etapa | Autor e administrador do blog não são os perfis do armazém. |
| Usuário inicial | Nenhum | Senha não entra no repositório. |
| Parâmetros comerciais | Tabela `settings` vazia | Multa, juros e tolerância não têm número na especificação. |
| Versões antigas de orçamento | Campo `version_number` sem histórico | `quote_number` é único. A tabela de versões anteriores fica para a etapa de orçamento. |
| Sobreposição de agenda | Não cabe em `UNIQUE` | O MariaDB 10.4 não tem restrição de período. A transação da aplicação fará o bloqueio. |

O mapeamento papel → permissão está no SQL da identidade. Ele segue a descrição de cada perfil e não concede chave que a seção 3 não entrega àquele perfil. Onde a especificação cita a tarefa mas não a chave, a pendência ficou registrada abaixo.

## Pendências

Não transformar estes itens em regra numérica sem uma decisão da operação:

- teto de desconto por usuário e o que acontece acima dele;
- fórmula de diária extra, multa, juros, frete, limpeza e imposto;
- prazo de tolerância antes de marcar perda;
- tempo de inspeção entre uma devolução e a próxima saída;
- escalas de severidade do dano, prioridade da manutenção e estado físico;
- valores de caução, crédito e bloqueio automático do cliente;
- meios de pagamento que a empresa realmente usa;
- fuso horário oficial da operação;
- prazos de retenção e anonimização da LGPD;
- fluxo de recuperação de senha;
- se inspeção e inventário ganharão chaves próprias ou continuarão no papel `armazem`;
- qual perfil, além do administrador, pode cancelar contrato, cadastrar patrimônio ou dar baixa;
- histórico das versões anteriores do orçamento;
- catálogo público e ligação com os diálogos do site institucional.

## Fora desta entrega

Telas de cadastro, login, disponibilidade, contrato, financeiro, relatórios e a integração com `site_principal/` não foram implementadas. O banco já guarda a estrutura para essas etapas.
