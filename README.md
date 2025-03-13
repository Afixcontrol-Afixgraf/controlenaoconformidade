# Sistema de Controle de Não-Conformidades e Produção

Um sistema integrado para gerenciar não-conformidades e registros de produção em uma empresa.

## Estrutura do Projeto

```
├── index.html              # Página principal do sistema de não-conformidades
├── home.html               # Página inicial com links para os sistemas
├── producao.html           # Interface do sistema de produção (apenas frontend)
├── database.sql            # Script SQL para criação do banco de dados
├── assets/                 # Pasta de recursos estáticos
│   ├── css/                # Arquivos CSS
│   │   └── styles.css      # Estilos CSS
│   ├── js/                 # Arquivos JavaScript
│   │   ├── script.js       # Script para o sistema de não-conformidades
│   │   └── app.js          # Script para funcionalidades gerais
│   └── data/               # Dados locais
│       └── nao-conformidades.json  # Dados de não-conformidades
└── api/                    # Backend API
    ├── config.php          # Configuração da conexão com o banco de dados
    ├── funcionarios.php    # API para gerenciar funcionários
    └── producao.php        # API para gerenciar registros de produção
```

## Requisitos

- Servidor web (Apache, Nginx, etc.)
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Navegador web moderno

## Instalação

1. Clone ou baixe este repositório para o diretório do seu servidor web
2. Importe o arquivo `database.sql` para o seu servidor MySQL
3. Configure as credenciais do banco de dados no arquivo `api/config.php`
4. Acesse o sistema através do navegador (ex: http://localhost/controlenaoconformidade/)

## Funcionalidades

### Sistema de Não-Conformidades

- Registro de não-conformidades
- Acompanhamento do status (aberto, em andamento, concluído)
- Registro de ações corretivas
- Definição de responsáveis e prazos
- Exportação de relatórios

### Sistema de Produção

- Registro de produção diária
- Timer para controle de tempo de produção
- Cálculo automático de eficiência
- Sistema de medalhas por desempenho
- Histórico de produção

## Tecnologias Utilizadas

- HTML5, CSS3, JavaScript
- Bootstrap 5
- PHP
- MySQL
- AJAX para comunicação assíncrona

## Arquitetura

O sistema utiliza uma arquitetura cliente-servidor com separação clara entre frontend e backend:

- **Frontend**: Páginas HTML/PHP com JavaScript para interatividade
- **Backend**: API RESTful em PHP para manipulação de dados
- **Banco de Dados**: MySQL para armazenamento persistente

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo LICENSE para mais detalhes. 