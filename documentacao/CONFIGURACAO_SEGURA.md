# Configuração segura do MVP 0.2.0

O plugin não contém valores operacionais padrão. Antes da ativação, o ambiente deve fornecer:

| Identificador | Finalidade | Obrigatório |
|---|---|---|
| `HTGP_GITHUB_API_BASE_URL` | Endpoint HTTPS autorizado | Sim |
| `HTGP_GITHUB_API_VERSION` | Versão contratada da API | Sim |
| `HTGP_HTTP_TIMEOUT_SECONDS` | Limite temporal da requisição | Sim |
| `HTGP_RESPONSE_SIZE_BYTES` | Tamanho máximo aceito da resposta | Sim |
| `HTGP_CACHE_TTL_SECONDS` | Período de cache | Sim |
| `HTGP_API_PAGE_SIZE` | Limite contratado por consulta | Sim |
| `HTGP_MAX_OWNER_LENGTH` | Comprimento máximo de identificador | Sim |
| `HTGP_MAX_INCLUDE_LENGTH` | Comprimento máximo do filtro curado | Sim |
| `HTGP_MAX_REPOSITORY_NAME_LENGTH` | Comprimento máximo de nome de repositório | Sim |
| `HTGP_GITHUB_TOKEN` | Credencial de leitura mínima | Não |

Os valores devem ser definidos pelo ambiente ou pelo `wp-config.php` fora do repositório e do pacote distribuível. O token nunca deve possuir permissão de escrita quando o plugin consumir apenas dados públicos.

Após ativar, um administrador autorizado deve configurar conta, quantidade, ordenação, direção, vínculo e inclusão de forks/arquivados em **Configurações → Integração GitHub**. Nenhum valor é assumido quando a configuração está incompleta.

O tema é integralmente responsável pela apresentação. O plugin entrega apenas HTML semântico e classes CSS estáveis, sem cores, dimensões ou identidade visual.
