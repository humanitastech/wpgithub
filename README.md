# HumanitasTech GitHub for WordPress

GitHub integration for WordPress criada pela HumanitasTech para apresentar repositórios públicos. A versão `1.0.1` corrige o isolamento de cache, limita consultas em falhas e vincula credenciais à origem autorizada.

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
