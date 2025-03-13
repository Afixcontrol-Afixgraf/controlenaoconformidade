// Função para exportar dados para Excel
async function exportarParaExcel() {
    const btnExportar = document.getElementById('btnExportar');
    const textoOriginal = btnExportar.innerHTML;
    
    try {
        // Mostrar feedback visual de carregamento
        btnExportar.innerHTML = '<i class="bi bi-hourglass-split"></i> Exportando...';
        btnExportar.disabled = true;
        btnExportar.classList.add('disabled');
        
        const response = await fetch('/api/naoconformidades/exportar');
        if (!response.ok) {
            throw new Error('Erro ao exportar dados');
        }
        
        // Obtém o blob do arquivo Excel
        const blob = await response.blob();
        
        // Cria um link para download
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'nao-conformidades.xlsx';
        
        // Adiciona o link ao documento e clica nele
        document.body.appendChild(a);
        a.click();
        
        // Limpa
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Feedback de sucesso
        btnExportar.innerHTML = '<i class="bi bi-check-circle"></i> Exportado!';
        btnExportar.classList.remove('btn-outline-success');
        btnExportar.classList.add('btn-success');
        
        // Restaura o botão após 2 segundos
        setTimeout(() => {
            btnExportar.innerHTML = textoOriginal;
            btnExportar.disabled = false;
            btnExportar.classList.remove('disabled', 'btn-success');
            btnExportar.classList.add('btn-outline-success');
        }, 2000);
    } catch (error) {
        console.error('Erro ao exportar:', error);
        
        // Feedback de erro
        btnExportar.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Erro ao exportar';
        btnExportar.classList.remove('btn-outline-success');
        btnExportar.classList.add('btn-outline-danger');
        
        // Restaura o botão após 3 segundos
        setTimeout(() => {
            btnExportar.innerHTML = textoOriginal;
            btnExportar.disabled = false;
            btnExportar.classList.remove('disabled', 'btn-outline-danger');
            btnExportar.classList.add('btn-outline-success');
        }, 3000);
        
        // Mostra alerta
        alert('Erro ao exportar dados para Excel. Por favor, tente novamente.');
    }
}

// Adiciona o evento de clique ao botão de exportação quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    const btnExportar = document.getElementById('btnExportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', exportarParaExcel);
    }
}); 