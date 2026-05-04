<?php

return [
    // Wallet
    'wallet.show.success' => 'Carteira carregada com sucesso.',
    'wallet.deposit.success' => 'Deposito realizado com sucesso.',
    'wallet.transfer.success' => 'Transferencia realizado com sucesso.',
    'wallet.reverse.success' => 'Transacao revertida com sucesso.',

    // Transactions
    'transaction.history.success' => 'Historico carregado com sucesso.',

    // Savings boxes
    'savings_box.index.success' => 'Caixinhas carregadas com sucesso.',
    'savings_box.store.success' => 'Caixinha criada com sucesso.',
    'savings_box.show.success' => 'Caixinha carregada com sucesso.',
    'savings_box.update.success' => 'Caixinha atualizada com sucesso.',
    'savings_box.cancel.success' => 'Caixinha cancelada com sucesso.',
    'savings_box.deposit.success' => 'Dinheiro guardado com sucesso.',
    'savings_box.withdraw.success' => 'Dinheiro resgatado com sucesso.',
    'savings_box.movements.success' => 'Movimentacoes da caixinha carregadas com sucesso.',

    // Auth
    'auth.register.success' => 'Usuario registrado com sucesso.',
    'auth.login.success' => 'Login realizado com sucesso.',
    'auth.logout.success' => 'Logout realizado com sucesso.',
    'auth.invalid_credentials' => 'Credenciais invalidas.',
    'auth.unauthenticated' => 'Nao autenticado.',

    // Errors
    'error.validation' => 'Erro de validacao.',
    'error.insufficient_balance' => 'Saldo insuficiente para transferir.',
    'error.transaction_not_reversible' => 'Somente transacoes completed podem ser revertidas.',
    'error.wallet_not_found' => 'Carteira nao encontrada para o usuario.',
    'error.savings_box_not_found' => 'Caixinha nao encontrada.',
    'error.savings_box_inactive' => 'Esta caixinha nao esta ativa para movimentacoes.',
    'error.savings_box_insufficient_balance' => 'Saldo insuficiente na caixinha.',
    'error.too_many_requests' => 'Muitas requisicoes. Tente novamente em instantes.',
    'error.internal_server' => 'Erro interno do servidor.',
];
