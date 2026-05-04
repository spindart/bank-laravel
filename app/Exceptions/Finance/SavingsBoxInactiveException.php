<?php

namespace App\Exceptions\Finance;

class SavingsBoxInactiveException extends FinanceException
{
    public function __construct()
    {
        parent::__construct(trans('messages.error.savings_box_inactive'), 422);
    }
}
