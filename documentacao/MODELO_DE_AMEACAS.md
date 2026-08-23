# Modelo de ameaças do MVP 0.2.0

## Ativos

- integridade do WordPress;
- credencial opcional do GitHub;
- disponibilidade do site;
- integridade dos dados externos exibidos;
- rastreabilidade das falhas de integração.

## Fronteiras de confiança

1. Administrador autenticado → configurações do WordPress.
2. WordPress → ambiente de execução e segredos.
3. WordPress → serviço externo HTTPS.
4. Resposta externa → HTML público.

## Ameaças e controles

| Ameaça | Controle |
|---|---|
| Injeção em shortcode | lista positiva, tamanho máximo e rejeição de caracteres inesperados |
| XSS por resposta externa | escape contextual de texto, atributos e URLs |
| SSRF | configuração administrativa externa, HTTPS obrigatório, `wp_http_validate_url`, `wp_safe_remote_get`, redirecionamento desativado |
| Exposição de token | segredo somente no ambiente, uso apenas em cabeçalho no servidor, mensagens genéricas |
| CSRF administrativo | Settings API do WordPress com nonce e verificação de capacidade |
| Escalada de privilégio | `manage_options` e falha fechada |
| Denegação por resposta grande | timeout e tamanho máximo definidos pelo ambiente |
| Uso excessivo da API | cache com duração definida pelo ambiente |
| Dados privados | descarte explícito de repositórios marcados como privados |
| Resposta malformada | código HTTP, JSON e estrutura mínima verificados |
| Vazamento por logs | eventos sem URL, token, resposta ou identificadores de usuário |

## Risco residual

- comprometimento de uma conta administrativa ou do ambiente permite alterar a configuração;
- a disponibilidade continua dependente do WordPress, hospedagem e GitHub;
- a segurança do sistema completo exige controles de infraestrutura, operação, backup, monitorização e auditoria fora do plugin.

