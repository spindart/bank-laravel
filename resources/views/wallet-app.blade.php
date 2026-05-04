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
            <a href="#savingsSection" class="sidebar-link">
                <i class="bi bi-piggy-bank"></i>
                <span>Caixinhas</span>
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
                                    <div id="walletBalance" class="display-5 fw-semibold text-success balance-value" aria-live="polite">R$ 0,00</div>
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

            <section id="savingsSection" class="card dashboard-card savings-card mb-4">
                <div class="card-body p-4">
                    <div class="savings-header">
                        <div class="history-title-wrap">
                            <div class="history-title-icon savings-title-icon">
                                <i class="bi bi-piggy-bank"></i>
                            </div>
                            <div class="history-copy">
                                <h2 class="h4 mb-1">Caixinhas</h2>
                                <p class="text-muted mb-0">Separe seu saldo disponivel por objetivos</p>
                            </div>
                        </div>
                        <button id="btnOpenSavingsCreate" type="button" class="btn btn-primary savings-new-btn" data-original-label="Nova caixinha">
                            <i class="bi bi-plus-lg me-2"></i>Nova caixinha
                        </button>
                    </div>

                    <div class="savings-summary-grid">
                        <div class="savings-summary-item">
                            <span>Total guardado</span>
                            <strong id="savingsTotalSaved">R$ 0,00</strong>
                        </div>
                        <div class="savings-summary-item">
                            <span>Ativas</span>
                            <strong id="savingsActiveCount">0</strong>
                        </div>
                        <div class="savings-summary-item">
                            <span>Concluidas</span>
                            <strong id="savingsCompletedCount">0</strong>
                        </div>
                    </div>

                    <div id="savingsEmptyState" class="savings-empty d-none">
                        <div class="savings-empty-icon"><i class="bi bi-piggy-bank"></i></div>
                        <h3>Nenhuma caixinha ainda</h3>
                        <p>Crie um objetivo para separar parte do saldo sem tirar dinheiro da sua carteira.</p>
                    </div>

                    <div id="savingsGrid" class="savings-grid"></div>
                </div>
            </section>

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
                                <option value="savings_deposit">Guardar em caixinha</option>
                                <option value="savings_withdraw">Resgate de caixinha</option>
                                <option value="savings_cancel_refund">Cancelamento de caixinha</option>
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

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="feedbackToast" class="toast feedback-toast border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body">
            <div id="feedbackToastIcon" class="feedback-toast-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <div id="feedbackToastMessage" class="feedback-toast-message">Operacao concluida.</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
    </div>
</div>

<div class="modal fade" id="transferConfirmModal" tabindex="-1" aria-labelledby="transferConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content transfer-confirm-modal">
            <div class="modal-header">
                <h2 id="transferConfirmTitle" class="modal-title h5">Confirmar transferencia</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="transfer-confirm-summary">
                    <div>
                        <span class="transfer-confirm-label">Destino</span>
                        <strong id="confirmTransferReceiver">Usuario -</strong>
                    </div>
                    <div>
                        <span class="transfer-confirm-label">Valor</span>
                        <strong id="confirmTransferAmount">R$ 0,00</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button id="btnConfirmTransfer" type="button" class="btn btn-success" data-original-label="Confirmar">
                    <i class="bi bi-send me-2"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="savingsFormModal" tabindex="-1" aria-labelledby="savingsFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content savings-modal">
            <form id="savingsForm">
                <div class="modal-header">
                    <h2 id="savingsFormTitle" class="modal-title h5">Nova caixinha</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input id="savingsFormId" type="hidden">
                    <div class="mb-3">
                        <label for="savingsName" class="form-label small">Nome</label>
                        <input id="savingsName" type="text" class="form-control form-control-lg" maxlength="80" required>
                    </div>
                    <div class="mb-3">
                        <label for="savingsDescription" class="form-label small">Descricao</label>
                        <textarea id="savingsDescription" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="savingsTargetAmount" class="form-label small">Meta</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input id="savingsTargetAmount" type="number" step="0.01" min="0.01" class="form-control form-control-lg" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="savingsTargetDate" class="form-label small">Data alvo</label>
                            <input id="savingsTargetDate" type="date" class="form-control form-control-lg">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="savingsIcon" class="form-label small">Icone</label>
                        <select id="savingsIcon" class="form-select form-select-lg">
                            <option value="bi-piggy-bank">Cofrinho</option>
                            <option value="bi-shield-check">Reserva</option>
                            <option value="bi-laptop">Notebook</option>
                            <option value="bi-airplane">Viagem</option>
                            <option value="bi-receipt">Impostos</option>
                            <option value="bi-star">Meta pessoal</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button id="btnSaveSavingsBox" type="submit" class="btn btn-primary" data-original-label="Salvar">
                        <i class="bi bi-check-lg me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="savingsAmountModal" tabindex="-1" aria-labelledby="savingsAmountTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content savings-modal">
            <form id="savingsAmountForm">
                <div class="modal-header">
                    <h2 id="savingsAmountTitle" class="modal-title h5">Guardar dinheiro</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input id="savingsAmountBoxId" type="hidden">
                    <input id="savingsAmountAction" type="hidden">
                    <div class="savings-operation-summary">
                        <span id="savingsAmountBoxName">Caixinha</span>
                        <strong id="savingsAmountBoxBalance">R$ 0,00 guardados</strong>
                    </div>
                    <label for="savingsOperationAmount" class="form-label small">Valor</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input id="savingsOperationAmount" type="number" step="0.01" min="0.01" class="form-control form-control-lg" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button id="btnConfirmSavingsAmount" type="submit" class="btn btn-primary" data-original-label="Confirmar">
                        <i class="bi bi-check-lg me-2"></i>Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="savingsDetailsModal" tabindex="-1" aria-labelledby="savingsDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content savings-modal">
            <div class="modal-header">
                <h2 id="savingsDetailsTitle" class="modal-title h5">Detalhes da caixinha</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div id="savingsDetailsBody" class="modal-body"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="savingsCancelModal" tabindex="-1" aria-labelledby="savingsCancelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content savings-modal">
            <div class="modal-header">
                <h2 id="savingsCancelTitle" class="modal-title h5">Cancelar caixinha</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input id="savingsCancelBoxId" type="hidden">
                <p class="mb-0">O saldo guardado sera devolvido automaticamente para o saldo disponivel.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                <button id="btnConfirmSavingsCancel" type="button" class="btn btn-outline-danger" data-original-label="Cancelar caixinha">
                    <i class="bi bi-archive me-2"></i>Cancelar caixinha
                </button>
            </div>
        </div>
    </div>
</div>

<div id="globalSpinner" class="position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1055;">
    <div class="spinner-border text-light" role="status"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
    let currentSavingsBoxes = [];
    let echoInstance = null;
    let lastWalletBalance = null;
    let transferConfirmModal = null;
    let savingsFormModal = null;
    let savingsAmountModal = null;
    let savingsDetailsModal = null;
    let savingsCancelModal = null;
    let feedbackToast = null;
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

    function showToast(type, message) {
        const toastEl = document.getElementById('feedbackToast');
        const icon = $('#feedbackToastIcon');
        const iconClass = type === 'success'
            ? 'bi-check-lg'
            : type === 'danger'
                ? 'bi-exclamation-triangle'
                : 'bi-info-lg';

        $('#feedbackToastMessage').text(message);
        $('#feedbackToast')
            .removeClass('toast-success toast-danger toast-info')
            .addClass(`toast-${type}`);
        icon.html(`<i class="bi ${iconClass}"></i>`);

        feedbackToast = feedbackToast || bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4200 });
        feedbackToast.show();
    }

    function showAlert(type, message) {
        const toastType = type === 'danger' ? 'danger' : type === 'success' ? 'success' : 'info';
        showToast(toastType, message);
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

    function setWalletBalance(value, options = {}) {
        const numericBalance = Number(value || 0);
        const shouldHighlight = options.highlight !== false
            && lastWalletBalance !== null
            && numericBalance !== lastWalletBalance;

        $('#walletBalance')
            .removeClass('skeleton skeleton-text balance-changed')
            .text(formatMoney(numericBalance));

        if (shouldHighlight) {
            const balance = $('#walletBalance');
            balance.addClass('balance-changed');
            window.setTimeout(() => balance.removeClass('balance-changed'), 1300);
        }

        lastWalletBalance = numericBalance;
    }

    function renderTransactionSkeletonRows() {
        const rows = Array.from({ length: 5 }, () => `
            <tr class="skeleton-row">
                <td><span class="skeleton skeleton-pill"></span></td>
                <td><span class="skeleton skeleton-badge"></span></td>
                <td><span class="skeleton skeleton-badge"></span></td>
                <td class="text-end"><span class="skeleton skeleton-text-sm ms-auto"></span></td>
                <td><span class="skeleton skeleton-text-xs"></span></td>
                <td><span class="skeleton skeleton-text-xs"></span></td>
                <td><span class="skeleton skeleton-text"></span></td>
                <td><span class="skeleton skeleton-button"></span></td>
            </tr>
        `).join('');

        $('#txTableBody').html(rows);
    }

    function setDashboardSkeleton(loading) {
        $('#walletBalance')
            .toggleClass('skeleton skeleton-text', loading)
            .text(loading ? '' : $('#walletBalance').text());

        if (loading) {
            renderTransactionSkeletonRows();
        }
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
            reversal: { label: 'Reversao', class: 'badge-type-reversal', icon: 'bi-arrow-counterclockwise' },
            savings_deposit: { label: 'Caixinha', class: 'badge-type-savings-deposit', icon: 'bi-piggy-bank' },
            savings_withdraw: { label: 'Resgate', class: 'badge-type-savings-withdraw', icon: 'bi-arrow-down-up' },
            savings_cancel_refund: { label: 'Cancelamento', class: 'badge-type-savings-cancel', icon: 'bi-archive' }
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

        if (!btn.data('original-html')) {
            btn.data('original-html', btn.html());
        }

        if (loading) {
            btn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${label}`);
        } else {
            btn.prop('disabled', false).html(btn.data('original-html'));
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

    function getSavingsStatusBadge(status) {
        const mapping = {
            active: { label: 'Ativa', class: 'savings-status-active' },
            completed: { label: 'Concluida', class: 'savings-status-completed' },
            cancelled: { label: 'Cancelada', class: 'savings-status-cancelled' },
            archived: { label: 'Arquivada', class: 'savings-status-cancelled' },
        };
        const item = mapping[status] || { label: status, class: 'savings-status-cancelled' };

        return `<span class="savings-status ${item.class}">${item.label}</span>`;
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(`${value}T00:00:00`);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
    }

    function renderSavingsBoxes(boxes, summary = {}) {
        currentSavingsBoxes = Array.isArray(boxes) ? boxes : [];
        $('#savingsTotalSaved').text(formatMoney(summary.total_saved || 0));
        $('#savingsActiveCount').text(summary.active_count || 0);
        $('#savingsCompletedCount').text(summary.completed_count || 0);
        $('#savingsEmptyState').toggleClass('d-none', currentSavingsBoxes.length > 0);

        const grid = $('#savingsGrid');
        grid.empty();

        currentSavingsBoxes.forEach((box) => {
            const progress = Math.min(100, Number(box.progress_percent || 0));
            const active = ['active', 'completed'].includes(box.status);
            const completedMessage = box.status === 'completed'
                ? '<div class="savings-completed-message"><i class="bi bi-stars me-1"></i>Meta concluida. Parabens!</div>'
                : '';

            grid.append(`
                <article class="savings-box-card" data-id="${box.id}">
                    <div class="savings-box-top">
                        <div class="savings-box-icon"><i class="bi ${box.icon || 'bi-piggy-bank'}"></i></div>
                        ${getSavingsStatusBadge(box.status)}
                    </div>
                    <h3>${box.name}</h3>
                    <p>${box.description || 'Sem descricao.'}</p>
                    ${completedMessage}
                    <div class="savings-values">
                        <div><span>Guardado</span><strong>${formatMoney(box.current_amount)}</strong></div>
                        <div><span>Meta</span><strong>${formatMoney(box.target_amount)}</strong></div>
                    </div>
                    <div class="savings-progress-wrap">
                        <div class="savings-progress-meta">
                            <span>${progress.toFixed(0)}%</span>
                            <span>Restam ${formatMoney(box.remaining_amount)}</span>
                        </div>
                        <div class="progress savings-progress" role="progressbar" aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: ${progress}%"></div>
                        </div>
                    </div>
                    <div class="savings-target-date"><i class="bi bi-calendar-event me-1"></i>Data alvo: ${formatDate(box.target_date)}</div>
                    <div class="savings-actions">
                        <button class="btn btn-sm btn-primary btn-savings-deposit" ${active ? '' : 'disabled'} data-id="${box.id}" data-original-label="Guardar"><i class="bi bi-plus-lg me-1"></i>Guardar</button>
                        <button class="btn btn-sm btn-outline-success btn-savings-withdraw" ${active ? '' : 'disabled'} data-id="${box.id}" data-original-label="Resgatar"><i class="bi bi-arrow-down-up me-1"></i>Resgatar</button>
                        <button class="btn btn-sm btn-outline-secondary btn-savings-details" data-id="${box.id}" data-original-label="Detalhes"><i class="bi bi-eye me-1"></i>Detalhes</button>
                        <button class="btn btn-sm btn-outline-secondary btn-savings-edit" ${active ? '' : 'disabled'} data-id="${box.id}" data-original-label="Editar"><i class="bi bi-pencil me-1"></i>Editar</button>
                        <button class="btn btn-sm btn-outline-danger btn-savings-cancel" ${active ? '' : 'disabled'} data-id="${box.id}" data-original-label="Cancelar"><i class="bi bi-archive me-1"></i>Cancelar</button>
                    </div>
                </article>
            `);
        });
    }

    async function loadSavingsBoxes() {
        const res = await apiRequest('GET', '/savings-boxes');
        renderSavingsBoxes(res.data.items || [], res.data.summary || {});
    }

    function findSavingsBox(id) {
        return currentSavingsBoxes.find((box) => Number(box.id) === Number(id));
    }

    function openSavingsForm(box = null) {
        $('#savingsFormTitle').text(box ? 'Editar caixinha' : 'Nova caixinha');
        $('#savingsFormId').val(box?.id || '');
        $('#savingsName').val(box?.name || '');
        $('#savingsDescription').val(box?.description || '');
        $('#savingsTargetAmount').val(box?.target_amount || '');
        $('#savingsTargetDate').val(box?.target_date || '');
        $('#savingsIcon').val(box?.icon || 'bi-piggy-bank');
        savingsFormModal = savingsFormModal || bootstrap.Modal.getOrCreateInstance(document.getElementById('savingsFormModal'));
        savingsFormModal.show();
    }

    function openSavingsAmount(action, box) {
        $('#savingsAmountAction').val(action);
        $('#savingsAmountBoxId').val(box.id);
        $('#savingsOperationAmount').val('');
        $('#savingsAmountTitle').text(action === 'deposit' ? 'Guardar dinheiro' : 'Resgatar dinheiro');
        $('#savingsAmountBoxName').text(box.name);
        $('#savingsAmountBoxBalance').text(`${formatMoney(box.current_amount)} guardados`);
        savingsAmountModal = savingsAmountModal || bootstrap.Modal.getOrCreateInstance(document.getElementById('savingsAmountModal'));
        savingsAmountModal.show();
    }

    async function openSavingsDetails(id) {
        const res = await apiRequest('GET', `/savings-boxes/${id}`);
        const box = res.data;
        const movements = Array.isArray(box.movements) ? box.movements : [];
        const rows = movements.length
            ? movements.map((movement) => `
                <tr>
                    <td>${movement.type}</td>
                    <td class="text-end">${formatMoney(movement.amount)}</td>
                    <td class="text-end">${formatMoney(movement.balance_before)}</td>
                    <td class="text-end">${formatMoney(movement.balance_after)}</td>
                    <td>${formatDateTime(movement.created_at)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="5" class="text-center text-muted py-4">Sem movimentacoes.</td></tr>';

        $('#savingsDetailsTitle').text(box.name);
        $('#savingsDetailsBody').html(`
            <div class="savings-details-summary">
                <div><span>Guardado</span><strong>${formatMoney(box.current_amount)}</strong></div>
                <div><span>Meta</span><strong>${formatMoney(box.target_amount)}</strong></div>
                <div><span>Restante</span><strong>${formatMoney(box.remaining_amount)}</strong></div>
                <div><span>Status</span><strong>${box.status}</strong></div>
                <div><span>Criada em</span><strong>${formatDateTime(box.created_at)}</strong></div>
                <div><span>Atualizada em</span><strong>${formatDateTime(box.updated_at)}</strong></div>
            </div>
            <div class="progress savings-progress my-3">
                <div class="progress-bar" style="width: ${Math.min(100, Number(box.progress_percent || 0))}%"></div>
            </div>
            <div class="table-responsive history-table-wrap mt-3">
                <table class="table table-hover table-borderless align-middle mb-0 history-table">
                    <thead><tr><th>Tipo</th><th class="text-end">Valor</th><th class="text-end">Antes</th><th class="text-end">Depois</th><th>Criada em</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `);
        savingsDetailsModal = savingsDetailsModal || bootstrap.Modal.getOrCreateInstance(document.getElementById('savingsDetailsModal'));
        savingsDetailsModal.show();
    }

    async function saveSavingsBox() {
        const id = $('#savingsFormId').val();
        const payload = {
            name: $('#savingsName').val(),
            description: $('#savingsDescription').val() || null,
            target_amount: Number($('#savingsTargetAmount').val()),
            target_date: $('#savingsTargetDate').val() || null,
            icon: $('#savingsIcon').val() || null,
        };

        await apiRequest(id ? 'PUT' : 'POST', id ? `/savings-boxes/${id}` : '/savings-boxes', payload);
        savingsFormModal?.hide();
        showAlert('success', id ? 'Caixinha atualizada.' : 'Caixinha criada.');
        await loadSavingsBoxes();
    }

    async function submitSavingsAmount() {
        const id = $('#savingsAmountBoxId').val();
        const action = $('#savingsAmountAction').val();
        const endpoint = action === 'withdraw' ? 'withdraw' : 'deposit';

        await apiRequest('POST', `/savings-boxes/${id}/${endpoint}`, {
            amount: Number($('#savingsOperationAmount').val()),
        });
        savingsAmountModal?.hide();
        showAlert('success', action === 'withdraw' ? 'Resgate concluido.' : 'Dinheiro guardado.');
        await loadWallet();
        await loadSavingsBoxes();
        await loadTransactions();
    }

    async function cancelSavingsBox() {
        const id = $('#savingsCancelBoxId').val();

        await apiRequest('DELETE', `/savings-boxes/${id}`);
        savingsCancelModal?.hide();
        showAlert('success', 'Caixinha cancelada e saldo devolvido.');
        await loadWallet();
        await loadSavingsBoxes();
        await loadTransactions();
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
            savings_deposit: 'Guardar em caixinha',
            savings_withdraw: 'Resgate de caixinha',
            savings_cancel_refund: 'Cancelamento de caixinha',
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
            setWalletBalance(payload.wallet.balance);
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
        setWalletBalance(res.data.balance, { highlight: false });
        currentUserId = res.data.user_id ?? null;
        $('#currentUserBadge').text(`ID ${res.data.user_id ?? '-'}`);
        currentWalletId = res.data.id ?? null;
    }

    async function loadTransactions() {
        const query = buildTransactionsQuery();
        const previousTransactions = currentTransactions;
        renderTransactionSkeletonRows();
        try {
            const res = await apiRequest('GET', query);
            currentTransactions = Array.isArray(res.data) ? res.data : [];
            transactionsPage.lastFetchedCount = currentTransactions.length;
            renderTransactions(currentTransactions);
            updateTransactionsPaginationControls();
        } catch (error) {
            renderTransactions(previousTransactions);
            updateTransactionsPaginationControls();
            throw error;
        }
    }

    async function loadDashboard() {
        await loadWallet();
        await loadSavingsBoxes();
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
            showAlert('success', 'Deposito enviado. O dashboard sera atualizado automaticamente ao concluir.');
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
            showAlert('success', 'Transferencia enviada. O dashboard sera atualizado automaticamente ao concluir.');
        } catch (xhr) {
            showAlert('danger', parseError(xhr));
        } finally {
            showLoading(false);
            toggleButtonLoading('#btnTransfer', false);
            toggleButtonLoading('#btnConfirmTransfer', false);
        }
    }

    function openTransferConfirm() {
        hideAlert();

        const receiver = Number($('#transferReceiver').val());
        const amount = Number($('#transferAmount').val());

        if (!Number.isInteger(receiver) || receiver < 1) {
            showAlert('danger', 'Informe um usuario destino valido.');
            return;
        }

        if (!Number.isFinite(amount) || amount <= 0) {
            showAlert('danger', 'Informe um valor valido para transferir.');
            return;
        }

        $('#confirmTransferReceiver').text(`Usuario ${receiver}`);
        $('#confirmTransferAmount').text(formatMoney(amount));
        transferConfirmModal = transferConfirmModal || bootstrap.Modal.getOrCreateInstance(document.getElementById('transferConfirmModal'));
        transferConfirmModal.show();
    }

    async function onReverse(id) {
        hideAlert();
        showLoading(true);
        try {
            await apiRequest('POST', `/reverse/${id}`);
            showAlert('success', `Reversao ${id} enviada. O dashboard sera atualizado automaticamente ao concluir.`);
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
        $('#btnTransfer').on('click', openTransferConfirm);
        $('#btnOpenSavingsCreate').on('click', () => openSavingsForm());
        $('#savingsForm').on('submit', function (event) {
            event.preventDefault();
            toggleButtonLoading('#btnSaveSavingsBox', true);
            saveSavingsBox()
                .catch((xhr) => showAlert('danger', parseError(xhr)))
                .finally(() => toggleButtonLoading('#btnSaveSavingsBox', false));
        });
        $('#savingsAmountForm').on('submit', function (event) {
            event.preventDefault();
            toggleButtonLoading('#btnConfirmSavingsAmount', true);
            submitSavingsAmount()
                .catch((xhr) => showAlert('danger', parseError(xhr)))
                .finally(() => toggleButtonLoading('#btnConfirmSavingsAmount', false));
        });
        $('#btnConfirmSavingsCancel').on('click', function () {
            toggleButtonLoading('#btnConfirmSavingsCancel', true);
            cancelSavingsBox()
                .catch((xhr) => showAlert('danger', parseError(xhr)))
                .finally(() => toggleButtonLoading('#btnConfirmSavingsCancel', false));
        });
        $(document).on('click', '.btn-savings-deposit', function () {
            const box = findSavingsBox($(this).data('id'));
            if (box) openSavingsAmount('deposit', box);
        });
        $(document).on('click', '.btn-savings-withdraw', function () {
            const box = findSavingsBox($(this).data('id'));
            if (box) openSavingsAmount('withdraw', box);
        });
        $(document).on('click', '.btn-savings-edit', function () {
            const box = findSavingsBox($(this).data('id'));
            if (box) openSavingsForm(box);
        });
        $(document).on('click', '.btn-savings-details', function () {
            const button = $(this);
            toggleButtonLoading(button, true);
            openSavingsDetails(button.data('id'))
                .catch((xhr) => showAlert('danger', parseError(xhr)))
                .finally(() => toggleButtonLoading(button, false));
        });
        $(document).on('click', '.btn-savings-cancel', function () {
            $('#savingsCancelBoxId').val($(this).data('id'));
            savingsCancelModal = savingsCancelModal || bootstrap.Modal.getOrCreateInstance(document.getElementById('savingsCancelModal'));
            savingsCancelModal.show();
        });
        $('#btnConfirmTransfer').on('click', function () {
            toggleButtonLoading('#btnConfirmTransfer', true);
            transferConfirmModal?.hide();
            onTransfer();
        });
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

        setDashboardSkeleton(true);
        setRealtimeStatus(false);
        try {
            await loadDashboard();
            connectDashboardRealtime();
        } catch (_) {
            clearToken();
            window.location.href = '/auth';
        } finally {
            setDashboardSkeleton(false);
        }
    });
</script>
</body>
</html>
