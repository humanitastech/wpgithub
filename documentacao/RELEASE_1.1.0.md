# HumanitasTech GitHub for WordPress 1.1.0

O shortcode `[ht_github_projects]` agora organiza os repositórios públicos pelos tópicos `plugin`, `skill`, `theme`, `app`, `library` e `docs`.

- Sem classificações reconhecidas, mantém a grade sem grupos.
- Quando há projetos classificados, os demais aparecem em Geral.
- Preserva os filtros existentes e a ordem dos projetos dentro de cada grupo.
- Se houver vários tópicos de classificação, usa a prioridade documentada no README sem duplicar o projeto.

## Atualização

Instale `htgithubforwp.zip` substituindo o plugin existente. O slug permanece `htgithubforwp` e o shortcode não muda. A atualização desde 1.0.0 exige configurar os parâmetros de ambiente introduzidos em 1.0.1, descritos em `documentacao/CONFIGURACAO_SEGURA.md`. Sem política completa, o cliente falha fechado. A página inicial com cards próprios do tema não passa automaticamente a usar o shortcode.

## Validação

58 asserções offline passaram, quatro PHP sem erros de sintaxe e seis arquivos do ZIP conferidos byte a byte contra a fonte. Auditoria local da versão 1.1.0 sem vulnerabilidade de código confirmada; homologação real do shortcode, runtime/controles operacionais e documentação ENS continuam pendentes. Os testes simulam APIs WordPress, HTTP e SQL; não representam certificação ou aprovação integral da produção.

Relatório: `audit-results/wpgithub-1.1.0-2026-10-30/report.md`.
