# Validação de segurança — 1.0.1
Data: 15/10/2026, Europe/Madrid. Base: 2d03222d7201653e3e00ceb6a8656041fdf349b6 (DEV, 1.0.0).
Autor: Voxiferum, para HumaitasTech, com apoio de Codex.

| Achado anterior | Antes | Depois/validação |
|---|---|---|
| HT-01 cache | Endpoint/credencial omitidos | Configuração completa e contexto HMAC no fingerprint; troca de origem/token exige nova busca |
| HT-02 disponibilidade | Falha repetia consultas/N+1 sem orçamento | Recuo global, Retry-After limitado, trava expiráveis com exclusão condicional, orçamento agregado; casos 429/503/timeout e disputa simulada |
| HT-03 token | Qualquer HTTPS configurado recebia token | Origem aprovada no ambiente; incompatibilidade falha antes do HTTP; token nunca nas opções |
| HT-04 resposta | private ausente aceito/tipos permissivos | private estritamente false, coleção limitada, tipos e origens; campos desconhecidos descartados |

Os parâmetros operacionais novos não têm defaults: migração em CONFIGURACAO_SEGURA.md. Literais estruturais de API/WordPress e metadados de versão permanecem necessários; não são configuração operacional. Constantes/ambiente prevalecem sobre opções conforme documentado.

39 asserções offline passaram em PHP CLI 8.2.12, com rede e APIs WordPress simuladas. Sintaxe PHP do runtime verificada. O teste não prova transporte, DNS, nonce ou concorrência real no banco. O relatório inicial e sua reprodução permanecem como evidência histórica; repro-local.php refere-se à base 1.0.0, não deve ser executado como teste da nova versão.

Apenas código e documentação foram corrigidos. DG-02 (governança), logs persistentes, versões reais, restauração e testes de homologação continuam pendentes e não podem ser solucionados somente por edição do plugin. Não houve scanner/exploração remota. Token sintético não representa segredo de usuário.

Método: skill ht-web-security-audit-ens e fluxo de validação de correções autorizadas. Documentação consultada em 15/10/2026: https://developer.wordpress.org/reference/functions/add_option/ e https://developer.wordpress.org/reference/functions/wp_remote_retrieve_header/. Sem nova conclusão normativa; ENS continua voluntário.
