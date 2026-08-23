# MVP revisado 0.2.0

## Estado

Implementado, validado sintaticamente e empacotado. Não deve ser implantado até que a configuração segura de ambiente seja definida e revisada.

## Objetivo

Exibir repositórios públicos de uma conta GitHub configurada pela administração, sem duplicar dados no WordPress e sem incorporar configuração operacional, credenciais, conteúdo editorial ou apresentação visual no código.

## Escopo

- configuração externa obrigatória para endpoint, versão de API, timeout, tamanho de resposta, cache e limites;
- token opcional exclusivamente no ambiente;
- configuração administrativa sem valores padrão;
- shortcode `[ht_github_projects]` com curadoria opcional por `include`;
- uma consulta segura à API e cache segregado;
- exclusão de repositórios privados;
- forks e arquivados controlados pela administração;
- HTML semântico sem CSS ou identidade visual;
- validação positiva, escape contextual, nonce e capacidade administrativa;
- falha fechada e mensagens públicas genéricas;
- eventos de segurança sem conteúdo sensível.

## Critérios de aceite

1. Nenhuma consulta é realizada se faltar configuração obrigatória.
2. Nenhum segredo é armazenado no banco, enviado ao navegador ou incluído em logs.
3. O destino deve ser HTTPS público e não pode conter credenciais.
4. Redirecionamentos externos são recusados.
5. Timeout, tamanho de resposta, cache e paginação vêm do ambiente.
6. Toda entrada é limitada e validada por lista positiva.
7. Toda saída é escapada conforme o contexto.
8. Somente administradores com `manage_options` alteram a configuração.
9. O pacote de runtime não contém documentação de desenvolvimento, testes ou dados reais.
10. O tema controla integralmente a apresentação.

## Fora do MVP

- certificação ENS do sistema completo;
- dados privados;
- escrita no GitHub;
- execução de código proveniente de repositórios;
- README, releases e contribuidores;
- bloco Gutenberg;
- CSS ou temas visuais;
- tradução automática;
- webhooks.
