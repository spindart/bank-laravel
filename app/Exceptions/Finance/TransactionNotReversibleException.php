<?php

namespace App\Exceptions\Finance;

class TransactionNotReversibleException extends FinanceException
{
    public function __construct()
    {
        parent::__construct(trans('messages.error.transaction_not_reversible'), 422);
    }
}

