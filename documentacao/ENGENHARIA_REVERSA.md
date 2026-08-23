# Engenharia reversa funcional

## Princípio

O projeto observa comportamentos públicos e documentação dos plugins de referência. O código do MVP é uma implementação original da HumanitasTech; nenhum trecho foi copiado.

## Matriz de referência

| Referência | Ideia aproveitada | Limitação evitada no MVP |
|---|---|---|
| Embed Repo for GitHub | Descoberta automática de todos os repositórios e grade responsiva | Dependências comerciais externas e excesso de controles puramente visuais |
| Repo Showcase | Configuração central por usuário, shortcode simples e paginação como direção futura | Projeto desatualizado, token obrigatório e ausência de curadoria |
| WP Plugin Info Card | Cartões ricos, estatísticas, cache e seleção de repositórios | Necessidade de cadastrar cada cartão e múltiplas chamadas por repositório |

## Diferencial HumanitasTech

- uma única chamada para descobrir até 100 repositórios;
- cache no servidor com duração configurável;
- modo automático ou lista curada;
- identidade visual HumanitasTech;
- token opcional e somente para elevar limites;
- saída segura com escape de HTML e links externos protegidos;
- arquitetura curta, auditável e independente de construtores de página.

## Decisões do MVP

- Shortcode antes do bloco Gutenberg para reduzir o tempo até validação.
- REST API do GitHub consumida no servidor para não expor token no navegador.
- Repositórios privados, forks e arquivados ficam ocultos por padrão.
- Estatísticas limitadas ao retorno da listagem; releases e contribuidores ficam para fases posteriores, pois aumentam o número de chamadas.

