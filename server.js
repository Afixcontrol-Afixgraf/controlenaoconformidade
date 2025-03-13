const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs');
const path = require('path');
const app = express();
const port = 3000;

// Middleware para processar requisições
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(express.static('public'));

// Arquivo onde serão armazenadas as não-conformidades
const DATA_FILE = path.join(__dirname, 'data', 'nao-conformidades.json');

// Certifica-se de que o diretório 'data' existe
if (!fs.existsSync(path.join(__dirname, 'data'))) {
    fs.mkdirSync(path.join(__dirname, 'data'));
}

// Certifica-se de que o arquivo de dados existe
if (!fs.existsSync(DATA_FILE)) {
    fs.writeFileSync(DATA_FILE, JSON.stringify([], null, 2), 'utf8');
}

// Rota principal - página inicial
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// API - Listar todas as não-conformidades
app.get('/api/naoconformidades', (req, res) => {
    try {
        const data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));
        res.json(data);
    } catch (error) {
        res.status(500).json({ erro: 'Erro ao ler as não-conformidades', detalhes: error.message });
    }
});

// API - Adicionar nova não-conformidade
app.post('/api/naoconformidades', (req, res) => {
    try {
        const data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));

        const novaNaoConformidade = {
            id: Date.now().toString(),
            data: new Date().toISOString().split('T')[0],
            ...req.body,
            status: req.body.status || 'Aberto',
            acoes: req.body.acoes || [],
            dataRegistro: new Date().toISOString()
        };

        data.push(novaNaoConformidade);
        fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf8');

        res.status(201).json(novaNaoConformidade);
    } catch (error) {
        res.status(500).json({ erro: 'Erro ao adicionar não-conformidade', detalhes: error.message });
    }
});

// API - Buscar uma não-conformidade específica
app.get('/api/naoconformidades/:id', (req, res) => {
    try {
        const data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));
        const naoConformidade = data.find(item => item.id === req.params.id);

        if (!naoConformidade) {
            return res.status(404).json({ erro: 'Não-conformidade não encontrada' });
        }

        res.json(naoConformidade);
    } catch (error) {
        res.status(500).json({ erro: 'Erro ao buscar não-conformidade', detalhes: error.message });
    }
});

// API - Atualizar uma não-conformidade
app.put('/api/naoconformidades/:id', (req, res) => {
    try {
        const data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));
        const index = data.findIndex(item => item.id === req.params.id);

        if (index === -1) {
            return res.status(404).json({ erro: 'Não-conformidade não encontrada' });
        }

        const naoConformidadeAtualizada = {
            ...data[index],
            ...req.body,
            dataAtualizacao: new Date().toISOString()
        };

        data[index] = naoConformidadeAtualizada;
        fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf8');

        res.json(naoConformidadeAtualizada);
    } catch (error) {
        res.status(500).json({ erro: 'Erro ao atualizar não-conformidade', detalhes: error.message });
    }
});

// API - Excluir uma não-conformidade
app.delete('/api/naoconformidades/:id', (req, res) => {
    try {
        let data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));
        const index = data.findIndex(item => item.id === req.params.id);

        if (index === -1) {
            return res.status(404).json({ erro: 'Não-conformidade não encontrada' });
        }

        data.splice(index, 1);
        fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf8');

        res.json({ mensagem: 'Não-conformidade excluída com sucesso' });
    } catch (error) {
        res.status(500).json({ erro: 'Erro ao excluir não-conformidade', detalhes: error.message });
    }
});

// Inicia o servidor
app.listen(port, () => {
    console.log(`Servidor rodando na porta ${port}`);
    console.log(`Acesse http://localhost:${port} no seu navegador`);
}); 