<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autenticação | Carteira</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .app-card { border: 0; border-radius: 14px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06); }
    </style>
</head>
<body>
<div class="container py-5">
    <h1 class="h3 mb-4 text-center">Carteira Financeira</h1>
    <div id="alertBox" class="alert d-none" role="alert"></div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card app-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">Login</h2>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input id="loginEmail" type="email" class="form-control" placeholder="voce@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input id="loginPassword" type="password" class="form-control" placeholder="********">
                    </div>
                    <button id="btnLogin" class="btn btn-primary w-100">Entrar</button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card app-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">Registro</h2>
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input id="registerName" type="text" class="form-control" placeholder="Seu nome">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input id="registerEmail" type="email" class="form-control" placeholder="voce@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input id="registerPassword" type="password" class="form-control" placeholder="Minimo 8 caracteres">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar senha</label>
                        <input id="registerPasswordConfirmation" type="password" class="form-control" placeholder="Repita a senha">
                    </div>
                    <button id="btnRegister" class="btn btn-success w-100">Criar conta</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="globalSpinner" class="position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1055;">
    <div class="spinner-border text-light" role="status"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    const API_BASE = `${window.location.origin}/api/v1`;
    const TOKEN_KEY = 'wallet_token';

    function showLoading(show) {
        $('#globalSpinner').toggleClass('d-none', !show).toggleClass('d-flex', show);
    }

    function showAlert(type, message) {
        const box = $('#alertBox');
        box.removeClass('d-none alert-success alert-danger alert-warning')
            .addClass(`alert-${type}`)
            .text(message);
    }

    function parseError(xhr) {
        const res = xhr.responseJSON;
        if (!res) return 'Erro inesperado.';
        if (res.errors && typeof res.errors === 'object') {
            const firstKey = Object.keys(res.errors)[0];
            if (firstKey && Array.isArray(res.errors[firstKey])) return res.errors[firstKey][0];
        }
        return res.message || 'Erro inesperado.';
    }

    function apiRequest(method, endpoint, data = null) {
        return $.ajax({
            url: `${API_BASE}${endpoint}`,
            method,
            contentType: 'application/json',
            data: data ? JSON.stringify(data) : null
        });
    }

    function setToken(value) {
        localStorage.setItem(TOKEN_KEY, value);
    }

    async function onLogin() {
        showLoading(true);
        try {
            const res = await apiRequest('POST', '/login', {
                email: $('#loginEmail').val(),
                password: $('#loginPassword').val()
            });
            setToken(res.data.token);
            window.location.href = '/dashboard';
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
        }
    }

    async function onRegister() {
        showLoading(true);
        try {
            const res = await apiRequest('POST', '/register', {
                name: $('#registerName').val(),
                email: $('#registerEmail').val(),
                password: $('#registerPassword').val(),
                password_confirmation: $('#registerPasswordConfirmation').val()
            });
            setToken(res.data.token);
            window.location.href = '/dashboard';
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
        }
    }

    $(document).ready(function () {
        $('#btnLogin').on('click', onLogin);
        $('#btnRegister').on('click', onRegister);
    });
</script>
</body>
</html>

