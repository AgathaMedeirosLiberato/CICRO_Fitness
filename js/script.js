document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById("conteudo-container");
    const btnIrCadastro = document.getElementById("btn-ir-cadastro");
    const btnIrLogin = document.getElementById("btn-ir-login");

    if (btnIrCadastro && container) {
        btnIrCadastro.addEventListener("click", function (e) {
            e.preventDefault(); 
            container.classList.remove("modo-login");
            container.classList.add("modo-cadastro");
        });
    }

    if (btnIrLogin && container) {
        btnIrLogin.addEventListener("click", function (e) {
            e.preventDefault();
            container.classList.remove("modo-cadastro");
            container.classList.add("modo-login");
        });
    }
});



document.addEventListener("DOMContentLoaded", () => {
    const campoCep = document.getElementById("cep");
    if (!campoCep) return;

    const ids = ["rua", "bairro", "cidade", "estado"];
    const campo = (id) => document.getElementById(id);

    function limparCamposEndereco() {
        ids.forEach((id) => { campo(id).value = ""; });
    }

    campoCep.addEventListener("input", () => {
        const d = campoCep.value.replace(/\D/g, "").slice(0, 8);
        campoCep.value = d.length > 5 ? d.slice(0, 5) + "-" + d.slice(5) : d;
    });

    campoCep.addEventListener("blur", () => {
        const cep = campoCep.value.replace(/\D/g, "");
        if (cep.length !== 8) return;

        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then((r) => r.json())
            .then((dados) => {
                if (dados.erro) {
                    alert("CEP não encontrado.");
                    limparCamposEndereco();
                    return;
                }
                campo("rua").value = dados.logradouro || "";
                campo("bairro").value = dados.bairro || "";
                campo("cidade").value = dados.localidade || "";
                campo("estado").value = dados.uf || "";
                (campo("rua").value ? campo("numero") : campo("rua")).focus();
            })
            .catch(() => {
                alert("Não foi possível consultar o CEP agora. Preencha o endereço manualmente.");
            });
    });
});


document.addEventListener("DOMContentLoaded", () => {
    const campos = document.querySelectorAll('input[data-mask="telefone"]');
    if (campos.length === 0) return;

    function formatarTelefone(d) {
        if (d.length === 0) return "";
        if (d.length <= 2) return "(" + d;
        if (d.length <= 6) return "(" + d.slice(0, 2) + ") " + d.slice(2);
        if (d.length <= 10) return "(" + d.slice(0, 2) + ") " + d.slice(2, 6) + "-" + d.slice(6);
        return "(" + d.slice(0, 2) + ") " + d.slice(2, 7) + "-" + d.slice(7);
    }

    campos.forEach((campo) => {
        campo.value = formatarTelefone(campo.value.replace(/\D/g, "").slice(0, 11));

        campo.addEventListener("beforeinput", (e) => {
            if (e.inputType && e.inputType.startsWith("insert") && e.data && /\D/.test(e.data)) {
                e.preventDefault();
            }
        });

        campo.addEventListener("input", () => {
            const cursor = campo.selectionStart ?? campo.value.length;
            const digitosAntesDoCursor = campo.value.slice(0, cursor).replace(/\D/g, "").length;

            const novo = formatarTelefone(campo.value.replace(/\D/g, "").slice(0, 11));
            campo.value = novo;

            let contados = 0;
            let posicao = digitosAntesDoCursor === 0 ? 0 : novo.length;
            for (let i = 0; i < novo.length && digitosAntesDoCursor > 0; i++) {
                if (/\d/.test(novo[i])) contados++;
                if (contados === digitosAntesDoCursor) { posicao = i + 1; break; }
            }
            if (document.activeElement === campo) campo.setSelectionRange(posicao, posicao);
        });
    });
});


document.addEventListener("DOMContentLoaded", () => {
    function formatarCpf(d) {
        if (d.length <= 3) return d;
        if (d.length <= 6) return d.slice(0, 3) + "." + d.slice(3);
        if (d.length <= 9) return d.slice(0, 3) + "." + d.slice(3, 6) + "." + d.slice(6);
        return d.slice(0, 3) + "." + d.slice(3, 6) + "." + d.slice(6, 9) + "-" + d.slice(9);
    }
    document.querySelectorAll('input[data-mask="cpf"]').forEach((campo) => {
        campo.value = formatarCpf(campo.value.replace(/\D/g, "").slice(0, 11));
        campo.addEventListener("input", () => {
            campo.value = formatarCpf(campo.value.replace(/\D/g, "").slice(0, 11));
        });
    });
});
