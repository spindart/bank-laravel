<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wallet | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-soft">
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <span class="sidebar-brand-text">Wallet</span>
        </div>

        <nav class="sidebar-nav">
            <a href="#" class="sidebar-link is-active">
                <i class="bi bi-house-door"></i>
                <span>Dashboard</span>
            </a>
            <a href="#historySection" class="sidebar-link">
                <i class="bi bi-clock-history"></i>
                <span>Transacoes</span>
            </a>
            {{-- <a href="#historySection" class="sidebar-link">
                <i class="bi bi-receipt"></i>
                <span>Extrato</span>
            </a>
            <a href="#" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Configuracoes</span>
            </a> --}}
        </nav>

        {{-- <div class="sidebar-help">
            <div class="sidebar-help-icon">
                <i class="bi bi-headset"></i>
            </div>
            <h3>Precisa de ajuda?</h3>
            <p>Entre em contato com nosso suporte.</p>
            <button type="button" class="btn btn-outline-primary w-100">Fale conosco</button>
        </div> --}}
    </aside>

    <div class="app-content">
        <header class="topbar">
            <div class="topbar-title-wrap">
                <div class="topbar-kicker">Painel financeiro</div>
                <h1 class="topbar-title">Sua carteira em um unico lugar</h1>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-badge">ID</div>
                <div class="topbar-user-copy">
                    <div class="small text-muted">Usuario logado</div>
                    <div id="currentUserBadge" class="fw-semibold">ID -</div>
                    <div id="realtimeStatus" class="small text-muted">Tempo real: conectando...</div>
                </div>
                <button id="btnLogout" class="btn btn-outline-danger topbar-logout" data-original-label="Sair">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </button>
            </div>
        </header>

        <main class="app-main">
            <div id="alertBox" class="alert d-none" role="alert"></div>

            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="card dashboard-card balance-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <span class="d-block text-uppercase text-muted fs-7 mb-2">Saldo disponivel</span>
                                    <div id="walletBalance" class="display-5 fw-semibold text-success">R$ 0,00</div>
                                </div>
                                <div class="balance-icon">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                            </div>
                            {{-- <button type="button" class="balance-link">
                                <span>Ver extrato</span>
                                <i class="bi bi-eye"></i>
                            </button> --}}
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card dashboard-card action-card h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 text-uppercase text-muted mb-3">Depositar</h2>
                            <label for="depositAmount" class="form-label small">Valor</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text">R$</span>
                                <input id="depositAmount" type="number" step="0.01" min="0.01" class="form-control form-control-lg" placeholder="0,00">
                            </div>
                            <button id="btnDeposit" class="btn btn-primary w-100 btn-action" data-original-label="Depositar">
                                <i class="bi bi-download me-2"></i>Depositar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card dashboard-card action-card h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 text-uppercase text-muted mb-3">Transferir</h2>
                            <label for="transferReceiver" class="form-label small">ID do usuario destino</label>
                            <input id="transferReceiver" type="number" min="1" class="form-control form-control-lg mb-3" placeholder="ID do usuario destino">
                            <label for="transferAmount" class="form-label small">Valor</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text">R$</span>
                                <input id="transferAmount" type="number" step="0.01" min="0.01" class="form-control form-control-lg" placeholder="0,00">
                            </div>
                            <button id="btnTransfer" class="btn btn-success w-100 btn-action" data-original-label="Transferir">
                                <i class="bi bi-send me-2"></i>Transferir
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <section id="historySection" class="card dashboard-card history-card">
                <div class="card-body p-4">
                    <div class="history-header">
                        <div class="history-title-wrap">
                            <div class="history-title-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="history-copy">
                                <h2 class="h4 mb-1">Historico de transacoes</h2>
                                <p class="text-muted mb-0">Ultimas movimentacoes da sua carteira</p>
                            </div>
                        </div>
                        <div id="txFilterSummary" class="history-summary">Sem filtros ativos</div>
                    </div>

                    <div class="history-toolbar">
                        <div class="history-filters">
                            <select id="txFilterType" class="form-select form-select-sm history-select" style="width: auto;">
                                <option value="">Tipo: todos</option>
                                <option value="deposit">Deposito</option>
                                <option value="transfer">Transferencia</option>
                                <option value="reversal">Reversao</option>
                            </select>
                            <select id="txFilterStatus" class="form-select form-select-sm history-select" style="width: auto;">
                                <option value="">Status: todos</option>
                                <option value="pending">Pendente</option>
                                <option value="completed">Concluido</option>
                                <option value="reversed">Revertido</option>
                            </select>
                            <input id="txFilterDate" type="date" class="form-control form-control-sm history-input" style="width: auto;" title="Filtrar por data">
                            <button id="btnClearFilters" class="btn btn-sm btn-outline-secondary history-secondary-btn">
                                <i class="bi bi-funnel me-2"></i>Limpar filtros
                            </button>
                        </div>
                        <div class="history-toolbar-right">
                            <label for="txLimit" class="small text-muted mb-0 history-limit-label">Por pagina</label>
                            <select id="txLimit" class="form-select form-select-sm history-select history-limit-select" style="width: auto;">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <div class="history-pagination-bar">
                        <div class="history-page-actions">
                            <span id="txPageInfo" class="history-page-pill">Pagina 1</span>
                            <button id="btnPrevPage" class="btn btn-sm btn-outline-secondary history-nav-btn" disabled>Anterior</button>
                            <button id="btnNextPage" class="btn btn-sm btn-outline-secondary history-nav-btn" disabled>Proxima</button>
                        </div>
                        <div class="history-refresh-wrap">
                            <button id="btnRefresh" class="btn btn-sm btn-outline-secondary history-secondary-btn" data-original-label="Atualizar">
                                <i class="bi bi-arrow-clockwise me-2"></i>Atualizar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive history-table-wrap">
                        <table class="table table-hover table-borderless table-striped align-middle mb-0 history-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th class="text-end">Valor</th>
                                <th>Sender</th>
                                <th>Receiver</th>
                                <th>Criada em</th>
                                <th>Acoes</th>
                            </tr>
                            </thead>
                            <tbody id="txTableBody">
                            <tr><td colspan="8" class="text-center text-muted py-4">Sem transacoes.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <footer class="app-footer">© 2026 Wallet. Todos os direitos reservados.</footer>
        </main>
    </div>
</div>

<div id="globalSpinner" class="position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1055;">
    <div class="spinner-border text-light" role="status"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.4/dist/echo.iife.js"></script>
<script>
    const API_BASE = `${window.location.origin}/api/v1`;
    const TOKEN_KEY = 'wallet_token';
    const REVERB_CONFIG = {
        key: @json(config('broadcasting.connections.reverb.key', '')),
        host: @json(config('broadcasting.connections.reverb.options.host', 'localhost')),
        port: Number(@json((int) config('broadcasting.connections.reverb.options.port', 8081))),
        scheme: @json(config('broadcasting.connections.reverb.options.scheme', 'http')),
    };
    const BROWSER_TIMEZONE = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

    let currentWalletId = null;
    let currentUserId = null;
    let currentTransferIdempotencyKey = null;
    let currentTransactions = [];
    let echoInstance = null;
    const transactionsPage = {
        limit: 20,
        offset: 0,
        lastFetchedCount: 0,
    };
    const transactionsFilters = {
        type: '',
        status: '',
        date: '',
    };

    function getTransferIdempotencyKey() {
        if (!currentTransferIdempotencyKey) {
            currentTransferIdempotencyKey = window.crypto?.randomUUID
                ? crypto.randomUUID()
                : `transfer-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        }

        return currentTransferIdempotencyKey;
    }

    function showLoading(show) {
        $('#globalSpinner').toggleClass('d-none', !show).toggleClass('d-flex', show);
    }

    function showAlert(type, message) {
        const box = $('#alertBox');
        box.removeClass('d-none alert-success alert-danger alert-warning alert-info')
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

    function setRealtimeStatus(connected) {
        const label = connected ? 'Tempo real: conectado' : 'Tempo real: indisponivel';
        const css = connected ? 'text-success' : 'text-warning';

        $('#realtimeStatus')
            .text(label)
            .removeClass('text-success text-warning text-muted')
            .addClass(css);
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
            deposit: { label: 'Deposito', class: 'badge-type-deposit', icon: 'bi-arrow-down' },
            transfer: { label: 'Transferencia', class: 'badge-type-transfer', icon: 'bi-arrow-up-right' },
            reversal: { label: 'Reversao', class: 'badge-type-reversal', icon: 'bi-arrow-counterclockwise' }
        };

        const item = mapping[type] || { label: type, class: 'badge bg-secondary', icon: 'bi-three-dots' };
        return `<span class="badge ${item.class} rounded-pill py-2 px-3"><i class="bi ${item.icon} me-1"></i>${item.label}</span>`;
    }

    function getStatusBadge(status) {
        const mapping = {
            completed: { label: 'Concluido', class: 'badge-status-completed' },
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

    function renderTransactions(transactions) {
        const tbody = $('#txTableBody');
        tbody.empty();

        if (!transactions.length) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted py-4">Sem transacoes.</td></tr>');
            return;
        }

        transactions.forEach((tx) => {
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

    function upsertTransactions(transactions) {
        if (!Array.isArray(transactions) || !transactions.length) {
            return;
        }

        const map = new Map(currentTransactions.map((tx) => [Number(tx.id), tx]));

        transactions.forEach((tx) => {
            map.set(Number(tx.id), {
                ...map.get(Number(tx.id)),
                ...tx,
            });
        });

        currentTransactions = Array.from(map.values())
            .sort((a, b) => Number(b.id) - Number(a.id))
            .slice(0, transactionsPage.limit);
        transactionsPage.lastFetchedCount = currentTransactions.length;
        renderTransactions(currentTransactions);
        updateTransactionsPaginationControls();
    }

    function updateTransactionsPaginationControls() {
        const currentPage = Math.floor(transactionsPage.offset / transactionsPage.limit) + 1;
        $('#txPageInfo').text(`Pagina ${currentPage}`);
        $('#txLimit').val(String(transactionsPage.limit));
        $('#btnPrevPage').prop('disabled', transactionsPage.offset === 0);
        $('#btnNextPage').prop('disabled', transactionsPage.lastFetchedCount < transactionsPage.limit);
        $('#txFilterType').val(transactionsFilters.type);
        $('#txFilterStatus').val(transactionsFilters.status);
        $('#txFilterDate').val(transactionsFilters.date);
        updateTransactionsFilterState();
    }

    function updateTransactionsFilterState() {
        const filters = [];
        const typeLabels = {
            deposit: 'Deposito',
            transfer: 'Transferencia',
            reversal: 'Reversao',
        };
        const statusLabels = {
            pending: 'Pendente',
            completed: 'Concluido',
            reversed: 'Revertido',
        };

        if (transactionsFilters.type) {
            filters.push(`Tipo: ${typeLabels[transactionsFilters.type] ?? transactionsFilters.type}`);
        }

        if (transactionsFilters.status) {
            filters.push(`Status: ${statusLabels[transactionsFilters.status] ?? transactionsFilters.status}`);
        }

        if (transactionsFilters.date) {
            filters.push(`Data: ${transactionsFilters.date}`);
        }

        $('#txFilterSummary')
            .text(filters.length ? filters.join(' | ') : 'Sem filtros ativos')
            .toggleClass('is-active', filters.length > 0);

        $('#txFilterType').toggleClass('is-active', Boolean(transactionsFilters.type));
        $('#txFilterStatus').toggleClass('is-active', Boolean(transactionsFilters.status));
        $('#txFilterDate').toggleClass('is-active', Boolean(transactionsFilters.date));
        $('#btnClearFilters').prop('disabled', filters.length === 0);
    }

    function hasActiveTransactionsFilters() {
        return Boolean(transactionsFilters.type || transactionsFilters.status || transactionsFilters.date);
    }

    function buildTransactionsQuery() {
        const params = new URLSearchParams();
        params.set('limit', String(transactionsPage.limit));
        params.set('offset', String(transactionsPage.offset));

        if (transactionsFilters.type) {
            params.set('type', transactionsFilters.type);
        }

        if (transactionsFilters.status) {
            params.set('status', transactionsFilters.status);
        }

        if (transactionsFilters.date) {
            params.set('date', transactionsFilters.date);
            params.set('timezone', BROWSER_TIMEZONE);
        }

        return `/transactions?${params.toString()}`;
    }

    function applyRealtimePayload(payload) {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        if (payload.wallet) {
            $('#walletBalance').text(formatMoney(payload.wallet.balance));
            currentWalletId = payload.wallet.id ?? currentWalletId;
            currentUserId = payload.wallet.user_id ?? currentUserId;
            $('#currentUserBadge').text(`ID ${currentUserId ?? '-'}`);
        }

        if (transactionsPage.offset === 0 && !hasActiveTransactionsFilters()) {
            upsertTransactions(payload.transactions ?? []);
        }
    }

    async function loadWallet() {
        const res = await apiRequest('GET', '/wallet');
        $('#walletBalance').text(formatMoney(res.data.balance));
        currentUserId = res.data.user_id ?? null;
        $('#currentUserBadge').text(`ID ${res.data.user_id ?? '-'}`);
        currentWalletId = res.data.id ?? null;
    }

    async function loadTransactions() {
        const query = buildTransactionsQuery();
        const res = await apiRequest('GET', query);
        currentTransactions = Array.isArray(res.data) ? res.data : [];
        transactionsPage.lastFetchedCount = currentTransactions.length;
        renderTransactions(currentTransactions);
        updateTransactionsPaginationControls();
    }

    async function loadDashboard() {
        await loadWallet();
        await loadTransactions();
    }

    function connectDashboardRealtime() {
        if (echoInstance || !currentUserId || !token()) {
            return;
        }

        if (!window.Echo || !window.Pusher || !REVERB_CONFIG.key) {
            setRealtimeStatus(false);
            return;
        }

        const EchoCtor = window.Echo.default ?? window.Echo;
        const isTls = REVERB_CONFIG.scheme === 'https';

        echoInstance = new EchoCtor({
            broadcaster: 'reverb',
            key: REVERB_CONFIG.key,
            wsHost: REVERB_CONFIG.host,
            wsPort: REVERB_CONFIG.port,
            wssPort: REVERB_CONFIG.port,
            forceTLS: isTls,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: `${API_BASE}/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${token()}`,
                },
            },
        });

        echoInstance.private(`private-user.${currentUserId}`)
            .listen('.wallet.dashboard.updated', function (payload) {
                applyRealtimePayload(payload);
            });

        const pusher = echoInstance.connector?.pusher;

        if (pusher?.connection) {
            pusher.connection.bind('state_change', function (states) {
                const connected = states.current === 'connected';
                setRealtimeStatus(connected);
            });
        }
    }

    function disconnectDashboardRealtime() {
        if (!echoInstance) {
            return;
        }

        echoInstance.disconnect();
        echoInstance = null;
        setRealtimeStatus(false);
    }

    async function onLogout() {
        hideAlert();
        showLoading(true);
        toggleButtonLoading('#btnLogout', true);
        try {
            await apiRequest('POST', '/logout');
        } catch (_) {
        } finally {
            disconnectDashboardRealtime();
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
            showAlert('info', 'Deposito enviado. O dashboard sera atualizado automaticamente ao concluir.');
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
                amount: Number($('#transferAmount').val()),
                idempotency_key: getTransferIdempotencyKey(),
            });
            currentTransferIdempotencyKey = null;
            $('#transferReceiver').val('');
            $('#transferAmount').val('');
            showAlert('info', 'Transferencia enviada. O dashboard sera atualizado automaticamente ao concluir.');
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
            showAlert('info', `Reversao ${id} enviada. O dashboard sera atualizado automaticamente ao concluir.`);
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
        }
    }

    function syncFiltersFromInputs() {
        transactionsFilters.type = String($('#txFilterType').val() || '');
        transactionsFilters.status = String($('#txFilterStatus').val() || '');
        transactionsFilters.date = String($('#txFilterDate').val() || '');
    }

    function resetTransactionsFilters() {
        transactionsFilters.type = '';
        transactionsFilters.status = '';
        transactionsFilters.date = '';
        $('#txFilterType').val('');
        $('#txFilterStatus').val('');
        $('#txFilterDate').val('');
    }

    function refreshTransactionsWithButtonState() {
        toggleButtonLoading('#btnRefresh', true);
        loadTransactions()
            .catch(() => showAlert('danger', 'Nao foi possivel carregar as transacoes.'))
            .finally(() => toggleButtonLoading('#btnRefresh', false));
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
        $('#txFilterType, #txFilterStatus, #txFilterDate').on('change', function () {
            syncFiltersFromInputs();
            transactionsPage.offset = 0;
            refreshTransactionsWithButtonState();
        });
        $('#btnClearFilters').on('click', function () {
            resetTransactionsFilters();
            transactionsPage.offset = 0;
            refreshTransactionsWithButtonState();
        });
        $('#txLimit').on('change', function () {
            const selected = Number($(this).val());
            transactionsPage.limit = Number.isInteger(selected) && selected >= 1 ? selected : 20;
            transactionsPage.offset = 0;
            refreshTransactionsWithButtonState();
        });
        $('#btnPrevPage').on('click', function () {
            if (transactionsPage.offset === 0) {
                return;
            }

            transactionsPage.offset = Math.max(0, transactionsPage.offset - transactionsPage.limit);
            refreshTransactionsWithButtonState();
        });
        $('#btnNextPage').on('click', function () {
            if (transactionsPage.lastFetchedCount < transactionsPage.limit) {
                return;
            }

            const previousOffset = transactionsPage.offset;
            transactionsPage.offset += transactionsPage.limit;
            toggleButtonLoading('#btnRefresh', true);
            loadTransactions()
                .then(() => {
                    if (!currentTransactions.length) {
                        transactionsPage.offset = previousOffset;
                        return loadTransactions().then(() => showAlert('info', 'Voce ja esta na ultima pagina.'));
                    }
                })
                .catch(() => {
                    transactionsPage.offset = previousOffset;
                    showAlert('danger', 'Nao foi possivel carregar as transacoes.');
                })
                .finally(() => toggleButtonLoading('#btnRefresh', false));
        });
        $(document).on('click', '.btn-reverse', function () {
            const button = $(this);
            const id = button.data('id');
            toggleButtonLoading(button, true);
            onReverse(id).finally(() => toggleButtonLoading(button, false));
        });

        showLoading(true);
        setRealtimeStatus(false);
        try {
            await loadDashboard();
            connectDashboardRealtime();
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
