# Sistema de Gestão Integrado

Este projeto consiste em dois sistemas integrados:
1. **Sistema de Não-Conformidades**: Para registro e controle de não-conformidades
2. **Sistema de Produção**: Para registro de produção com timer e sistema de conquistas

## Requisitos

- Node.js (v14 ou superior)
- PHP (v7.4 ou superior)
- MySQL (v5.7 ou superior)
- Servidor web (Apache ou similar para os arquivos PHP)

## Estrutura do Projeto

```
/
├── data/                  # Dados do sistema de não-conformidades
├── public/                # Arquivos públicos
│   ├── home.html          # Página inicial
│   ├── index.html         # Sistema de não-conformidades
│   ├── producao.php       # Sistema de produção
│   ├── script.js          # JavaScript para exportação Excel
│   └── styles.css         # Estilos CSS
├── server.js              # Servidor Node.js
├── package.json           # Dependências Node.js
├── database.sql           # Script SQL para criar o banco de dados
└── README.md              # Este arquivo
```

## Configuração

### 1. Configuração do Node.js (Sistema de Não-Conformidades)

1. Instale as dependências:
   ```
   npm install
   ```

2. Inicie o servidor Node.js:
   ```
   node server.js
   ```

3. O servidor estará disponível em `http://localhost:3000`

### 2. Configuração do PHP e MySQL (Sistema de Produção)

1. Configure um servidor web (Apache, XAMPP, etc.) para servir arquivos PHP

2. Crie o banco de dados MySQL:
   - Importe o arquivo `database.sql` para o seu servidor MySQL
   - Ou execute os comandos SQL manualmente

3. Configure a conexão com o banco de dados:
   - Abra o arquivo `public/producao.php`
   - Edite as variáveis de conexão no início do arquivo:
     ```php
     $host = "localhost";
     $usuario = "root";
     $senha = "";
     $banco = "sistema_producao";
     ```

### 3. Configuração Integrada (Opcional)

Para uma experiência integrada completa, você pode:

1. Configurar o Apache para servir os arquivos PHP
2. Configurar um proxy reverso para redirecionar as requisições Node.js
3. Ou simplesmente executar o Node.js para o sistema de não-conformidades e acessar o sistema de produção diretamente pelo servidor web

## Funcionalidades

### Sistema de Não-Conformidades

- Registro de não-conformidades
- Acompanhamento de status
- Filtros de busca
- Exportação para Excel

### Sistema de Produção

- Timer de produção com controle de tempo
- Registro de produção com cálculo automático de pontos
- Sistema de medalhas baseado na quantidade produzida:
  - Bronze: 5.000+ peças
  - Prata: 6.000+ peças
  - Ouro: 7.000+ peças
- Histórico de produção

## Uso do Sistema de Produção

1. Inicie o timer clicando no botão "Iniciar"
2. Preencha os dados do formulário (funcionário, OS, material, etc.)
3. Insira a quantidade de peças produzidas
4. Clique em "Concluir" quando terminar a produção
5. Clique em "Registrar Produção" para salvar os dados

O sistema calculará automaticamente:
- A duração em minutos
- Os pontos (quantidade / duração)
- A medalha conquistada com base na quantidade

## Licença

Este projeto está licenciado sob a licença ISC. 