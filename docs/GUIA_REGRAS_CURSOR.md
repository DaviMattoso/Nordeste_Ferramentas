# Guia das regras do Cursor

Este guia explica as regras de desenvolvimento do Nordeste Ferramentas usadas pelo agente no Cursor. Elas orientam continuidade do trabalho; não substituem a leitura do código.

Atualizado em 02/11/2026, com base nos arquivos do repositório aberto neste workspace.

## Onde estão

Os arquivos `.mdc` ficam em `.cursor/rules/`. Cada arquivo tem um cabeçalho YAML:

- `description`: resume o assunto (aparece na seleção de regras).
- `alwaysApply: true`: a regra entra em qualquer conversa deste projeto.
- `globs`: a regra entra quando os arquivos correspondentes estão em contexto.
- `alwaysApply: false` com `globs`: regra específica, não global.

Não coloque senhas, tokens ou dados pessoais nas regras.

## Arquivos e quando se aplicam

| Arquivo | Quando vale | Para que serve |
| --- | --- | --- |
| `00-contexto-nf.mdc` | Sempre | Mapa do projeto, tecnologias confirmadas e o que ainda não está implementado |
| `01-fluxo-de-trabalho.mdc` | Sempre | Como o agente deve ler, planejar, alterar, perguntar e entregar |
| `02-organizacao-codigo-e-comentarios.mdc` | Sempre | Onde criar arquivos, como nomear e como comentar decisões complexas |
| `10-php-e-estrutura.mdc` | Arquivos PHP em `nf_blog/` | Páginas procedurais, configuração central e templates |
| `20-seguranca-e-permissoes.mdc` | PHP do blog e `.htaccess` | Login, papéis, CSRF, uploads e proteções do Apache |
| `30-mysql-e-dados.mdc` | PHP e SQL | Modelo das tabelas, consultas, MCP local e migrações |
| `40-interface-css-javascript.mdc` | CSS, JS, HTML e PHP | Identidade visual, grade, scripts e acessibilidade |
| `50-integracao-institucional-api.mdc` | API, `site_principal/` e `index.html` da raiz | Contrato JSON e vitrine do site institucional |
| `60-validacao.mdc` | Sempre | Verificações proporcionais, sem inventar comandos de teste |
| `70-aluguel-contexto.mdc` | Sempre | Mapa curto do aluguel, banco `nf_aluguel` e ordem de aplicar migração |
| `71-aluguel-organizacao.mdc` | Arquivos em `nf_aluguel/` | Pastas, nomes e o que não copiar do blog |
| `72-aluguel-php-javascript.mdc` | PHP, CSS e JS do aluguel | PHP procedural, mysqli e JavaScript puro |
| `73-aluguel-seguranca.mdc` | PHP e `.htaccess` do aluguel | Papéis, CSRF, uploads e pastas bloqueadas |
| `74-aluguel-banco.mdc` | PHP e SQL do aluguel | Migrações em `nf_aluguel`, integridade e verificação |
| `75-aluguel-validacao.mdc` | Arquivos em `nf_aluguel/` | Conferências proporcionais do módulo |

Há overlap intencional pequeno: o contexto descreve o mapa; as regras específicas dizem como alterar aquele domínio. Em caso de dúvida, o código atual prevalece sobre o texto da regra.

## Como o Cursor usa isso

1. Regras com `alwaysApply: true` entram no contexto de qualquer tarefa.
2. Regras com `globs` entram quando você trabalha em arquivos daquele padrão.
3. Você também pode mencionar uma regra na conversa se quiser reforçá-la.

O agente deve seguir as regras, mas continua obrigado a ler os arquivos reais antes de editar.

## Como manter as regras atualizadas

Atualize a regra pertinente **na mesma tarefa** em que o fato mudar. Exemplos:

- Nova pasta, página ou helper: ajuste `00` e `02`.
- Mudança de autenticação, CSRF ou upload: ajuste `20`.
- Nova tabela, coluna ou migração: ajuste `30` e o SQL inicial correspondente.
- Novo contrato da API ou do institucional: ajuste `50`.
- Novo comando real de verificação: ajuste `60`. Não invente `npm test` ou PHPUnit se o projeto não tiver isso.

Ao editar uma regra:

1. Confirme o comportamento no código, não só na memória da conversa.
2. Separe o que já existe, o que é convenção futura e o que ainda é dúvida.
3. Evite copiar trechos longos de código; cite o caminho do arquivo.
4. Não contradiga outra regra; se duas falarem do mesmo assunto, unifique.
5. Mantenha cada `.mdc` abaixo de 500 linhas e com um assunto claro.
6. Atualize a data deste guia se a organização das regras mudar.

## O que estas regras não fazem

- Não alteram PHP, CSS, JavaScript, banco ou servidor por si sós.
- Não comprovam que o ambiente local está configurado.
- Não registram resultados antigos de checklist como teste da cópia atual.

Para executar o projeto, use Apache/XAMPP com MySQL/MariaDB. O blog usa o banco `nf_blog` e `NF_ROOT_URL` apontando para a pasta pública real (o fallback do código é `http://localhost/blog/`). O aluguel usa o banco `nf_aluguel` e o fallback `http://localhost/aluguel/`. Live Server não executa PHP.
