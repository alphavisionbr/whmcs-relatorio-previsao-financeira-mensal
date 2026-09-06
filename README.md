# Alphavision® WHMCS Relatório Previsão Financeira Mensal

Relatório customizado para WHMCS.

**Versão pública atual:** 1.0.0

[English version](README.en.md)

## Sobre

Valores previstos e já faturados a receber no mês selecionado, sem duplicidade.

Este projeto passou a ser distribuído individualmente para que cada relatório possa ser instalado, atualizado e versionado sem exigir o download dos demais relatórios da coleção Alphavision®.

## Recursos

- Faturas abertas prevalecem sobre previsões recorrentes para evitar dupla contagem.
- Faturas são exibidas em uma única linha pelo valor total.
- Serviços, domínios, addons e itens faturáveis já contemplados por faturas deixam de aparecer como previsão separada.
- Situações financeiras apresentadas: `Previsto`, `Faturada`, `Vencida` e `Pagamento pendente`.

## Instalação

O WHMCS carrega relatórios customizados a partir de `modules/reports/`.

Extraia o ZIP instalável da Release diretamente na raiz da instalação do WHMCS. O arquivo será instalado em:

```text
modules/reports/previsao_financeira_mensal.php
```

Depois, acesse a área de relatórios do WHMCS.

## Migração do pacote anterior

Se estiver migrando do pacote Alphavision WHMCS Reports, remova `modules/reports/previsao_mensal.php` antes de instalar este relatório para evitar duas versões visíveis no WHMCS.

## Origem desta versão pública

A versão pública **1.0.0** inicia um ciclo de versionamento independente para este relatório.

Derivado do relatório `previsao_mensal.php` do pacote interno Alphavision WHMCS Reports 1.0.5.

O número 1.0.0 não representa regressão funcional. Ele marca a separação do antigo pacote conjunto em projetos independentes.

## Compatibilidade

Este projeto utiliza o sistema nativo de relatórios customizados do WHMCS e destina-se a instalações self-hosted.

Mudanças na estrutura do banco de dados ou no mecanismo de relatórios do WHMCS devem ser validadas antes de atualizações em produção.

## Segurança

Não publique dados reais de clientes, domínios, faturas ou credenciais em Issues.

Consulte [SECURITY.md](SECURITY.md).

## Suporte

Consulte [SUPPORT.md](SUPPORT.md).

## Histórico

Consulte [CHANGELOG.md](CHANGELOG.md).

## Licença

Distribuído sob a **MIT License**.

SPDX: `MIT`

## Alphavision®

Desenvolvido e mantido pela **Alphavision®**.

**Projeto:** https://github.com/alphavisionbr/whmcs-relatorio-previsao-financeira-mensal  
**Site:** https://alphavision.com.br  
**Contato:** contato@alphavision.com.br

---

**Alphavision®**  
Web Platforms, Cloud Infrastructure & Digital Solutions
