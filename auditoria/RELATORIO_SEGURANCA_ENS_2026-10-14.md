# Revisão de segurança — wpgithub

**Atualização de 15/10/2026:** o relatório abaixo descreve a base 1.0.0. As correções autorizadas para 1.0.1 e os resultados antes/depois estão em `../documentacao/VALIDACAO_1.0.1.md`; os testes vigentes estão em `../tests/security.php`. A reprodução `repro-local.php` é histórica e aplica-se somente à base auditada. Lacunas de governança e homologação permanecem pendentes.

Data: 14/10/2026, Europe/Madrid. Autor: **Voxiferum**, para **HumaitasTech**, com apoio de **Codex**.

## Resultado e escopo

O plugin possui proteções úteis, mas a revisão identificou duas falhas confirmadas de comportamento: cache sem isolamento por endpoint e repetição de consultas durante falhas externas. Há também riscos condicionais na vinculação do token ao destino e na validação de respostas. Não foi demonstrada exploração remota, XSS, SQL injection ou acesso administrativo anônimo. Isso não constitui garantia de ausência de vulnerabilidades.

Repositório: https://github.com/humanitastech/wpgithub. Branch padrão examinada: `DEV`. Commit: `2d03222d7201653e3e00ceb6a8656041fdf349b6`, de 29/08/2026. Plugin: `htgithubforwp`, versão 1.0.0. Revisão integral dos quatro arquivos PHP, CSS, readme, script de empacotamento, oito documentos e ZIP versionado. Não houve comparação com uma versão anterior; os resultados descrevem o estado atual.

O usuário informou que é um projeto amador e que o ENS é adotado voluntariamente, sem categoria aprovada ou Declaração de Aplicabilidade. Não se conclui obrigação legal ou contratual. Esta é uma avaliação técnica e de lacunas documentais; não é certificação ENS ou ISO. Nenhuma correção de produção, publicação ou alteração de conta foi realizada.

## Critérios e categorização provisória

Referência ENS: Real Decreto 311/2022, texto consolidado BOE, atualização indicada de 06/11/2024, consultado em 14/10/2026. Referências complementares: ISO/IEC 27001:2022 com Amd 1:2024 e ISO/IEC 27002:2022, verificadas no catálogo oficial nesta data. Acesso apenas a resumos públicos ISO; cláusulas e equivalências não verificadas. Não foram utilizados textos pagos nem atribuídos números de controles ISO.

Hipótese de trabalho: portfólio público, indisponibilidade tolerável, sem serviços essenciais ou dados privados intencionalmente publicados. Dimensões propostas: disponibilidade BAIXO; autenticidade BAIXO; integridade BAIXO; confidencialidade MEDIO se um token permitir leitura de ativos privados ou operações relevantes; rastreabilidade BAIXO. Nesse cenário de segredo com impacto relevante, categoria proposta MEDIA; sem token ou com credencial estritamente limitada a dados públicos e consequências limitadas, candidata BÁSICA. A hipótese de confidencialidade não decorre apenas da existência de um segredo: depende do impacto do seu uso indevido. O responsável pelo projeto deve validar ativos, permissões e consequências antes de aprovar qualquer categoria. O sistema WordPress completo pode exigir outros níveis.

Selecionar reforços e aprovar aplicabilidade depende dessa validação e da análise de riscos. Como referência condicional, `mp.s.2` exige R1 ou R2 em BÁSICA/MEDIA; em ALTA, R2 e R3. `mp.sw.1` com R1–R4 é aplicável em MEDIA/ALTA; não é obrigatório em BÁSICA. A separação de pastas no repositório não demonstra separação real entre ambientes. Os controles organizativos permanecem sem evidência de aprovação.

## Componentes, ativos e ameaças

| Componente/entrada | Ativos e fronteira | Capacidade do atacante e proteção observada |
|---|---|---|
| Página com `[ht_github_projects]` | Visitante → PHP → API; workers PHP e quota externa | Visitante anônimo pode repetir GET; cache de sucesso reduz consultas, sem recuo em erros |
| `options.php`/Integração GitHub | Administrador → `htgp_settings` no banco | Alteração requer `manage_options`; Settings API gera nonce; autorização e CSRF completos dependem do WordPress |
| Ambiente/`wp-config.php` | Token → cabeçalho HTTP | Administrador do ambiente controla segredo; o banco pode controlar o destino quando não fixado externamente |
| Repositórios e releases externos | API → transient → HTML público | Autor de repositório controla nomes/descrições; escape HTML e URL reduz XSS; resposta externa continua não confiável |
| ZIP e instalação | Mantenedor → pacote → WordPress | Comprometimento da distribuição daria execução PHP; script foi lido, não executado |

Dados observados: nomes de conta/repositório, descrições, URLs, datas e estatísticas públicas; token opcional. Usuários e logs gerais são responsabilidade da instalação WordPress. Não há upload, webhook, pagamento, endpoint REST próprio, JavaScript, SQL próprio ou execução de conteúdo GitHub neste plugin. Essas superfícies são não aplicáveis ao código examinado, sem conclusão sobre outros plugins.

Hospedagem, CDN, WordPress instalado, PHP de produção, permissões reais do token, plugins adicionais, backup, logs e conteúdo efetivamente publicado não foram fornecidos. A URL no cabeçalho do plugin é metadado, não prova de instalação ativa. Não houve scanner remoto ou requisições exploratórias ao site/GitHub.

## Achados confirmados

### HT-01 — Cache reutilizado entre destinos distintos

Severidade **baixa**, confiança **alta**. Evidência: `codigo/htgithubforwp/includes/class-htgp-github-client.php:22–25` e `:77–80`. As chaves incluem conta, consulta/nome e versão do plugin, mas omitem endpoint e contexto de configuração. O cache é consultado antes de construir o destino.

Reprodução local: consultar conta sintética no endpoint A, mudar configuração para B, criar outro cliente e repetir consulta; nenhuma segunda chamada HTTP ocorre e os dados de A são retornados. Teste executado com funções WordPress simuladas. A mesma omissão existe no cache de releases, verificada pelo código.

Consequência: conteúdo da fonte anterior permanece publicado até expiração após uma troca legítima de destino. Não foi demonstrado vazamento de dados privados nem envenenamento por visitante anônimo. Probabilidade depende de troca de configuração; impacto usual é integridade e atualidade do portfólio.

Recomendação: incluir identidade normalizada do endpoint e versão do esquema nas chaves, invalidar na mudança de configuração e definir política para mudança de contexto de credenciais sem armazenar o token em nomes de chave. Requisito interno diretamente violado: item 9 de `REQUISITOS_SEGURANCA_ENS.md`. Relação ENS: gestão da configuração `op.exp.3`; proteção de caches `mp.s.2` R3 como complemento condicional para ALTA, sem afirmar que toda colisão local equivale a ataque normativo a cache.

### HT-02 — Falhas externas repetem trabalho síncrono em visitas públicas

Severidade **média**, confiança **alta** no comportamento; impacto de disponibilidade em produção **não medido**. Evidência: cliente `:41–55`, `:98–115`; plugin `:68–72`. Listagem só guarda sucesso; release guarda sucesso e 404. Timeout, 429 e 5xx não têm recuo, cache de erro ou exclusão de chamadas concorrentes.

Reprodução: resposta sintética 503 para duas consultas iguais; duas chamadas HTTP registradas tanto para listagem quanto para release. HTTP simulado, sem espera, carga ou rede real. Código mostra uma consulta de listagem mais N consultas sequenciais de release em cache frio. Para N=100 e timeout configurado de 5 s, o orçamento nominal de espera chega a 505 s, sujeito a limites do servidor e comportamento do transporte; não é medição de latência.

Consequência: visitantes anônimos podem amplificar consumo de workers e quota quando a API falha ou os caches expiram. Cache de página/CDN pode reduzir o risco, mas não foi verificado. Não se conclui que o site pode ser derrubado com um número específico de pedidos.

Recomendação: cache negativo curto respeitando `Retry-After`, recuo, trava por chave, orçamento agregado de tempo/chamadas e sincronização fora do caminho de renderização. Estabelecer limites superiores de timeout, tamanho e paginação; hoje os números apenas precisam ser positivos. Referência de risco: `mp.s.4` quando disponibilidade exigir essa medida; `op.pl.4` e configuração segura como critérios complementares.

## Riscos condicionais e hipóteses

### HT-03 — Token sem vinculação a uma origem aprovada

Severidade **média condicional**, confiança **alta** no envio e **não verificada** na explorabilidade do ambiente. Configuração `:49–56`, `:73`; plugin `:118`, `:145`; cliente `:27–41`, `:83–98`. Qualquer endpoint HTTPS público válido pode receber `Authorization: Bearer`. Validação de URL pública protege destinos internos, mas não comprova que um destino é autorizado a receber essa credencial.

Teste sintético confirmou o cabeçalho no destino `collector.example` usando transporte simulado. Esse domínio não foi resolvido ou contatado. Um administrador com `manage_options` pode alterar a opção do destino quando não há valor externo prevalente. Esse administrador já tem privilégios elevados em muitas instalações; não se relata escalada de privilégio ou ataque anônimo. Se ambiente/constante fixar o destino e o administrador não controlar o ambiente, esse caminho fica bloqueado.

Consequência potencial: exfiltração por erro de configuração ou conta administrativa comprometida de uma credencial mantida fora do banco. Gravidade real depende das permissões dessa credencial. Recomendação: lista de origens aprovada pelo operador fora das opções editáveis, associação explícita token/origem e ausência de Authorization em origens diferentes. Preservar suporte a endpoints empresariais aprovados. Testar origem permitida/negada sem rede. Relação: mínimo privilégio e configuração `op.exp.2`; `mp.sw.1` R1 quando aplicável.

### HT-04 — Esquema externo permissivo

Severidade **baixa condicional**, confiança **alta** na aceitação; divulgação real **não demonstrada**. Cliente `:61` usa `empty($repository['private'])`; campo ausente, null ou zero passa. Basta haver `name` e `html_url`, sem checar tipos. Teste local confirmou aceitação sem `private`. O endpoint oficial de listagem por usuário documenta dados públicos, portanto isso não demonstra vazamento de repositórios privados do GitHub.

API alternativa, proxy defeituoso ou retorno malformado pode contornar a intenção de aceitar somente dados comprovadamente públicos, provocar erros de tipo ou induzir links externos enganosos. Escape de URL não estabelece procedência; o escape existente refuta a hipótese simples de XSS por descrição HTML. Recomendação: exigir `private === false`, tipos e estrutura para os campos usados, limites de coleção e política de origem para links. Rejeitar linhas inválidas e registrar evento sanitizado. Relação: desenho seguro `mp.sw.1` R3 e proteção web `mp.s.2`, conforme aplicabilidade.

## Lacunas documentais e operacionais

**DG-01, prioridade média documental:** `MVP.md`, `MODELO_DE_AMEACAS.md`, `CONFIGURACAO_SEGURA.md` e `ENGENHARIA_REVERSA.md` descrevem 0.2.0, uma consulta, releases fora do escopo e apresentação integralmente no tema. O runtime 1.0.0 consulta releases, permite opções no banco para parâmetros técnicos e entrega CSS visual. Falta também `HTGP_HTTP_USER_AGENT` na tabela de configuração obrigatória. São divergências confirmadas de documentação, não vulnerabilidades independentes. Atualizar inventário, número de chamadas, precedência ambiente/constante/opção, instruções e critérios de aceite.

**DG-02, prioridade conforme adoção ENS:** não foram apresentados política aprovada, responsáveis, aplicabilidade, análise de impactos, inventário operacional, processo de atualização/incidentes, retenção ou restauração de backup. O modelo de ameaças ajuda, mas não prova governança implementada. Ausência no repositório significa não verificado, sem presumir inexistência fora dele.

**DG-03, prioridade média operacional:** `do_action('htgp_security_event', ...)` aparece somente em dois erros de listagem. Não há consumidor de logs neste repositório, cobertura equivalente de releases ou registro de alterações administrativas. Emitir um hook não prova trilha de auditoria persistente. Definir integração, campos mínimos, retenção e acesso sem gravar tokens ou corpos externos.

**DG-04, prioridade média de manutenção:** readme declara WordPress ≥6.5, PHP ≥7.4 e “Tested up to 7.1”, sem evidência de testes de compatibilidade no repositório. Não há dependências Composer/npm ou bibliotecas vendorizadas; WordPress/PHP reais não foram inventariados. Isso impede avaliar versões instaladas e advisories alcançáveis. Não se classifica a produção como vulnerável a partir desses requisitos mínimos.

## Matriz de evidências ENS/ISO

Matriz seletiva; não é Declaração de Aplicabilidade completa. Para todas as linhas, o complemento ISO é uma associação temática **provisória**, sem cláusula verificada, sem equivalência normativa e sem substituição do ENS. Evidências operacionais são necessárias além da fonte.

| ENS e reforço condicionado | Complemento ISO e diferença | Evidência | Estado | Lacuna/ação |
|---|---|---|---|---|
| `org.1–org.4` | 27001: gestão e responsabilidades; escopo de SGSI distinto | Documentação técnica sem aprovação demonstrada | Não verificado | Designar responsáveis e aprovar política/procedimentos/autorização |
| `op.pl.1`; R1 MEDIA/R2 ALTA | 27001: tratamento de risco; método ISO não valida categoria ENS | Modelo de ameaças, sem impactos aprovados | Parcialmente satisfeito | Validar riscos, dimensões e aplicabilidade |
| `op.exp.2`, `op.exp.3`; reforços conforme categoria | 27002: configuração; sem correspondência de cláusula | HTTPS, validação, HT-01/03 | Parcialmente satisfeito | Origem aprovada e invalidação de cache |
| `mp.s.2`; R1/R2 ou R2+R3 conforme categoria | 27002: segurança de aplicações; teste parcial não é auditoria completa | Escape, capacidade, Settings API, revisão e testes isolados | Parcialmente satisfeito | Testes integrados e tratamento de achados |
| `mp.sw.1` R1–R4 se MEDIA/ALTA | 27002: desenvolvimento seguro; não prova separação de produção | Fonte curta, dados sintéticos, documentação | Parcialmente satisfeito | Demonstrar ambientes e ciclo de desenvolvimento |
| `op.pl.4`; `mp.s.4` se D MEDIO/ALTO | 27002: disponibilidade; capacidade depende do ambiente | HT-02, timeout por chamada | Parcialmente satisfeito | Orçamento agregado e teste local de falhas concorrentes |
| `op.exp.8`; reforços segundo T | 27002: logs; hook não é registro implementado | Dois eventos sem consumidor | Não verificado | Integrar e demonstrar trilha persistente |
| `op.exp.4`, `op.exp.7` | 27001/27002: manutenção e incidentes; exigem evidência própria | DG-02/04 | Não verificado | Inventário, atualização e exercício de incidentes |
| `op.ext.1` se MEDIA/ALTA | 27002: fornecedores; catálogo ISO não valida acordo ENS | GitHub/API e hospedagem não documentada | Não verificado | Registrar responsabilidades e dependências |
| `mp.s.2.3`, verificação restrita do escape | 27002: proteção de aplicação; sem conclusão sobre todo o site | Textos/atributos/URLs escapados no template | Satisfeito no escopo verificado | Testar integração com resposta hostil e tema real |
| Requisito interno 9: cache segregado | 27002: tema de integridade, sem cláusula | HT-01 reproduzido | Não satisfeito | Segregar cache por origem |

## Verificação executada e limites

Ambiente de avaliação: Windows/PowerShell, Git, ripgrep, PHP CLI 8.2.12. Esse PHP serve apenas à reprodução, não recomenda versão de produção. Comandos: `git clone --depth 1`, `git rev-parse HEAD`, `git ls-files`, leitura dos arquivos, buscas `rg`, `php -l` nos quatro PHP, `php auditoria/repro-local.php`, SHA-256 e comparação ZIP por .NET. Nenhuma instalação de dependências ou execução do build.

Resultados: quatro verificações sintáticas passaram; cinco asserções locais passaram (envio simulado de token a endpoint customizado, reutilização de cache, resposta sem private, repetição de falha de listagem e de release). As asserções confirmam o comportamento problemático, não a segurança do sistema. O simulador de `wp_http_validate_url` apenas aceita HTTPS e não reproduz DNS, rede, bloqueios SSRF, nonce, usuários ou o transporte real WordPress; não é teste desses controles.

ZIP: SHA-256 `614e9bc2bf3b8705ffe77296f0f4f0cc98fad986fb47d29681aa4ba013a40a30`. Seis arquivos, todos equivalentes à fonte após normalizar CRLF/LF. A comparação binária inicialmente divergiu por finais de linha; não há divergência textual nesse conjunto. Não foi verificada assinatura/procedência externa nem histórico completo de segredos: clone raso cobre somente o snapshot. Não foram encontrados valores de credenciais reais nos arquivos lidos; isso não atesta histórico limpo.

Cobertura: frontend/template e backend/plugin revisados estaticamente; cliente API testado isoladamente; arquivo distribuído comparado; infraestrutura e governança não verificadas. Login/MFA/sessões, CSRF completo, TLS/CSP de produção, restauração, testes em PHP 7.4/WordPress 6.5–7.1 e varredura de advisories da instalação não executados. Não foi demonstrado SSRF contra rede interna: a implementação usa API segura do WordPress e bloqueia redirecionamentos.

## Plano de ação e aceite

| Prioridade | Ação e responsável sugerido | Dependência | Critério verificável |
|---|---|---|---|
| P1 técnica | Recuo, trava e orçamento de consultas — mantenedor PHP | Política de atualização/latência | Em 429/503/timeout, visitas simultâneas não geram uma chamada por visitante/repositório; conteúdo útil ou mensagem rápida dentro do orçamento |
| P1 condicional | Vincular token a origem — operador + mantenedor | Permissões e destinos aprovados | Token sintético ausente em destino negado; presente somente em origem aprovada; testar precedência da configuração |
| P2 técnica | Separar cache e invalidar — mantenedor | Identidade de origem | Troca de A para B provoca nova busca para listagem e release; sem segredo na chave/log |
| P2 técnica | Validar esquema — mantenedor | Contrato da API | Campo private ausente/null/true é rejeitado; tipos inesperados não causam fatal; links seguem política |
| P2 documental | Atualizar documentos/readme — mantenedor | Decisões de comportamento | Documentos 1.0.0 correspondem a releases, CSS, opções e variáveis realmente usadas |
| P2 operacional | Logs e compatibilidade — operador + mantenedor | WordPress local e inventário | Exercitar usuários sem capacidade, nonce inválido, resposta malformada, falhas e eventos sem segredo; registrar versões suportadas |
| P3 voluntária | Categoria, matriz completa, operação — responsável do projeto | Impactos e escopo aprovados | Aplicabilidade e riscos validados, evidência de backup/restauração, atualização e incidentes |

Não é necessário transformar um projeto amador em um SGSI para corrigir os riscos técnicos. Se houver futura exigência legal/contratual ou intenção de declarar conformidade, obter avaliação formal independente do sistema completo e procedimento adequado à categoria.

## Fontes utilizadas

Consultadas em 14/10/2026. Critérios normativos: [BOE — RD 311/2022 consolidado](https://www.boe.es/eli/es/rd/2022/05/03/311/con), [CCN — conformidade ENS](https://ens.ccn.cni.es/es/conformidad), [ISO/IEC 27001:2022](https://www.iso.org/standard/27001), [Amd 1:2024](https://www.iso.org/standard/88435.html), [ISO/IEC 27002:2022](https://www.iso.org/standard/75652.html). Resumos públicos ISO; sem acesso a cláusulas completas. A página do CCN não foi suficiente para validar edições específicas de guias: nenhum requisito foi extraído de CCN-STIC 460/812.

Documentação técnica oficial: [WordPress wp_safe_remote_get](https://developer.wordpress.org/reference/functions/wp_safe_remote_get/), [wp_http_validate_url](https://developer.wordpress.org/reference/functions/wp_http_validate_url/), [settings_fields](https://developer.wordpress.org/reference/functions/settings_fields/), [GitHub — endpoints de repositórios](https://docs.github.com/en/rest/repos/repos). São referências atuais de API, não comprovação do comportamento de uma instalação concreta.

Evidência primária: [snapshot do repositório](https://github.com/humanitastech/wpgithub/tree/2d03222d7201653e3e00ceb6a8656041fdf349b6), documentos e pacote nesse commit; declarações do usuário nesta conversa. Método: skill local `ht-web-security-audit-ens` e seus arquivos `standards.md`, `checks.md`, `web-review.md`, `bibliography.md`; bibliografia lida como contexto, sem executar skills ou scripts externos.
