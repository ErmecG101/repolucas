// Função para formatar a data e hora no formato necessário
function formatDateTime() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0'); // Meses vão de 0 a 11
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    // Formatar como yyyy-MM-ddTHH:mm
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Definir o valor do input quando a página for carregada
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('data_criacao').value = formatDateTime();
});
