<?php

return [
    // Wallet
    'wallet.show.success' => 'Wallet loaded successfully.',
    'wallet.deposit.success' => 'Deposit completed successfully.',
    'wallet.transfer.success' => 'Transfer completed successfully.',
    'wallet.reverse.success' => 'Transaction reversed successfully.',

    // Transactions
    'transaction.history.success' => 'History loaded successfully.',

    // Savings boxes
    'savings_box.index.success' => 'Savings boxes loaded successfully.',
    'savings_box.store.success' => 'Savings box created successfully.',
    'savings_box.show.success' => 'Savings box loaded successfully.',
    'savings_box.update.success' => 'Savings box updated successfully.',
    'savings_box.cancel.success' => 'Savings box cancelled successfully.',
    'savings_box.deposit.success' => 'Savings deposit request queued successfully.',
    'savings_box.withdraw.success' => 'Savings withdraw request queued successfully.',
    'savings_box.movements.success' => 'Savings box movements loaded successfully.',

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
    'error.savings_box_not_found' => 'Savings box not found.',
    'error.savings_box_inactive' => 'This savings box is not active for movements.',
    'error.savings_box_insufficient_balance' => 'Insufficient savings box balance.',
    'error.too_many_requests' => 'Too many requests. Please try again shortly.',
    'error.internal_server' => 'Internal server error.',
];
