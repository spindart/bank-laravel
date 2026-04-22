<?php

return [
    // Wallet
    'wallet.show.success' => 'Wallet loaded successfully.',
    'wallet.deposit.success' => 'Deposit completed successfully.',
    'wallet.transfer.success' => 'Transfer completed successfully.',
    'wallet.reverse.success' => 'Transaction reversed successfully.',

    // Transactions
    'transaction.history.success' => 'History loaded successfully.',

    // Auth
    'auth.register.success' => 'User registered successfully.',
    'auth.login.success' => 'Login successful.',
    'auth.logout.success' => 'Logout successful.',
    'auth.invalid_credentials' => 'Invalid credentials.',
    'auth.unauthenticated' => 'Unauthenticated.',

    // Errors
    'error.validation' => 'Validation error.',
    'error.insufficient_balance' => 'Insufficient balance for transfer.',
    'error.transaction_not_reversible' => 'Only completed transactions can be reversed.',
    'error.wallet_not_found' => 'Wallet not found for user.',
    'error.too_many_requests' => 'Too many requests. Please try again shortly.',
    'error.internal_server' => 'Internal server error.',
];
