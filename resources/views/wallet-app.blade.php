<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wallet | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-soft">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-xl">
        <a class="navbar-brand fw-bold text-primary" href="#">Wallet</a>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block">
                <div class="text-muted small">Usuário logado</div>
                <div id="currentUserBadge" class="fw-semibold">ID -</div>
            </div>
            <button id="btnLogout" class="btn btn-outline-danger btn-sm" data-original-label="Sair">Sair</button>
        </div>
    </div>
</nav>

<main class="container-xl pb-5">
    <div id="alertBox" class="alert d-none" role="alert"></div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="d-block text-uppercase text-muted fs-7 mb-2">Saldo disponível</span>
                            <div id="walletBalance" class="display-5 fw-semibold text-success">R$ 0,00</div>
                        </div>
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary py-2 px-3">Carteira</span>
                    </div>
               
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3">Depositar</h2>
                    <label for="depositAmount" class="form-label small text-uppercase">Valor</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">R$</span>
                        <input id="depositAmount" type="number" step="0.01" min="0.01" class="form-control form-control-lg" placeholder="0,00">
                    </div>
                    <button id="btnDeposit" class="btn btn-primary w-100 btn-action" data-original-label="Depositar">Depositar</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3">Transferir</h2>
                    <label for="transferReceiver" class="form-label small text-uppercase">ID do usuário destino</label>
                    <input id="transferReceiver" type="number" min="1" class="form-control form-control-lg mb-3" placeholder="ID do usuário destino">
                    <label for="transferAmount" class="form-label small text-uppercase">Valor</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">R$</span>
                        <input id="transferAmount" type="number" step="0.01" min="0.01" class="form-control form-control-lg" placeholder="0,00">
                    </div>
                    <button id="btnTransfer" class="btn btn-success w-100 btn-action" data-original-label="Transferir">Transferir</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Histórico de transações</h2>
                    <p class="text-muted mb-0">Últimas movimentações da sua carteira</p>
                </div>
                <button id="btnRefresh" class="btn btn-sm btn-outline-secondary btn-action" data-original-label="Atualizar">Atualizar</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th class="text-end">Valor</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Criada em</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody id="txTableBody">
                    <tr><td colspan="8" class="text-center text-muted py-4">Sem transações.</td></tr>
                    </tbody>
                </table>
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
    let currentWalletId = null;

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

    function formatMoney(value) {
        const n = Number(value || 0);
        return `R$ ${n.toFixed(2).replace('.', ',')}`;
    }

    function formatDateTime(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear());
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    }

    function getTypeBadge(type) {
        const mapping = {
            deposit: { label: 'Depósito', class: 'badge-type-deposit', icon: 'bi-arrow-down-circle' },
            transfer: { label: 'Transferência', class: 'badge-type-transfer', icon: 'bi-arrow-right-circle' },
            reversal: { label: 'Reversão', class: 'badge-type-reversal', icon: 'bi-arrow-counterclockwise' }
        };

        const item = mapping[type] || { label: type, class: 'badge bg-secondary', icon: 'bi-three-dots' };
        return `<span class="badge ${item.class} rounded-pill py-2 px-3"><i class="bi ${item.icon} me-1"></i>${item.label}</span>`;
    }

    function getStatusBadge(status) {
        const mapping = {
            completed: { label: 'Concluído', class: 'badge-status-completed' },
            reversed: { label: 'Revertido', class: 'badge-status-reversed' },
            pending: { label: 'Pendente', class: 'badge-status-pending' }
        };

        const item = mapping[status] || { label: status, class: 'badge bg-secondary' };
        return `<span class="badge ${item.class} rounded-pill py-2 px-3">${item.label}</span>`;
    }

    function canReverseTransaction(tx) {
        if (tx.status !== 'completed') {
            return false;
        }

        if (tx.type === 'deposit') {
            return tx.receiver_wallet_id === currentWalletId;
        }

        if (tx.type === 'transfer') {
            return tx.sender_wallet_id === currentWalletId;
        }

        return false;
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
        $('#currentUserBadge').text(`ID ${res.data.user_id ?? '-'}`);
        currentWalletId = res.data.id ?? null;
    }

    async function loadTransactions() {
        const res = await apiRequest('GET', '/transactions');
        const tbody = $('#txTableBody');
        tbody.empty();

        if (!res.data || !res.data.length) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted py-4">Sem transações.</td></tr>');
            return;
        }

        res.data.forEach((tx) => {
            const actionBtn = canReverseTransaction(tx)
                ? `<button class="btn btn-sm btn-outline-danger btn-reverse" data-original-label="Reverter" data-id="${tx.id}"><i class="bi bi-arrow-counterclockwise me-1"></i>Reverter</button>`
                : '<span class="text-muted">-</span>';

            tbody.append(`
                <tr>
                    <td>${tx.id}</td>
                    <td>${getTypeBadge(tx.type)}</td>
                    <td>${getStatusBadge(tx.status)}</td>
                    <td class="text-end text-dark fw-semibold">${formatMoney(tx.amount)}</td>
                    <td>${tx.sender_wallet_id ?? '-'}</td>
                    <td>${tx.receiver_wallet_id ?? '-'}</td>
                    <td>${formatDateTime(tx.created_at)}</td>
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
        toggleButtonLoading('#btnLogout', true);
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
        toggleButtonLoading('#btnDeposit', true);
        try {
            await apiRequest('POST', '/deposit', {
                amount: Number($('#depositAmount').val())
            });
            $('#depositAmount').val('');
            await loadDashboard();
            showAlert('success', 'Depósito realizado com sucesso.');
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
            toggleButtonLoading('#btnDeposit', false);
        }
    }

    async function onTransfer() {
        hideAlert();
        showLoading(true);
        toggleButtonLoading('#btnTransfer', true);
        try {
            await apiRequest('POST', '/transfer', {
                receiver_user_id: Number($('#transferReceiver').val()),
                amount: Number($('#transferAmount').val())
            });
            $('#transferReceiver').val('');
            $('#transferAmount').val('');
            await loadDashboard();
            showAlert('success', 'Transferência realizada com sucesso.');
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
            toggleButtonLoading('#btnTransfer', false);
        }
    }

    async function onReverse(id) {
        hideAlert();
        showLoading(true);
        try {
            await apiRequest('POST', `/reverse/${id}`);
            await loadDashboard();
            showAlert('success', `Transação ${id} revertida com sucesso.`);
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
        $('#btnRefresh').on('click', function () {
            toggleButtonLoading('#btnRefresh', true);
            loadDashboard().finally(() => toggleButtonLoading('#btnRefresh', false));
        });
        $(document).on('click', '.btn-reverse', function () {
            const button = $(this);
            const id = button.data('id');
            toggleButtonLoading(button, true);
            onReverse(id).finally(() => toggleButtonLoading(button, false));
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
