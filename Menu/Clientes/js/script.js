function validarCPF(campo) {
    const cpf = campo.value;
    const cpfError = document.getElementById('cpf-error');

    // Remover qualquer caractere que não seja numérico
    campo.value = cpf.replace(/\D/g, '');

    // Exibir mensagem de erro se o usuário tentar digitar caracteres não numéricos
    if (cpf !== campo.value) {
        cpfError.style.display = 'inline';
    } else {
        cpfError.style.display = 'none';
    }
}