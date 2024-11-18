window.onload = function() {
    // Obtém a data e hora atuais
    var now = new Date();
    // Formata a data no padrão yyyy-MM-ddTHH:mm
    var year = now.getFullYear();
    var month = ('0' + (now.getMonth() + 1)).slice(-2);
    var day = ('0' + now.getDate()).slice(-2);
    var hours = ('0' + now.getHours()).slice(-2);
    var minutes = ('0' + now.getMinutes()).slice(-2);
    // Junta a data e hora
    var datetime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    // Define o valor do campo datetime-local
    document.getElementById('data_venda').value = datetime;
};