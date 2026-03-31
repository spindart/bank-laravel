<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autenticação | Wallet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-soft">
<main class="container-xl py-5">
    <div class="text-center mb-5">
        <h1 class="h3 fw-bold mb-2">Carteira Financeira</h1>
        <p class="text-muted mb-0">Acesse seu dashboard ou crie sua conta rapidamente.</p>
    </div>

    <div id="alertBox" class="alert d-none" role="alert"></div>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-5">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="badge badge-type-transfer rounded-pill py-2 px-3"><i class="bi bi-box-arrow-in-right me-1"></i>Login</span>
                        <h2 class="h6 text-uppercase text-muted mb-0">Acesse sua conta</h2>
                    </div>
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label small text-uppercase text-muted">Email</label>
                        <input id="loginEmail" type="email" class="form-control form-control-lg" placeholder="voce@email.com">
                    </div>
                    <div class="mb-4">
                        <label for="loginPassword" class="form-label small text-uppercase text-muted">Senha</label>
                        <input id="loginPassword" type="password" class="form-control form-control-lg" placeholder="********">
                    </div>
                    <button id="btnLogin" class="btn btn-primary w-100 btn-action" data-original-label="Entrar"><i class="bi bi-lock-fill me-1"></i>Entrar</button>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="badge badge-type-deposit rounded-pill py-2 px-3"><i class="bi bi-person-plus-fill me-1"></i>Registro</span>
                        <h2 class="h6 text-uppercase text-muted mb-0">Criar conta</h2>
                    </div>
                    <div class="mb-3">
                        <label for="registerName" class="form-label small text-uppercase text-muted">Nome</label>
                        <input id="registerName" type="text" class="form-control form-control-lg" placeholder="Seu nome">
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label small text-uppercase text-muted">Email</label>
                        <input id="registerEmail" type="email" class="form-control form-control-lg" placeholder="voce@email.com">
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label small text-uppercase text-muted">Senha</label>
                        <input id="registerPassword" type="password" class="form-control form-control-lg" placeholder="Minimo 8 caracteres">
                    </div>
                    <div class="mb-4">
                        <label for="registerPasswordConfirmation" class="form-label small text-uppercase text-muted">Confirmar senha</label>
                        <input id="registerPasswordConfirmation" type="password" class="form-control form-control-lg" placeholder="Repita a senha">
                    </div>
                    <button id="btnRegister" class="btn btn-success w-100 btn-action" data-original-label="Criar conta"><i class="bi bi-check-circle-fill me-1"></i>Criar conta</button>
                </div>
            </div>
        </div>
    </div>
</main>

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
            .text(message)
            .hide()
            .fadeIn(180);
    }

    function hideAlert() {
        $('#alertBox').fadeOut(120, function () {
            $(this).addClass('d-none');
        });
    }

    function toggleButtonLoading(button, loading) {
        const btn = typeof button === 'string' ? $(button) : button;
        const label = btn.data('original-label') || btn.text().trim();
        if (loading) {
            btn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${label}`);
        } else {
            btn.prop('disabled', false).text(label);
        }
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
        hideAlert();
        showLoading(true);
        toggleButtonLoading('#btnLogin', true);
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
            toggleButtonLoading('#btnLogin', false);
        }
    }

    async function onRegister() {
        hideAlert();
        showLoading(true);
        toggleButtonLoading('#btnRegister', true);
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
            toggleButtonLoading('#btnRegister', false);
        }
    }

    $(document).ready(function () {
        $('#btnLogin').on('click', onLogin);
        $('#btnRegister').on('click', onRegister);
    });
</script>
</body>
</html>

