# Requisitos obrigatórios de segurança e configuração

Status: obrigatório para todas as versões posteriores ao protótipo 0.1.0.

## Regra de configuração

- Nenhuma credencial, conta, repositório, URL operacional, versão de API, limite, timeout, período de cache, texto editorial, cor, identidade visual ou comportamento de negócio pode ser fixado no código.
- Segredos não podem ser armazenados no código, no pacote distribuível, em logs, em JavaScript ou em opções comuns do WordPress. Devem ser fornecidos pelo ambiente de execução e possuir privilégio mínimo.
- A aplicação deve falhar de forma fechada quando faltar configuração obrigatória.
- Metadados comentados de autoria e licença do plugin são permitidos.
- Literais estruturais inevitáveis da linguagem, das APIs do WordPress e dos identificadores internos devem ser inventariados e justificados.

## Controles mínimos

1. Separação entre desenvolvimento, testes e produção; nenhuma ferramenta ou dado de desenvolvimento no ambiente produtivo.
2. Princípio de mínimo privilégio para WordPress, GitHub e servidor.
3. Validação por lista positiva, normalização e limites para toda entrada.
4. Escape contextual de toda saída HTML, atributo, URL e JavaScript.
5. Proteção CSRF com nonce e verificação de capacidade em toda operação administrativa.
6. Proibição de `eval`, execução dinâmica, inclusão de caminho controlado pelo usuário, desserialização insegura e construção dinâmica de SQL.
7. Requisições externas somente por HTTPS, com destino validado, timeout configurado, tamanho limitado e falha segura.
8. Segredos exclusivamente no ambiente; nunca retornados ao navegador nem incluídos em mensagens de erro.
9. Cache segregado por configuração, sem dados sensíveis, com invalidação controlada.
10. Logs sem credenciais ou dados pessoais, com eventos suficientes para auditoria e resposta a incidentes.
11. Dependências mínimas, inventariadas e verificadas; pacote reproduzível e hash de distribuição.
12. Revisão de código, análise estática, testes de segurança e registro de evidências antes da implantação.

## Referência ENS

- Real Decreto 311/2022: segurança integral, gestão de riscos, mínimo privilégio e proteção de aplicações.
- Medida `mp.sw.1`: desenvolvimento separado de produção.
- Reforço R1: mínimo privilégio.
- Reforço R2: metodologia de desenvolvimento seguro e inspeção do código-fonte.
- Reforço R3: segurança desde o desenho, proteção da informação e pistas de auditoria.
- Reforço R4: testes sem dados reais, salvo proteção equivalente.
- CCN-STIC 812: segurança em ambientes e aplicações web.
- CCN-STIC 460: recomendações de segurança para WordPress.

## Estado do protótipo 0.1.0

O protótipo 0.1.0 não cumpre integralmente estes requisitos porque contém valores operacionais padrão, textos editoriais, parâmetros técnicos e tokens visuais no código. Está classificado como **não implantável** até sua substituição por uma versão revisada.

## Conformidade

Seguir estas regras melhora o alinhamento com o ENS, mas não equivale a uma declaração ou certificação de conformidade. A conformidade depende também da categorização do sistema, análise de riscos, infraestrutura, operação, auditoria e demais medidas aplicáveis à organização.
