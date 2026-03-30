<?php

namespace App\Exceptions\Finance;

class InsufficientBalanceException extends FinanceException
{
    public function __construct()
    {
        parent::__construct('Saldo insuficiente para transferir.', 422);
    }
}

