# Auditoria wpgithub-1.1.0-2026-10-30

Auditoria em 30/10/2026 concluída no escopo local: nenhuma vulnerabilidade de código confirmada. 58 asserções passaram, quatro PHP sem erros sintáticos e seis arquivos do ZIP idênticos à fonte. Três pendências operacionais/documentais permanecem abertas. Não equivale a certificação ENS/ISO nem a aprovação integral da produção. Autor: Voxiferum, para HumaitasTech, com apoio de Codex.

Perfil: component | Revisão: a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0
Ambiente: Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes. | Árvore de trabalho: dirty

## Escopo

Plugin wpgithub 1.1.0: quatro PHP, CSS, testes, documentação operacional e seis arquivos do ZIP. Ênfase no delta de tópicos contra 1.0.1; reinspeção dos fluxos compartilhados HTTP/cache/admin. Sem testes ativos remotos nem alteração de produção.

## Modelo de ameaças

Visitante anônimo acessa shortcode e pode repetir consultas; administrador manage_options altera opções; ambiente controla política e token opcional; API externa fornece JSON não confiável. Ativos: token, quota GitHub, workers PHP, opções e integridade HTML. Fronteiras: navegador→WordPress, opções/ambiente→HTTP, JSON→validação→cache→grupos→HTML, fonte→ZIP→implantação. Atores plausíveis: visitante abusivo, dados externos hostis e administrador comprometido; controle do ambiente já implica confiança privilegiada.

## Limitações

- ENS usado como referência técnica; obrigação legal/contratual não demonstrada nesta sessão. Categoria e Declaração de Aplicabilidade aprovadas não fornecidas. Solicitação documental pendente ao responsável: categoria, SoA, riscos e responsáveis.
- Proposta provisória de impactos: disponibilidade, autenticidade, integridade e rastreabilidade BAIXO apenas sob hipótese de portfólio sem efeito em serviços críticos; confidencialidade BAIXO sem credencial privilegiada e pendente se houver token. Categoria do sistema não aprovada; responsável deve validar cada dimensão e selecionar reforços. Não se presume BÁSICA pelo tamanho do site.
- Os testes simulam WordPress, SQL, HTTP, escape e permissões; não validam nonce/sessões reais, DNS/TLS, object cache ou concorrência entre processos.
- Na etapa anterior da conversa, o painel indicou plugin 1.1.0 ativo e WordPress 6.9.10; não houve nova inspeção remota nesta auditoria. PHP/SAPI, patches do provedor e configurações de ambiente não verificados. Página inicial usa cards do tema e não comprova execução do shortcode.
- Testes definem HTGP_VERSION como 1.0.1: cobrem comportamento atual, mas não simulam integralmente o bootstrap e a migração 1.1.0. A versão do pacote foi verificada separadamente.
- Sem scanner remoto, fuzzing, carga, exploração, instalação de dependências ou correções. Nenhum código, pacote ou configuração foi alterado pela auditoria.
- Sem textos ISO integrais; associações ISO são temáticas, provisórias, com cláusulas não verificadas e sem equivalência normativa. Sem auditoria de todo o WordPress, infraestrutura, histórico completo de segredos ou terceiros.

## Módulos e trabalho pendente

| id | status | reason | remaining_work |
| --- | --- | --- | --- |
| identity | partial | Selecionado pela superfície real do componente e dependências. | Validar evidências integradas/operacionais ou organizativas; ver verificações e pendências. |
| inputs | complete | Selecionado pela superfície real do componente e dependências. | Nenhum teste local declarado pendente. |
| browser | complete | Selecionado pela superfície real do componente e dependências. | Nenhum teste local declarado pendente. |
| cms | partial | Selecionado pela superfície real do componente e dependências. | Validar evidências integradas/operacionais ou organizativas; ver verificações e pendências. |
| dependencies | partial | Selecionado pela superfície real do componente e dependências. | Validar evidências integradas/operacionais ou organizativas; ver verificações e pendências. |
| operations | partial | Selecionado pela superfície real do componente e dependências. | Validar evidências integradas/operacionais ou organizativas; ver verificações e pendências. |
| governance | partial | Selecionado pela superfície real do componente e dependências. | Validar evidências integradas/operacionais ou organizativas; ver verificações e pendências. |

## Cobertura

| id | module | title | status | rationale | evidence_ids |
| --- | --- | --- | --- | --- | --- |
| TOPIC-01 | inputs | Tópicos e limites de entrada | satisfied | Dados externos validados; privados rejeitados antes da classificação. | ['E-TOPIC', 'E-TEST'] |
| XSS-01 | browser | Saída contextual e classificação | satisfied | Grupos fixos e títulos/atributos escapados; teste de descrição hostil passa. | ['E-TOPIC', 'E-TEST'] |
| HTTP-01 | inputs | SSRF e destino da credencial | satisfied | Controles presentes e regressões locais passam; DNS/core reais fora da prova. | ['E-HTTP', 'E-TEST'] |
| CACHE-01 | operations | Cache, backoff e orçamento | satisfied | Controles de 1.0.1 preservados no delta; testes repetidos na fonte 1.1.0. | ['E-HTTP', 'E-TEST'] |
| ADMIN-01 | identity | Privilégio administrativo e CSRF | partial | Capacidade e Settings API presentes; nonce, sessão e opções.php reais não exercitados. | ['E-ADMIN', 'E-TEST'] |
| PACKAGE-01 | dependencies | Integridade e conteúdo do ZIP | satisfied | Pacote corresponde à fonte, sem material operacional secreto; hash local não é assinatura independente. | ['E-SNAPSHOT', 'E-LINT', 'E-ZIP'] |
| DEPLOY-01 | cms | Migração e aceitação do shortcode | not_verified | Ativação no painel não comprova parâmetros obrigatórios nem consulta pelo shortcode. | ['E-OPS'] |
| RUNTIME-01 | dependencies | Versões e manutenção de produção | not_verified | Sem PHP real/patches e inventário completo da instalação; não se atribuem CVEs por inferência. | ['E-OPS'] |
| OPS-01 | operations | Logs, recuperação e concorrência real | not_verified | Hooks não comprovam coleta operacional; não há evidência de restauração ou teste de concorrência real. | ['E-OPS'] |
| GOV-01 | governance | Categoria, SoA e política aprovada | not_verified | Documentação aprovada não fornecida; lacuna de evidência e não vulnerabilidade automática. | [] |

## Achados e revisão humana

```json
{
  "id": "OBS-01",
  "title": "Homologação da migração 1.0.0 → 1.1.0 pendente",
  "severity": "low",
  "verdict": "unconfirmed",
  "kind": "evidence_gap",
  "impact": "Se a política obrigatória estiver ausente, o shortcode retorna conteúdo indisponível; não há prova de ocorrência no servidor.",
  "likelihood": "Indeterminada por falta de evidência de implantação/gestão; sem exploração confirmada.",
  "confidence": "Alta quanto à lacuna da evidência disponível; não determina estado real do servidor.",
  "reproduction": "Inspeção local dos caminhos citados; sem teste remoto ou alteração. Se a política obrigatória estiver ausente, o shortcode retorna conteúdo indisponível; não há prova de ocorrência no servidor.",
  "recommendation": "Prioridade 1; operador WordPress: validar parâmetros documentados e testar shortcode em homologação, incluindo consulta inicial/cache e cenário Geral. Não enfraquecer a falha fechada.",
  "acceptance_criteria": "Configuração completa e shortcode real renderizando Plugins/Skills, com cache funcional e sem avisos; registrar resultado e versão do PHP.",
  "check_ids": [
    "DEPLOY-01"
  ],
  "evidence_ids": [
    "E-OPS"
  ],
  "review": {
    "status": "pending"
  },
  "remediation": "open"
}
```

```json
{
  "id": "OBS-02",
  "title": "Inventário de runtime e evidências operacionais ausentes",
  "severity": "informational",
  "verdict": "unconfirmed",
  "kind": "evidence_gap",
  "impact": "Não é possível avaliar manutenção do PHP/WordPress, persistência de eventos e recuperação; nenhum runtime vulnerável foi confirmado.",
  "likelihood": "Indeterminada por falta de evidência de implantação/gestão; sem exploração confirmada.",
  "confidence": "Alta quanto à lacuna da evidência disponível; não determina estado real do servidor.",
  "reproduction": "Inspeção local dos caminhos citados; sem teste remoto ou alteração. Não é possível avaliar manutenção do PHP/WordPress, persistência de eventos e recuperação; nenhum runtime vulnerável foi confirmado.",
  "recommendation": "Prioridade 2; responsável de hospedagem: fornecer versões/patches, política de atualização, destino de logs e evidência de restauração.",
  "acceptance_criteria": "Inventário com versões reais e análise de advisories alcançáveis, eventos observáveis sem segredos e restauração demonstrada.",
  "check_ids": [
    "RUNTIME-01",
    "OPS-01"
  ],
  "evidence_ids": [
    "E-OPS"
  ],
  "review": {
    "status": "pending"
  },
  "remediation": "open"
}
```

```json
{
  "id": "OBS-03",
  "title": "Categoria ENS e documentação organizativa não demonstradas",
  "severity": "informational",
  "verdict": "unconfirmed",
  "kind": "evidence_gap",
  "impact": "Impede selecionar controles/reforços e concluir conformidade; não demonstra falha explorável.",
  "likelihood": "Indeterminada por falta de evidência de implantação/gestão; sem exploração confirmada.",
  "confidence": "Alta quanto à lacuna da evidência disponível; não determina estado real do servidor.",
  "reproduction": "Inspeção local dos caminhos citados; sem teste remoto ou alteração. Impede selecionar controles/reforços e concluir conformidade; não demonstra falha explorável.",
  "recommendation": "Prioridade documental; responsável do sistema: validar impactos, aplicabilidade, categoria, SoA, riscos e política.",
  "acceptance_criteria": "Documentos aprovados com responsáveis, âmbito e justificativas; revisão normativa independente quando necessária.",
  "check_ids": [
    "GOV-01"
  ],
  "evidence_ids": [
    "E-SNAPSHOT"
  ],
  "review": {
    "status": "pending"
  },
  "remediation": "open"
}
```


## Evidências

```json
{
  "id": "E-SNAPSHOT",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "evidence/snapshot.json",
  "method": "SHA256 dos arquivos e git status/diff",
  "observation": "Árvore contém alterações 1.1.0 não commitadas; auditoria anterior preservada.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```

```json
{
  "id": "E-TEST",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "tests/security.php; evidence/regressoes.txt",
  "method": "php tests/security.php; exit 0",
  "observation": "58 PASS: isolamento de cache, origem/token, privados, erros, locks, orçamento, XSS, administração, tipos de tópicos e agrupamento.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```

```json
{
  "id": "E-LINT",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "evidence/sintaxe.txt",
  "method": "php -l nos quatro PHP",
  "observation": "Quatro arquivos sem erros; PHP CLI 8.2.12.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```

```json
{
  "id": "E-ZIP",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "evidence/pacote.txt; dist/htgithubforwp.zip",
  "method": "zipfile e SHA256",
  "observation": "Seis arquivos idênticos byte a byte à fonte; checksum confere; não inclui testes, .env ou wp-config.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```

```json
{
  "id": "E-TOPIC",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "codigo/htgithubforwp/includes/class-htgp-github-client.php:40-46; class-htgp-plugin.php:71-76,115-150",
  "method": "Rastreio estático JSON→validação→grupos→HTML",
  "observation": "Lista até 20 tópicos, strings até 50 caracteres, padrão restrito; apenas seis categorias; labels escapados; nenhuma interpolação de tópico em SQL/URL/HTML executável.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```

```json
{
  "id": "E-HTTP",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "codigo/htgithubforwp/includes/class-htgp-github-client.php:63-137; class-htgp-configuration.php:32-55,71-100",
  "method": "Rastreio estático e regressões sintéticas",
  "observation": "HTTP seguro, redirects zero, token preso à origem de ambiente, cache por contexto e versão, limite de resposta/tempo/chamadas, cooldown e lock SQL preparado.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```

```json
{
  "id": "E-ADMIN",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "codigo/htgithubforwp/includes/class-htgp-plugin.php:153-221,271-316",
  "method": "Revisão de hooks, callback e formulário",
  "observation": "manage_options no menu/página/callback; Settings API e settings_fields no formulário; sem rotas REST próprias, uploads, webhooks ou execução de comandos.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```

```json
{
  "id": "E-OPS",
  "revision": "a954cc9e9110d06355ee2ce1207f7da35c2ab5f9 + alterações locais 1.1.0",
  "environment": "Windows; PHP CLI 8.2.12; Python 3.13.2; WordPress/HTTP/SQL simulados nos testes.",
  "location": "documentacao/CONFIGURACAO_SEGURA.md:24-36; class-htgp-configuration.php:31-38; tests/security.php:3; readme.txt:5-6",
  "method": "Inventário local e comparação com evidência da etapa anterior",
  "observation": "1.1.0 exige sete parâmetros de política introduzidos em 1.0.1; se faltarem, cliente falha fechado. Sem evidência de configuração do servidor ou execução real do shortcode. Readme aceita PHP 7.4; PHP de produção desconhecido. Sem dependências npm/Composer vendorizadas; hooks de segurança sem consumidor persistente neste componente.",
  "limitations": "Restrito ao snapshot local; não prova comportamento/configuração de produção."
}
```


## Matriz normativa

```json
{
  "check_id": "TOPIC-01",
  "mappings": []
}
```

```json
{
  "check_id": "XSS-01",
  "mappings": []
}
```

```json
{
  "check_id": "HTTP-01",
  "mappings": [
    {
      "criterion": "op.exp.3.6",
      "source": "https://www.boe.es/eli/es/rd/2022/05/03/311/con",
      "rationale": "Associação técnica limitada ao requisito; aplicabilidade e reforços dependem da categoria/SoA, sem conclusão integral de conformidade.",
      "status": "verified"
    },
    {
      "criterion": "ISO/IEC 27001:2022 — gestão de riscos; cláusula não verificada",
      "source": "https://www.iso.org/standard/27001",
      "rationale": "Complemento temático de gestão, sem equivalência normativa.",
      "status": "provisional"
    }
  ]
}
```

```json
{
  "check_id": "CACHE-01",
  "mappings": []
}
```

```json
{
  "check_id": "ADMIN-01",
  "mappings": [
    {
      "criterion": "op.exp.3.6",
      "source": "https://www.boe.es/eli/es/rd/2022/05/03/311/con",
      "rationale": "Associação técnica limitada ao requisito; aplicabilidade e reforços dependem da categoria/SoA, sem conclusão integral de conformidade.",
      "status": "verified"
    },
    {
      "criterion": "ISO/IEC 27001:2022 — gestão de riscos; cláusula não verificada",
      "source": "https://www.iso.org/standard/27001",
      "rationale": "Complemento temático de gestão, sem equivalência normativa.",
      "status": "provisional"
    }
  ]
}
```

```json
{
  "check_id": "PACKAGE-01",
  "mappings": []
}
```

```json
{
  "check_id": "DEPLOY-01",
  "mappings": [
    {
      "criterion": "mp.sw.2",
      "source": "https://www.boe.es/eli/es/rd/2022/05/03/311/con",
      "rationale": "Associação técnica limitada ao requisito; aplicabilidade e reforços dependem da categoria/SoA, sem conclusão integral de conformidade.",
      "status": "verified"
    },
    {
      "criterion": "ISO/IEC 27001:2022 — gestão de riscos; cláusula não verificada",
      "source": "https://www.iso.org/standard/27001",
      "rationale": "Complemento temático de gestão, sem equivalência normativa.",
      "status": "provisional"
    }
  ]
}
```

```json
{
  "check_id": "RUNTIME-01",
  "mappings": [
    {
      "criterion": "op.exp.4",
      "source": "https://www.boe.es/eli/es/rd/2022/05/03/311/con",
      "rationale": "Associação técnica limitada ao requisito; aplicabilidade e reforços dependem da categoria/SoA, sem conclusão integral de conformidade.",
      "status": "verified"
    },
    {
      "criterion": "ISO/IEC 27001:2022 — gestão de riscos; cláusula não verificada",
      "source": "https://www.iso.org/standard/27001",
      "rationale": "Complemento temático de gestão, sem equivalência normativa.",
      "status": "provisional"
    }
  ]
}
```

```json
{
  "check_id": "OPS-01",
  "mappings": []
}
```

```json
{
  "check_id": "GOV-01",
  "mappings": [
    {
      "criterion": "org.1",
      "source": "https://www.boe.es/eli/es/rd/2022/05/03/311/con",
      "rationale": "Associação técnica limitada ao requisito; aplicabilidade e reforços dependem da categoria/SoA, sem conclusão integral de conformidade.",
      "status": "verified"
    },
    {
      "criterion": "ISO/IEC 27001:2022 — gestão de riscos; cláusula não verificada",
      "source": "https://www.iso.org/standard/27001",
      "rationale": "Complemento temático de gestão, sem equivalência normativa.",
      "status": "provisional"
    }
  ]
}
```


## Histórico

```json
{
  "at": "2026-10-30",
  "actor": "Codex",
  "type": "audit_started",
  "subject": "wpgithub-1.1.0-2026-10-30",
  "detail": "Novo snapshot 1.1.0; auditoria anterior preservada. Todos os checks foram reavaliados, sem transportar aprovação humana."
}
```

```json
{
  "at": "2026-10-30",
  "actor": "Codex",
  "type": "checkpoint",
  "subject": "technical-review",
  "detail": "Sete módulos examinados; regressões/lint/pacote verificados; sem vulnerabilidade confirmada. Módulos parciais aguardam evidência operacional/documental. Sem correções ou mudanças no WordPress."
}
```


## Fontes

```json
{
  "id": "ENS",
  "title": "RD 311/2022 consolidado",
  "url": "https://www.boe.es/eli/es/rd/2022/05/03/311/con",
  "edition": "Atualização BOE indicada: 06/11/2024",
  "consulted_at": "2026-10-30"
}
```

```json
{
  "id": "ISO",
  "title": "ISO/IEC 27001:2022",
  "url": "https://www.iso.org/standard/27001",
  "edition": "Edição 3; catálogo indica uma emenda; conteúdo da emenda não revalidado",
  "consulted_at": "2026-10-30"
}
```

```json
{
  "id": "WP-HTTP",
  "title": "wp_safe_remote_get",
  "url": "https://developer.wordpress.org/reference/functions/wp_safe_remote_get/",
  "edition": "Documentação oficial atual, não prova runtime instalado",
  "consulted_at": "2026-10-30"
}
```

```json
{
  "id": "WP-SETTINGS",
  "title": "register_setting",
  "url": "https://developer.wordpress.org/reference/functions/register_setting/",
  "edition": "Documentação oficial atual",
  "consulted_at": "2026-10-30"
}
```

```json
{
  "id": "SKILL",
  "title": "ht-web-security-audit-ens: instruções, registros e sete módulos",
  "location": "C:/Users/livia/.codex/skills/ht-web-security-audit-ens",
  "consulted_at": "2026-10-30"
}
```

