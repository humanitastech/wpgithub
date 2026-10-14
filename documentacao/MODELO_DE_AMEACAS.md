# Modelo de ameaças — 1.0.1

Ativos: token opcional, integridade do WordPress/portfólio, quota externa, workers PHP, pacote e rastreabilidade operacional.

| Fronteira | Ameaça | Controle |
|---|---|---|
| Administrador → opções | CSRF/alteração não autorizada | Settings API e manage_options; tipos escalares |
| Opção de destino → token de ambiente | Exfiltração a origem arbitrária | Token vinculado à origem aprovada fora do banco; falha fechada |
| Visitante → renderização/API | Repetição de consultas, estouro de quota e latência | Cache, recuo global, trava, quantidade/tempo agregados e tetos do ambiente |
| API → HTML/cache | Dados privados, tipos hostis, links enganosos/XSS | private estritamente false, esquema e listas de origens; escape contextual |
| Mudança de configuração → cache | Reuso de dados de outro contexto | Fingerprint com configuração, versão e HMAC do contexto da credencial |
| Distribuição → WordPress | Alteração de pacote | ZIP limitado ao runtime, hash publicado e revisão de fonte |

Riscos residuais: operador do ambiente pode autorizar origens maliciosas; comprometimento completo do administrador/servidor excede a defesa deste plugin; transporte/DNS dependem da versão e filtros WordPress; locks exigem banco WordPress consistente. O recuo fornece mensagem temporária, sem stale-while-revalidate ou sincronização em background. Cache de página, proteção de tráfego, MFA, atualização, backup e logs são controles da instalação. Testes offline simulam interleaving, não medem concorrência real de banco.
