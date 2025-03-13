# Sistema de Registro e Controle de Não-Conformidades

Um sistema web para registro, gestão e acompanhamento de não-conformidades, desenvolvido com Node.js, HTML, JavaScript e armazenamento em arquivo JSON.

## Funcionalidades

- Cadastro de não-conformidades com informações detalhadas
- Atualização do status e informações de acompanhamento
- Filtragem por status, setor e texto
- Visualização detalhada dos registros
- Exportação dos dados para CSV

## Requisitos

- Node.js (versão 14.x ou superior)
- Navegador web moderno

## Instalação

1. Clone este repositório ou faça o download dos arquivos
2. Navegue até a pasta do projeto e instale as dependências:

```bash
npm install
```

3. Inicie o servidor:

```bash
node server.js
```

4. Acesse o sistema no navegador:

```
http://localhost:3000
```

## Estrutura do Projeto

- `server.js` - Servidor Node.js e API
- `public/` - Arquivos estáticos da aplicação web
  - `index.html` - Interface do usuário
  - `styles.css` - Estilos da aplicação
  - `app.js` - Lógica do cliente
- `data/` - Pasta onde são armazenados os dados em formato JSON

## Armazenamento de Dados

As não-conformidades são armazenadas em arquivo JSON localizado em:

```
data/nao-conformidades.json
```

## API Endpoints

O servidor fornece os seguintes endpoints para a API:

- `GET /api/naoconformidades` - Lista todas as não-conformidades
- `POST /api/naoconformidades` - Cria uma nova não-conformidade
- `GET /api/naoconformidades/:id` - Busca uma não-conformidade específica
- `PUT /api/naoconformidades/:id` - Atualiza uma não-conformidade
- `DELETE /api/naoconformidades/:id` - Exclui uma não-conformidade

## Como Usar

1. Na tela inicial, clique em "Nova Não-Conformidade" para registrar uma ocorrência
2. Preencha todos os campos obrigatórios e clique em "Salvar"
3. Utilize os filtros para localizar registros específicos
4. Clique nos botões de ação para ver detalhes, editar ou excluir um registro
5. Utilize o botão "Exportar" para baixar os dados em formato CSV 