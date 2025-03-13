const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs');
const path = require('path');
const XLSX = require('xlsx');
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
    res.sendFile(path.join(__dirname, 'public', 'home.html'));
});

// Rota para o sistema de não-conformidades
app.get('/index.html', (req, res) => {
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

// API - Exportar não-conformidades para Excel
app.get('/api/naoconformidades/exportar', (req, res) => {
    try {
        const data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));
        
        // Aplicar filtros se fornecidos na query
        let dadosFiltrados = [...data];
        const { status, setor, termo } = req.query;
        
        if (status) {
            dadosFiltrados = dadosFiltrados.filter(item => 
                item.status && item.status.toLowerCase() === status.toLowerCase());
        }
        
        if (setor) {
            dadosFiltrados = dadosFiltrados.filter(item => 
                item.area && item.area.toLowerCase() === setor.toLowerCase());
        }
        
        if (termo) {
            const termoBusca = termo.toLowerCase();
            dadosFiltrados = dadosFiltrados.filter(item => 
                (item.descricao && item.descricao.toLowerCase().includes(termoBusca)) ||
                (item.responsavel && item.responsavel.toLowerCase().includes(termoBusca)) ||
                (item.id && item.id.toLowerCase().includes(termoBusca))
            );
        }
        
        // Prepara os dados para exportação com formatação melhorada
        const dadosExportacao = dadosFiltrados.map(item => {
            // Formatar datas
            const dataRegistro = item.dataRegistro ? new Date(item.dataRegistro) : null;
            const dataFormatada = dataRegistro ? 
                `${dataRegistro.getDate().toString().padStart(2, '0')}/${(dataRegistro.getMonth() + 1).toString().padStart(2, '0')}/${dataRegistro.getFullYear()}` : '';
            
            const dataAtualizacao = item.dataAtualizacao ? new Date(item.dataAtualizacao) : null;
            const dataAtualizacaoFormatada = dataAtualizacao ? 
                `${dataAtualizacao.getDate().toString().padStart(2, '0')}/${(dataAtualizacao.getMonth() + 1).toString().padStart(2, '0')}/${dataAtualizacao.getFullYear()}` : '';
            
            return {
                'ID': item.id,
                'Data': item.data,
                'Título': item.titulo || '',
                'Descrição': item.descricao || '',
                'Setor': item.area || '',
                'Responsável': item.responsavel || '',
                'Status': item.status || '',
                'Prazo': item.prazo || '',
                'Causa': item.causa || '',
                'Ações Corretivas': item.acoesCorretivas || '',
                'Ações': Array.isArray(item.acoes) ? item.acoes.map(acao => acao.descricao).join('; ') : '',
                'Data de Registro': dataFormatada,
                'Data de Atualização': dataAtualizacaoFormatada
            };
        });

        // Cria uma nova planilha
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(dadosExportacao);
        
        // Ajusta a largura das colunas
        const colunas = Object.keys(dadosExportacao[0] || {});
        const largurasColunas = {};
        
        // Define largura mínima para cada coluna
        colunas.forEach(col => {
            largurasColunas[col] = { wch: Math.max(col.length, 10) };
        });
        
        // Ajusta largura com base no conteúdo
        dadosExportacao.forEach(row => {
            colunas.forEach(col => {
                const valor = String(row[col] || '');
                const comprimento = valor.length;
                if (comprimento > largurasColunas[col].wch) {
                    largurasColunas[col].wch = Math.min(comprimento, 50); // Limita a 50 caracteres
                }
            });
        });
        
        // Aplica as larguras das colunas
        worksheet['!cols'] = colunas.map(col => largurasColunas[col]);

        // Adiciona a planilha ao workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Não-Conformidades');

        // Gera o arquivo Excel
        const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'buffer' });

        // Define os headers para download
        res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        res.setHeader('Content-Disposition', 'attachment; filename=nao-conformidades.xlsx');
        res.setHeader('Cache-Control', 'no-cache');

        // Envia o arquivo
        res.send(excelBuffer);
    } catch (error) {
        console.error('Erro ao exportar:', error);
        res.status(500).json({ erro: 'Erro ao exportar não-conformidades', detalhes: error.message });
    }
});

// Inicia o servidor
app.listen(port, () => {
    console.log(`Servidor rodando na porta ${port}`);
    console.log(`Acesse http://localhost:${port} no seu navegador`);
}); 