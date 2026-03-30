<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carteira Financeira</title>
    <style>
        :root {
            --bg: linear-gradient(135deg, #f4f7fb 0%, #e9eef8 100%);
            --card: #ffffff;
            --text: #1f2a37;
            --muted: #6b7280;
            --primary: #0f766e;
            --primary-strong: #115e59;
            --danger: #b91c1c;
            --ok: #065f46;
            --border: #d1d5db;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: var(--bg);
            min-height: 100vh;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 28px rgba(2, 6, 23, 0.08);
            margin-bottom: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .hidden { display: none; }

        label {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-strong); }
        .btn-danger { background: #fee2e2; color: var(--danger); }

        .feedback {
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .feedback.error { background: #fee2e2; color: var(--danger); }
        .feedback.success { background: #dcfce7; color: var(--ok); }

        .wallet-balance {
            font-size: 34px;
            font-weight: 700;
            margin: 4px 0 0;
        }

        .muted { color: var(--muted); font-size: 13px; }

        .history-item {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .history-type {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            color: var(--primary);
        }

        .loader {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.24);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #fff;
            backdrop-filter: blur(2px);
        }

        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .title { font-size: 22px; }
        }
    </style>
</head>
<body>
<div id="loader" class="loader hidden">Carregando...</div>
<div class="container">
    <div class="header">
        <h1 class="title">Sistema de Carteira Financeira</h1>
        <button id="logoutBtn" class="btn-danger hidden">Sair</button>
    </div>

    <div id="feedback" class="feedback hidden"></div>

    <div id="authView" class="grid">
        <div class="card">
            <h2>Login</h2>
            <label for="loginEmail">Email</label>
            <input id="loginEmail" type="email" placeholder="voce@email.com">
            <label for="loginPassword">Senha</label>
            <input id="loginPassword" type="password" placeholder="********">
            <button id="loginBtn" class="btn-primary">Entrar</button>
        </div>

        <div class="card">
            <h2>Registro</h2>
            <label for="registerName">Nome</label>
            <input id="registerName" type="text" placeholder="Seu nome">
            <label for="registerEmail">Email</label>
            <input id="registerEmail" type="email" placeholder="voce@email.com">
            <label for="registerPassword">Senha</label>
            <input id="registerPassword" type="password" placeholder="Minimo 8 caracteres">
            <label for="registerPasswordConfirmation">Confirmar senha</label>
            <input id="registerPasswordConfirmation" type="password" placeholder="Repita a senha">
            <button id="registerBtn" class="btn-primary">Criar conta</button>
        </div>
    </div>

    <div id="dashboardView" class="hidden">
        <div class="card">
            <div class="muted">Saldo atual</div>
            <p class="wallet-balance" id="walletBalance">R$ 0,00</p>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Deposito</h3>
                <label for="depositAmount">Valor</label>
                <input id="depositAmount" type="number" step="0.01" min="0.01" placeholder="0.00">
                <button id="depositBtn" class="btn-primary">Depositar</button>
            </div>

            <div class="card">
                <h3>Transferencia</h3>
                <label for="transferReceiver">ID do usuario destino</label>
                <input id="transferReceiver" type="number" min="1" placeholder="2">
                <label for="transferAmount">Valor</label>
                <input id="transferAmount" type="number" step="0.01" min="0.01" placeholder="0.00">
                <button id="transferBtn" class="btn-primary">Transferir</button>
            </div>
        </div>

        <div class="card">
            <h3>Historico de transacoes</h3>
            <div id="historyList"></div>
        </div>
    </div>
</div>

<script>
    const API_BASE = `${window.location.origin}/api/v1`;
    const TOKEN_KEY = 'auth_token';
    const loader = document.getElementById('loader');
    const feedback = document.getElementById('feedback');
    const authView = document.getElementById('authView');
    const dashboardView = document.getElementById('dashboardView');
    const logoutBtn = document.getElementById('logoutBtn');
    const walletBalance = document.getElementById('walletBalance');
    const historyList = document.getElementById('historyList');

    function showLoader(show) {
        loader.classList.toggle('hidden', !show);
    }

    function showFeedback(message, type = 'success') {
        feedback.textContent = message;
        feedback.className = `feedback ${type}`;
        feedback.classList.remove('hidden');
    }

    function hideFeedback() {
        feedback.classList.add('hidden');
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function setToken(token) {
        localStorage.setItem(TOKEN_KEY, token);
    }

    function clearToken() {
        localStorage.removeItem(TOKEN_KEY);
    }

    async function apiFetch(path, options = {}) {
        const token = getToken();
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(options.headers || {})
        };
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(`${API_BASE}${path}`, {
            ...options,
            headers
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'Erro na requisicao.');
        }
        return data;
    }

    async function login() {
        showLoader(true);
        hideFeedback();
        try {
            const response = await apiFetch('/login', {
                method: 'POST',
                body: JSON.stringify({
                    email: document.getElementById('loginEmail').value,
                    password: document.getElementById('loginPassword').value
                })
            });
            setToken(response.data.token);
            showFeedback('Login realizado com sucesso.');
            await loadDashboard();
        } catch (error) {
            showFeedback(error.message, 'error');
        } finally {
            showLoader(false);
        }
    }

    async function register() {
        showLoader(true);
        hideFeedback();
        try {
            const response = await apiFetch('/register', {
                method: 'POST',
                body: JSON.stringify({
                    name: document.getElementById('registerName').value,
                    email: document.getElementById('registerEmail').value,
                    password: document.getElementById('registerPassword').value,
                    password_confirmation: document.getElementById('registerPasswordConfirmation').value
                })
            });
            setToken(response.data.token);
            showFeedback('Conta criada com sucesso.');
            await loadDashboard();
        } catch (error) {
            showFeedback(error.message, 'error');
        } finally {
            showLoader(false);
        }
    }

    async function logout() {
        showLoader(true);
        try {
            await apiFetch('/logout', { method: 'POST' });
        } catch (error) {
            console.warn(error.message);
        } finally {
            clearToken();
            authView.classList.remove('hidden');
            dashboardView.classList.add('hidden');
            logoutBtn.classList.add('hidden');
            showLoader(false);
            showFeedback('Sessao encerrada.', 'success');
        }
    }

    async function loadWallet() {
        const response = await apiFetch('/wallet');
        walletBalance.textContent = `R$ ${Number(response.data.balance).toFixed(2).replace('.', ',')}`;
    }

    async function loadHistory() {
        const response = await apiFetch('/transactions');
        historyList.innerHTML = '';
        if (!response.data.length) {
            historyList.innerHTML = '<p class="muted">Nenhuma transacao encontrada.</p>';
            return;
        }

        response.data.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'history-item';
            row.innerHTML = `
                <div>
                    <div class="history-type">${item.type} - ${item.status}</div>
                    <div class="muted">ID: ${item.id} | sender: ${item.sender_wallet_id ?? '-'} | receiver: ${item.receiver_wallet_id ?? '-'}</div>
                </div>
                <div><strong>R$ ${Number(item.amount).toFixed(2).replace('.', ',')}</strong></div>
            `;
            historyList.appendChild(row);
        });
    }

    async function loadDashboard() {
        authView.classList.add('hidden');
        dashboardView.classList.remove('hidden');
        logoutBtn.classList.remove('hidden');
        await loadWallet();
        await loadHistory();
    }

    async function deposit() {
        showLoader(true);
        hideFeedback();
        try {
            await apiFetch('/deposit', {
                method: 'POST',
                body: JSON.stringify({
                    amount: Number(document.getElementById('depositAmount').value)
                })
            });
            showFeedback('Deposito realizado com sucesso.');
            document.getElementById('depositAmount').value = '';
            await loadDashboard();
        } catch (error) {
            showFeedback(error.message, 'error');
        } finally {
            showLoader(false);
        }
    }

    async function transfer() {
        showLoader(true);
        hideFeedback();
        try {
            await apiFetch('/transfer', {
                method: 'POST',
                body: JSON.stringify({
                    receiver_user_id: Number(document.getElementById('transferReceiver').value),
                    amount: Number(document.getElementById('transferAmount').value)
                })
            });
            showFeedback('Transferencia realizada com sucesso.');
            document.getElementById('transferReceiver').value = '';
            document.getElementById('transferAmount').value = '';
            await loadDashboard();
        } catch (error) {
            showFeedback(error.message, 'error');
        } finally {
            showLoader(false);
        }
    }

    document.getElementById('loginBtn').addEventListener('click', login);
    document.getElementById('registerBtn').addEventListener('click', register);
    document.getElementById('depositBtn').addEventListener('click', deposit);
    document.getElementById('transferBtn').addEventListener('click', transfer);
    logoutBtn.addEventListener('click', logout);

    (async function init() {
        if (!getToken()) {
            return;
        }

        showLoader(true);
        try {
            await loadDashboard();
        } catch (error) {
            clearToken();
            authView.classList.remove('hidden');
            dashboardView.classList.add('hidden');
        } finally {
            showLoader(false);
        }
    })();
</script>
</body>
</html>

