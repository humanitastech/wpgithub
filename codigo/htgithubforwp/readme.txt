=== HumanitasTech GitHub for WordPress ===
Contributors: humanitastech
Tags: github, repositories, portfolio, projects, shortcode
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

GitHub integration for WordPress, com exibição responsiva, cache seguro e curadoria opcional de repositórios públicos.

== Description ==

MVP seguro da HumanitasTech para transformar repositórios públicos em um portfólio legível. Não inclui configuração operacional, credenciais ou identidade visual no código.

Shortcode: `[ht_github_projects]`

O filtro opcional `include` recebe nomes de repositórios separados por vírgula.

== External services ==

Este plugin consulta exclusivamente o serviço externo HTTPS definido pelo ambiente de execução. A organização responsável pela implantação deve documentar o destino, os termos aplicáveis e a base jurídica correspondente.

== Changelog ==

= 1.1.0 =
* Agrupamento automático pelos tópicos plugin, skill, theme, app, library e docs.
* Grupo Geral quando há projetos classificados e não classificados.
* Sem classificações reconhecidas, preserva a grade sem grupos.

= 1.0.1 =
* Cache segregado por configuração, origem e contexto da credencial.
* Recuo compartilhado em falhas, exclusão mútua e orçamento agregado de consultas.
* Token vinculado à origem aprovada no ambiente; esquema público e origens de links validados.
* Migração exige novos parâmetros de segurança de ambiente; consulte CONFIGURACAO_SEGURA.md.

= 0.1.0 =
* MVP: grade automática, curadoria, estatísticas, cache, shortcode e configurações.

= 0.2.0 =
* Reescrita com configuração externa obrigatória, falha fechada, token fora do banco, requisições seguras, validação positiva e saída sem estilo incorporado.

= 1.0.0 =
* Primeira versão estável do MVP seguro, com documentação operacional e alinhamento às orientações aplicáveis do ENS.
