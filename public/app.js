// Variáveis globais
let naoConformidades = [];
let naoConformidadeSelecionada = null;

// Elementos DOM
const listaNaoConformidades = document.getElementById('listaNaoConformidades');
const totalRegistros = document.getElementById('totalRegistros');
const formNaoConformidade = document.getElementById('formNaoConformidade');
const modalNaoConformidade = new bootstrap.Modal(document.getElementById('modalNaoConformidade'));
const modalDetalhes = new bootstrap.Modal(document.getElementById('modalDetalhes'));
const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));

// Elementos de filtro
const txtPesquisa = document.getElementById('txtPesquisa');
const filtroStatus = document.getElementById('filtroStatus');
const filtroSetor = document.getElementById('filtroSetor');
const btnPesquisar = document.getElementById('btnPesquisar');
const btnLimparFiltros = document.getElementById('btnLimparFiltros');

// Botões 
const btnSalvar = document.getElementById('btnSalvar');
const btnConfirmarExclusao = document.getElementById('btnConfirmarExclusao');
const btnExportar = document.getElementById('btnExportar');

// Inicialização da aplicação
document.addEventListener('DOMContentLoaded', () => {
    carregarNaoConformidades();
    configurarEventos();
});

// Funções de API
async function carregarNaoConformidades() {
    try {
        const response = await fetch('/api/naoconformidades');
        if (!response.ok) {
            throw new Error('Falha ao carregar não-conformidades');
        }
        naoConformidades = await response.json();
        filtrarEExibirNaoConformidades();
    } catch (error) {
        exibirAlerta('Erro ao carregar não-conformidades: ' + error.message, 'danger');
    }
}

async function salvarNaoConformidade(dadosNaoConformidade) {
    try {
        const url = dadosNaoConformidade.id
            ? `/api/naoconformidades/${dadosNaoConformidade.id}`
            : '/api/naoconformidades';

        const method = dadosNaoConformidade.id ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dadosNaoConformidade),
        });

        if (!response.ok) {
            throw new Error('Falha ao salvar não-conformidade');
        }

        carregarNaoConformidades();
        modalNaoConformidade.hide();

        const mensagem = dadosNaoConformidade.id
            ? 'Não-conformidade atualizada com sucesso!'
            : 'Não-conformidade registrada com sucesso!';

        exibirAlerta(mensagem, 'success');
    } catch (error) {
        exibirAlerta('Erro ao salvar não-conformidade: ' + error.message, 'danger');
    }
}

async function excluirNaoConformidade(id) {
    try {
        const response = await fetch(`/api/naoconformidades/${id}`, {
            method: 'DELETE',
        });

        if (!response.ok) {
            throw new Error('Falha ao excluir não-conformidade');
        }

        carregarNaoConformidades();
        modalConfirmacao.hide();
        exibirAlerta('Não-conformidade excluída com sucesso!', 'success');
    } catch (error) {
        exibirAlerta('Erro ao excluir não-conformidade: ' + error.message, 'danger');
    }
}

// Funções de UI
function filtrarEExibirNaoConformidades() {
    const termoPesquisa = txtPesquisa.value.toLowerCase();
    const statusFiltro = filtroStatus.value;
    const setorFiltro = filtroSetor.value;

    const resultadosFiltrados = naoConformidades.filter(nc => {
        const correspondeTermoPesquisa = termoPesquisa === '' ||
            nc.titulo.toLowerCase().includes(termoPesquisa) ||
            nc.descricao.toLowerCase().includes(termoPesquisa) ||
            nc.responsavel.toLowerCase().includes(termoPesquisa);

        const correspondeStatus = statusFiltro === '' || nc.status === statusFiltro;
        const correspondeSetor = setorFiltro === '' || nc.setor === setorFiltro;

        return correspondeTermoPesquisa && correspondeStatus && correspondeSetor;
    });

    exibirNaoConformidades(resultadosFiltrados);
}

function exibirNaoConformidades(lista) {
    listaNaoConformidades.innerHTML = '';

    if (lista.length === 0) {
        listaNaoConformidades.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="bi bi-info-circle text-muted me-2"></i>
                    Nenhuma não-conformidade encontrada
                </td>
            </tr>
        `;
    } else {
        lista.forEach(nc => {
            const row = document.createElement('tr');

            let statusClass = '';
            switch (nc.status) {
                case 'Aberto':
                    statusClass = 'status-aberto';
                    break;
                case 'Em Andamento':
                    statusClass = 'status-andamento';
                    break;
                case 'Concluído':
                    statusClass = 'status-concluido';
                    break;
                case 'Cancelado':
                    statusClass = 'status-cancelado';
                    break;
            }

            // Formato de data
            const dataFormatada = new Date(nc.data).toLocaleDateString('pt-BR');

            row.innerHTML = `
                <td>${nc.id.substring(0, 6)}...</td>
                <td>${dataFormatada}</td>
                <td>${nc.titulo}</td>
                <td>${nc.setor}</td>
                <td>${nc.responsavel}</td>
                <td><span class="badge-status ${statusClass}">${nc.status}</span></td>
                <td>
                    <div class="btn-group btn-group-acoes">
                        <button class="btn btn-sm btn-outline-info btn-detalhe" 
                                data-id="${nc.id}" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary btn-editar" 
                                data-id="${nc.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-excluir" 
                                data-id="${nc.id}" title="Excluir">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            listaNaoConformidades.appendChild(row);
        });
    }

    totalRegistros.textContent = `${lista.length} registros encontrados`;
    configurarBotoesAcao();
}

function limparFormulario() {
    formNaoConformidade.reset();
    document.getElementById('idNaoConformidade').value = '';
    document.getElementById('data').valueAsDate = new Date();
    document.getElementById('modalLabel').textContent = 'Nova Não-Conformidade';
}

function preencherFormulario(naoConformidade) {
    document.getElementById('idNaoConformidade').value = naoConformidade.id;
    document.getElementById('titulo').value = naoConformidade.titulo;
    document.getElementById('data').value = naoConformidade.data;
    document.getElementById('setor').value = naoConformidade.setor;
    document.getElementById('responsavel').value = naoConformidade.responsavel;
    document.getElementById('descricao').value = naoConformidade.descricao;
    document.getElementById('causa').value = naoConformidade.causa || '';
    document.getElementById('acoesCorretivas').value = naoConformidade.acoesCorretivas || '';
    document.getElementById('status').value = naoConformidade.status;
    document.getElementById('prazo').value = naoConformidade.prazo || '';

    document.getElementById('modalLabel').textContent = 'Editar Não-Conformidade';
}

function exibirDetalhes(naoConformidade) {
    document.getElementById('detalheId').textContent = naoConformidade.id;
    document.getElementById('detalheData').textContent = new Date(naoConformidade.data).toLocaleDateString('pt-BR');
    document.getElementById('detalheTitulo').textContent = naoConformidade.titulo;
    document.getElementById('detalheSetor').textContent = naoConformidade.setor;
    document.getElementById('detalheResponsavel').textContent = naoConformidade.responsavel;
    document.getElementById('detalheDescricao').textContent = naoConformidade.descricao;
    document.getElementById('detalheCausa').textContent = naoConformidade.causa || 'Não informada';
    document.getElementById('detalheAcoesCorretivas').textContent = naoConformidade.acoesCorretivas || 'Não informada';
    document.getElementById('detalheStatus').textContent = naoConformidade.status;

    const prazoFormatado = naoConformidade.prazo
        ? new Date(naoConformidade.prazo).toLocaleDateString('pt-BR')
        : 'Não definido';

    document.getElementById('detalhePrazo').textContent = prazoFormatado;
}

function exibirAlerta(mensagem, tipo) {
    const alertaDiv = document.createElement('div');
    alertaDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertaDiv.setAttribute('role', 'alert');
    alertaDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    `;

    // Inserir no topo da página
    const container = document.querySelector('.container');
    container.insertBefore(alertaDiv, container.firstChild);

    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        alertaDiv.remove();
    }, 5000);
}

// Configuração de eventos
function configurarEventos() {
    // Botão Nova Não-Conformidade
    document.querySelector('[data-bs-target="#modalNaoConformidade"]').addEventListener('click', () => {
        limparFormulario();
    });

    // Botão Salvar
    btnSalvar.addEventListener('click', () => {
        if (!formNaoConformidade.checkValidity()) {
            formNaoConformidade.reportValidity();
            return;
        }

        const id = document.getElementById('idNaoConformidade').value;
        const naoConformidade = {
            titulo: document.getElementById('titulo').value,
            data: document.getElementById('data').value,
            setor: document.getElementById('setor').value,
            responsavel: document.getElementById('responsavel').value,
            descricao: document.getElementById('descricao').value,
            causa: document.getElementById('causa').value,
            acoesCorretivas: document.getElementById('acoesCorretivas').value,
            status: document.getElementById('status').value,
            prazo: document.getElementById('prazo').value
        };

        if (id) {
            naoConformidade.id = id;
        }

        salvarNaoConformidade(naoConformidade);
    });

    // Botão Confirmar Exclusão
    btnConfirmarExclusao.addEventListener('click', () => {
        if (naoConformidadeSelecionada) {
            excluirNaoConformidade(naoConformidadeSelecionada);
        }
    });

    // Filtros
    btnPesquisar.addEventListener('click', filtrarEExibirNaoConformidades);
    txtPesquisa.addEventListener('keyup', event => {
        if (event.key === 'Enter') {
            filtrarEExibirNaoConformidades();
        }
    });

    filtroStatus.addEventListener('change', filtrarEExibirNaoConformidades);
    filtroSetor.addEventListener('change', filtrarEExibirNaoConformidades);

    btnLimparFiltros.addEventListener('click', () => {
        txtPesquisa.value = '';
        filtroStatus.value = '';
        filtroSetor.value = '';
        filtrarEExibirNaoConformidades();
    });

    // Exportar para CSV
    btnExportar.addEventListener('click', exportarParaCSV);
}

function configurarBotoesAcao() {
    // Botões de Detalhe
    document.querySelectorAll('.btn-detalhe').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const naoConformidade = naoConformidades.find(nc => nc.id === id);

            if (naoConformidade) {
                exibirDetalhes(naoConformidade);
                modalDetalhes.show();
            }
        });
    });

    // Botões de Editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const naoConformidade = naoConformidades.find(nc => nc.id === id);

            if (naoConformidade) {
                preencherFormulario(naoConformidade);
                modalNaoConformidade.show();
            }
        });
    });

    // Botões de Excluir
    document.querySelectorAll('.btn-excluir').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            naoConformidadeSelecionada = id;
            modalConfirmacao.show();
        });
    });
}

// Função para exportar dados para CSV
function exportarParaCSV() {
    // Obtém os dados filtrados atuais
    const termoPesquisa = txtPesquisa.value.toLowerCase();
    const statusFiltro = filtroStatus.value;
    const setorFiltro = filtroSetor.value;

    const dadosFiltrados = naoConformidades.filter(nc => {
        const correspondeTermoPesquisa = termoPesquisa === '' ||
            nc.titulo.toLowerCase().includes(termoPesquisa) ||
            nc.descricao.toLowerCase().includes(termoPesquisa) ||
            nc.responsavel.toLowerCase().includes(termoPesquisa);

        const correspondeStatus = statusFiltro === '' || nc.status === statusFiltro;
        const correspondeSetor = setorFiltro === '' || nc.setor === setorFiltro;

        return correspondeTermoPesquisa && correspondeStatus && correspondeSetor;
    });

    if (dadosFiltrados.length === 0) {
        exibirAlerta('Não há dados para exportar', 'warning');
        return;
    }

    // Cabeçalhos do CSV
    let csvContent = 'ID,Data,Título,Setor,Responsável,Descrição,Causa,Ações Corretivas,Status,Prazo\n';

    // Adiciona dados
    dadosFiltrados.forEach(nc => {
        const row = [
            nc.id,
            nc.data,
            nc.titulo.replace(/,/g, ''),
            nc.setor,
            nc.responsavel.replace(/,/g, ''),
            nc.descricao.replace(/,/g, '').replace(/\n/g, ' '),
            (nc.causa || '').replace(/,/g, '').replace(/\n/g, ' '),
            (nc.acoesCorretivas || '').replace(/,/g, '').replace(/\n/g, ' '),
            nc.status,
            nc.prazo || ''
        ];

        csvContent += row.join(',') + '\n';
    });

    // Cria um objeto Blob para o arquivo
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    // Cria um link para download e clica nele
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', 'nao-conformidades.csv');
    link.style.display = 'none';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
} 