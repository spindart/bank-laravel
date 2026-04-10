<?php

namespace App\Exceptions\Finance;

class InsufficientBalanceException extends FinanceException
{
    public function __construct()
    {
        parent::__construct(trans('messages.error.insufficient_balance'), 422);
    }
}

