# HumanitasTech GitHub Projects

Plugin WordPress da HumanitasTech para apresentar repositórios públicos do GitHub. A versão `1.0.0` é o MVP estável e seguro do projeto.

## Estrutura

- `codigo/humanitastech-github-projects/`: código instalável do plugin;
- `documentacao/`: escopo, configuração segura, modelo de ameaças, matriz ENS e roadmap;
- `dist/`: pacote instalável da versão vigente.

## Segurança

O código não contém credenciais, endpoints operacionais, identidade visual ou valores de implantação. A configuração obrigatória é fornecida pelo ambiente e o plugin falha de forma fechada quando ela está ausente ou inválida. Consulte `documentacao/CONFIGURACAO_SEGURA.md` antes da implantação.

O alinhamento documentado ao ENS não equivale à certificação do sistema completo; essa avaliação depende também da infraestrutura, operação, governança e controles do ambiente WordPress.

## Licença

GPL-2.0-or-later.
