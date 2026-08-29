# Empacotamento e instalação

## Identidade técnica imutável

- Slug e diretório instalado: `htgithubforwp`
- Arquivo principal: `htgithubforwp.php`
- Pacote para instalação: `htgithubforwp.zip`
- Shortcode público: `[ht_github_projects]`
- Opção de configuração: `htgp_settings`

O número da versão pertence ao cabeçalho do plugin, às tags do Git e às notas de versão. Ele nunca deve ser incorporado ao slug, ao diretório instalado ou ao nome do ZIP usado pelo instalador do WordPress.

## Gerar o pacote

Execute na raiz do projeto:

```powershell
.\build-package.ps1
```

O script valida a estrutura e grava `dist\htgithubforwp.zip`. O arquivo principal fica na raiz do ZIP, evitando que o WordPress crie uma pasta adicional dentro do diretório do plugin.

## Instalar ou atualizar

No WordPress, acesse **Plugins → Adicionar plugin → Enviar plugin** e selecione `dist\htgithubforwp.zip`.

Uma instalação válida deve resultar exatamente em:

```text
wp-content/plugins/htgithubforwp/htgithubforwp.php
```

Não publique pacotes que gerem diretórios numerados, slugs legados ou níveis aninhados.
