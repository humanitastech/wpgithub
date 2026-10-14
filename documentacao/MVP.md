# MVP — 1.0.1

Plugin WordPress para apresentar repositórios públicos e última release. O shortcode ht_github_projects admite curadoria por include. Conta e filtros são administrativos. Parâmetros técnicos usam ambiente/constantes ou opções conforme CONFIGURACAO_SEGURA.md; segredos e política de segurança somente ambiente/constantes.

O runtime inclui CSS visual. No cache frio faz uma listagem e até N consultas de release, sujeitas à quantidade e tempo agregados aprovados. Falhas geram recuo compartilhado; HTTP concorrente usa trava compartilhada sem espera. Cache é segregado por configuração e contexto da credencial.

Aceite: saída escapada; privados/campo private ausente rejeitados; tipos de resposta validados; destinos de links autorizados; nenhum token para origem diferente; sem consulta diante de configuração incompleta; limites de chamadas e tempo; cache de sucesso/404; recuo em falhas e Retry-After; controle manage_options e Settings API. Testes offline: php tests/security.php. Integração com WordPress real e homologação permanecem necessárias.

Fora do escopo: dados privados, escrita GitHub, README executável, webhooks, pagamentos, upload, certificação ENS e governança da instalação completa.
