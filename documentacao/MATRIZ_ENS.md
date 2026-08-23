# Matriz de alinhamento ENS

| Referência | Evidência no MVP 0.2.0 |
|---|---|
| `mp.sw.1.1` Separação | fonte e documentação permanecem no ambiente de desenvolvimento; o pacote contém somente runtime |
| R1 Mínimo privilégio | token opcional externo e recomendação de escopo somente leitura; administração limitada a `manage_options` |
| R2 Desenvolvimento seguro | modelo de ameaças, validação positiva, escape contextual, inspeção de fonte e validação estática |
| R3 Segurança desde o desenho | falha fechada, proteção de segredo, eventos de segurança, limites de requisição e ausência de execução dinâmica |
| R4 Dados de teste | testes não requerem dados reais ou repositórios privados |
| Proteção de aplicação web | nonce via Settings API, SSRF reduzido, XSS reduzido e ausência de SQL próprio |
| Trazabilidade | `htgp_security_event` emite códigos de evento sem conteúdo sensível |
| Conservação e integridade | pacote versionado e hash SHA-256 registrado na distribuição |

Esta matriz é evidência de alinhamento técnico, não certificação de conformidade do sistema completo.
