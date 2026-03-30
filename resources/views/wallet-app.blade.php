<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Carteira</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .app-card { border: 0; border-radius: 14px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06); }
        .money { font-size: 2.1rem; font-weight: 700; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard da Carteira</h1>
        <button id="btnLogout" class="btn btn-outline-danger">Sair</button>
    </div>

    <div id="alertBox" class="alert d-none" role="alert"></div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card app-card">
                <div class="card-body">
                    <div class="text-muted">Saldo atual</div>
                    <div id="walletBalance" class="money">R$ 0,00</div>
                    <div class="mt-2">
                        <span class="badge text-bg-dark fs-6 px-3 py-2">Seu ID de usuário: <span id="currentUserId">-</span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card app-card">
                <div class="card-body">
                    <h3 class="h6 mb-3">Depositar</h3>
                    <div class="input-group mb-2">
                        <span class="input-group-text">R$</span>
                        <input id="depositAmount" type="number" step="0.01" min="0.01" class="form-control" placeholder="0.00">
                    </div>
                    <button id="btnDeposit" class="btn btn-primary w-100">Depositar</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card app-card">
                <div class="card-body">
                    <h3 class="h6 mb-3">Transferir</h3>
                    <input id="transferReceiver" type="number" min="1" class="form-control mb-2" placeholder="ID do usuario destino">
                    <div class="input-group mb-2">
                        <span class="input-group-text">R$</span>
                        <input id="transferAmount" type="number" step="0.01" min="0.01" class="form-control" placeholder="0.00">
                    </div>
                    <button id="btnTransfer" class="btn btn-warning w-100">Transferir</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 mb-0">Historico de transacoes</h3>
                <button id="btnRefresh" class="btn btn-sm btn-outline-secondary">Atualizar</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Criada em</th>
                        <th>Acoes</th>
                    </tr>
                    </thead>
                    <tbody id="txTableBody">
                    <tr><td colspan="8" class="text-center text-muted">Sem transacoes.</td></tr>
                    </tbody>
                </table>
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

    function hideAlert() {
        $('#alertBox').addClass('d-none');
    }

    function formatMoney(value) {
        const n = Number(value || 0);
        return `R$ ${n.toFixed(2).replace('.', ',')}`;
    }

    function token() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function clearToken() {
        localStorage.removeItem(TOKEN_KEY);
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
            data: data ? JSON.stringify(data) : null,
            headers: { Authorization: `Bearer ${token()}` }
        });
    }

    async function loadWallet() {
        const res = await apiRequest('GET', '/wallet');
        $('#walletBalance').text(formatMoney(res.data.balance));
        $('#currentUserId').text(res.data.user_id ?? '-');
    }

    async function loadTransactions() {
        const res = await apiRequest('GET', '/transactions');
        const tbody = $('#txTableBody');
        tbody.empty();

        if (!res.data || !res.data.length) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">Sem transacoes.</td></tr>');
            return;
        }

        res.data.forEach((tx) => {
            const canReverse = tx.status === 'completed' && (tx.type === 'deposit' || tx.type === 'transfer');
            const actionBtn = canReverse
                ? `<button class="btn btn-sm btn-outline-danger btn-reverse" data-id="${tx.id}">Reverter</button>`
                : '<span class="text-muted">-</span>';

            tbody.append(`
                <tr>
                    <td>${tx.id}</td>
                    <td><span class="badge text-bg-info">${tx.type}</span></td>
                    <td><span class="badge text-bg-secondary">${tx.status}</span></td>
                    <td>${formatMoney(tx.amount)}</td>
                    <td>${tx.sender_wallet_id ?? '-'}</td>
                    <td>${tx.receiver_wallet_id ?? '-'}</td>
                    <td>${tx.created_at ?? '-'}</td>
                    <td>${actionBtn}</td>
                </tr>
            `);
        });
    }

    async function loadDashboard() {
        await loadWallet();
        await loadTransactions();
    }

    async function onLogout() {
        hideAlert();
        showLoading(true);
        try {
            await apiRequest('POST', '/logout');
        } catch (_) {
        } finally {
            clearToken();
            window.location.href = '/auth';
        }
    }

    async function onDeposit() {
        hideAlert();
        showLoading(true);
        try {
            await apiRequest('POST', '/deposit', {
                amount: Number($('#depositAmount').val())
            });
            $('#depositAmount').val('');
            await loadDashboard();
            showAlert('success', 'Deposito realizado com sucesso.');
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
        }
    }

    async function onTransfer() {
        hideAlert();
        showLoading(true);
        try {
            await apiRequest('POST', '/transfer', {
                receiver_user_id: Number($('#transferReceiver').val()),
                amount: Number($('#transferAmount').val())
            });
            $('#transferReceiver').val('');
            $('#transferAmount').val('');
            await loadDashboard();
            showAlert('success', 'Transferencia realizada com sucesso.');
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
        }
    }

    async function onReverse(id) {
        hideAlert();
        showLoading(true);
        try {
            await apiRequest('POST', `/reverse/${id}`);
            await loadDashboard();
            showAlert('success', `Transacao ${id} revertida com sucesso.`);
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
        }
    }

    $(document).ready(async function () {
        if (!token()) {
            window.location.href = '/auth';
            return;
        }

        $('#btnLogout').on('click', onLogout);
        $('#btnDeposit').on('click', onDeposit);
        $('#btnTransfer').on('click', onTransfer);
        $('#btnRefresh').on('click', loadDashboard);
        $(document).on('click', '.btn-reverse', function () {
            onReverse($(this).data('id'));
        });

        showLoading(true);
        try {
            await loadDashboard();
        } catch (_) {
            clearToken();
            window.location.href = '/auth';
        } finally {
            showLoading(false);
        }
    });
</script>
</body>
</html>
