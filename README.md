# HumanitasTech GitHub for WordPress

GitHub integration for WordPress criada pela HumanitasTech para apresentar repositórios públicos. A versão `1.1.0` agrupa projetos por tópicos e preserva as proteções de cache, limites de consulta e vínculo de credenciais à origem autorizada.

## Estrutura

- `codigo/htgithubforwp/`: código instalável do plugin;
- `documentacao/`: escopo, configuração segura, modelo de ameaças, matriz ENS e roadmap;
- `dist/`: pacote instalável da versão vigente.

O pacote oficial é gerado exclusivamente por `build-package.ps1`. O slug técnico é imutável: `htgithubforwp`.

## Segurança

Credenciais e política de segurança vêm exclusivamente do ambiente/constantes. Parâmetros técnicos existentes admitem opções administrativas de fallback. O plugin inclui CSS visual e falha de forma fechada sem configuração válida. **A atualização exige novos parâmetros de ambiente**, descritos em `documentacao/CONFIGURACAO_SEGURA.md`.

Validação offline: `php tests/security.php`. Evidências e limitações: `documentacao/VALIDACAO_1.0.1.md`. O teste usa transporte e APIs WordPress simulados; homologação em uma instalação real continua necessária.

O alinhamento documentado ao ENS não equivale à certificação do sistema completo; essa avaliação depende também da infraestrutura, operação, governança e controles do ambiente WordPress.

## Licença

GPL-2.0-or-later.

## Agrupamento por tópicos (1.1.0)

O shortcode existente `[ht_github_projects]` agrupa automaticamente os projetos públicos exibidos pelos tópicos `plugin`, `skill`, `theme`, `app`, `library` e `docs`. Os títulos são Plugins, Skills, Temas, Aplicações, Bibliotecas e Documentação, nessa ordem. A ordenação configurada dos projetos é mantida dentro de cada grupo.

Se nenhum projeto exibido possui uma dessas classificações, a grade permanece como antes, sem títulos de grupos. Se há projetos classificados, os demais aparecem em Geral, ao final. Tópicos de assuntos (como `php`, `codex` e `wordpress`) e `ht-showcase` não definem grupos nem restringem a exibição. A classificação considera somente os projetos que passam pelos filtros existentes e pelo limite configurado.

Use um tópico de classificação por repositório. Se houver mais de um, prevalece o primeiro na ordem acima, sem duplicar o projeto. Não há mudança de visibilidade dos repositórios. O cache de listagens anterior é separado do novo formato para buscar os tópicos na atualização.
