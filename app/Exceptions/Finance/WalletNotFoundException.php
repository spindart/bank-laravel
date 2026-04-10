<?php

namespace App\Exceptions\Finance;

class WalletNotFoundException extends FinanceException
{
    public function __construct()
    {
        parent::__construct(trans('messages.error.wallet_not_found'), 404);
    }
}

