# Diário de Pets

Sistema web para tutores registrarem o dia a dia dos seus pets: alimentação, banho, idas ao veterinário, medicação, custos e observações. Trabalho acadêmico desenvolvido para rodar localmente em ambiente XAMPP.

## Stack

- PHP 8+ puro (sem framework)
- MySQL 5.7+ / MariaDB
- HTML5 + CSS puro
- JavaScript Vanilla
- PDO com prepared statements

## Estrutura

```
/config      conexão PDO
/public      front controller, assets e uploads
/includes    funções compartilhadas, header/footer, auth
/sql         scripts de banco
```

## Como rodar localmente

1. Instale e inicie o **XAMPP** (Apache + MySQL).
2. Coloque o projeto em `c:/xampp/htdocs/trabalho.pet/` (ou ajuste a URL conforme a pasta).
3. Acesse `http://localhost/phpmyadmin` e importe o schema:
   - Clique em **"Importar"** no menu superior.
   - Selecione o arquivo `sql/schema.sql` deste projeto.
   - Clique em **"Executar"**. Isso cria o banco `diario_pets` e as tabelas.
4. Confira as credenciais em [config/conexao.php](config/conexao.php) (padrão XAMPP: usuário `root`, senha vazia).
5. Acesse no navegador: `http://localhost/trabalho.pet/public/`

## Status

Em desenvolvimento — Etapa 1 (estrutura inicial e banco) concluída.
