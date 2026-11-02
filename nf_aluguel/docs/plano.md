# Plano de implementação

A ordem segue a especificação: banco e regras antes da tela, produto separado da unidade antes da disponibilidade, disponibilidade antes do contrato, contrato antes do relatório. Cada etapa termina com um fluxo pequeno que dá para conferir.

## Etapa 1 — Fundação

Critério: o módulo abre no Apache, conecta em `nf_aluguel`, as migrações estão registradas e as pastas internas não são baixáveis.

Estado: estrutura, regras, documentação e banco concluídos. Falta o acesso operacional, que é a próxima tarefa.

## Etapa 2 — Acesso

Critério de conclusão:

- criar o primeiro administrador por um procedimento local, sem gravar senha no Git;
- entrar com e-mail ou username e sair;
- sessão com cookie HttpOnly, SameSite=Lax e Secure apenas em HTTPS;
- CSRF nos formulários que alteram estado;
- uma página recusa quem não tem a permissão e aceita quem tem;
- usuário sem conta não se cadastra sozinho.

## Etapa 3 — Cadastros

Critério: cadastrar categoria, marca, produto e três unidades com códigos diferentes; cliente pessoa física e jurídica sem documento repetido; armazém e posição; tarifa do produto. Trocar a tarifa não altera um contrato antigo, porque o contrato ainda não existe, mas o preço fica em `product_prices` com vigência.

## Etapa 4 — Disponibilidade, reserva e orçamento

Critério: consultar um período e ver a quantidade livre; reservar duas unidades; a terceira tentativa sobre a mesma unidade e o mesmo período falha; reserva vencida libera a agenda; orçamento guarda o snapshot e ainda precisa definir como as versões antigas serão preservadas.

## Etapa 5 — Contrato, retirada e devolução

Critério: converter reserva em contrato, separar a unidade, registrar checklist de saída, mudar o estado para `RENTED`, recusar unidade em manutenção, prorrogar sem conflito, bloquear prorrogação com conflito, devolver parte dos itens e só liberar a unidade depois da inspeção.

## Etapa 6 — Danos e manutenção

Critério: dano na devolução gera valor e manutenção; ordem aberta tira a unidade da disponibilidade; conclusão devolve a unidade por uma ação explícita; baixa exige `asset.retire`.

## Etapa 7 — Financeiro

Critério: caução, parcela, pagamento parcial, pagamento final e estorno rastreável. As fórmulas de multa e juros só entram depois que os números existirem em `settings`. Até lá, o sistema registra os valores informados e não inventa taxa.

## Etapa 8 — Relatórios e auditoria

Critério: dashboard do dia e um relatório que bata com contratos e pagamentos de uma base conhecida. Ações críticas gravam `audit_logs`.

## Etapa 9 — Integração e produção

Critério: link do institucional para o que for público, sem vazar dado operacional; concorrência de dois operadores na mesma unidade; backup restaurado uma vez; HTTPS e rate limit tratados como infraestrutura, não como detalhe de tela.

## Próxima tarefa

Implementar a etapa 2, o acesso. Não abrir cadastro de produto antes de existir usuário, papel e CSRF.
