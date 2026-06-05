// Confirmação de exclusão
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm || 'Confirmar esta ação?')) e.preventDefault();
    });
});

// Máscara CPF
document.querySelectorAll('input[data-mask="cpf"]').forEach(input => {
    input.addEventListener('input', () => {
        let v = input.value.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = v;
    });
});

// Máscara telefone
document.querySelectorAll('input[data-mask="phone"]').forEach(input => {
    input.addEventListener('input', () => {
        let v = input.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 10) v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        else v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        input.value = v;
    });
});
