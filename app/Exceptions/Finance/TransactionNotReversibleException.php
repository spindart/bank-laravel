<?php

namespace App\Exceptions\Finance;

class TransactionNotReversibleException extends FinanceException
{
    public function __construct()
    {
        parent::__construct('Somente transacoes completed podem ser revertidas.', 422);
    }
}

