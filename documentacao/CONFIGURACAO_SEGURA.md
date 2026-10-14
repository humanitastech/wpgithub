# Configuração segura — 1.0.1

Nenhum endpoint, origem, token ou limite operacional é presumido pelo runtime. A implantação escolhe os valores em ambiente ou constantes do wp-config.php, fora do Git e do ZIP. Sem política válida, a integração falha de forma fechada. Não copiar credenciais para testes, mensagens ou opções do WordPress.

## Parâmetros já existentes
| Ambiente/constante | Opção administrativa de fallback |
|---|---|
| HTGP_GITHUB_API_BASE_URL | api_base_url |
| HTGP_GITHUB_API_VERSION | api_version |
| HTGP_HTTP_USER_AGENT | user_agent |
| HTGP_HTTP_TIMEOUT_SECONDS | timeout_seconds |
| HTGP_RESPONSE_SIZE_BYTES | response_size |
| HTGP_CACHE_TTL_SECONDS | cache_ttl_seconds |
| HTGP_API_PAGE_SIZE | api_page_size |
| HTGP_MAX_OWNER_LENGTH | max_owner_length |
| HTGP_MAX_INCLUDE_LENGTH | max_include_length |
| HTGP_MAX_REPOSITORY_NAME_LENGTH | max_repo_length |

Precedência: variável de ambiente; constante se a variável não existir; opção se o valor resultante estiver vazio. O token e a política abaixo não têm fallback no banco. Variáveis numéricas são inteiros positivos em representação decimal canônica.

## Política obrigatória de ambiente na atualização
| Nome | Finalidade |
|---|---|
| HTGP_PUBLIC_LINK_ORIGINS | Origens HTTPS permitidas nos links públicos, separadas por vírgula |
| HTGP_PROFILE_ORIGINS | Origens HTTPS permitidas para o perfil configurado |
| HTGP_FAILURE_TTL_SECONDS | Espera mínima após falha externa |
| HTGP_MAX_BACKOFF_SECONDS | Teto de espera aplicado a Retry-After |
| HTGP_REQUEST_BUDGET_SECONDS | Tempo agregado disponível por cliente no pedido PHP |
| HTGP_MAX_REQUESTS | Quantidade máxima de chamadas HTTP por cliente no pedido PHP |
| HTGP_MAX_TIMEOUT_SECONDS | Teto do timeout administrativo |
| HTGP_MAX_RESPONSE_SIZE | Teto do tamanho de resposta administrativo |
| HTGP_MAX_API_PAGE_SIZE | Teto da quantidade de repositórios |

As origens devem ser canônicas: esquema https, host em minúsculas, porta explícita somente se necessária, sem caminho, barra final, query, fragmento ou credenciais. A lista deve conter somente serviços que o operador aprovou. O endpoint pode conter um caminho de API empresarial, mas não query/fragmento. HTTPS público não significa destino autorizado a receber segredo.

HTGP_GITHUB_TOKEN é opcional e deve ter o mínimo privilégio. Se usado, HTGP_GITHUB_TOKEN_ORIGIN é obrigatório e deve coincidir exatamente com a origem do endpoint. A opção administrativa não pode trocar a origem da credencial. Para dados públicos, preferir operação sem token quando a quota permitir.

O operador escolhe os limites após avaliar capacidade e latência: timeout <= MAX_TIMEOUT_SECONDS; tamanho <= MAX_RESPONSE_SIZE; paginação <= MAX_API_PAGE_SIZE; FAILURE_TTL_SECONDS <= MAX_BACKOFF_SECONDS. Não existem valores operacionais padrão. Retry-After numérico ou data HTTP é respeitado até o teto aprovado. O tempo total é um orçamento das chamadas externas, não garantia do tempo total de CPU/WordPress.

## Migração e operação
Antes de instalar 1.0.1, fornecer todos os parâmetros novos. Manter os parâmetros existentes e a configuração administrativa do perfil/filtros. Validar em homologação: falta de parâmetro, origem incompatível, consulta normal, erro de API, cache e usuários sem manage_options. A versão anterior continua no histórico Git para rollback; não manter pacotes antigos públicos na hospedagem.

O cache muda de namespace com endpoint, configuração, versão ou contexto da credencial. A credencial é representada somente por HMAC com salt WordPress, nunca por texto em chave/cache. Caches antigos expiram normalmente. Só campos públicos usados no template são persistidos.

A trava compartilhada usa opção não autoload com expiração e exclusão condicional pelo valor completo. Pedidos concorrentes não aguardam: recebem indisponibilidade temporária quando não há cache. Falhas de transporte/status/esquema acionam espera global para o mesmo contexto, abrangendo listagem e releases. Releases 404 têm cache negativo normal. Planejar limpeza operacional de locks abandonados de contextos aposentados; a própria consulta recupera locks vencidos no contexto atual.

O plugin emite htgp_security_event com códigos sanitizados para falhas, rejeição de linhas e validação de configuração. Um consumidor operacional ainda deve registrar data, contexto, resultado e retenção apropriados; o hook não constitui sozinho uma trilha persistente.

A apresentação continua com HTML escapado e CSS do plugin. Não existe formulário público, upload ou escrita no GitHub.
