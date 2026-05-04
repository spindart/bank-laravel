<?php

namespace App\Exceptions\Finance;

class SavingsBoxNotFoundException extends FinanceException
{
    public function __construct()
    {
        parent::__construct(trans('messages.error.savings_box_not_found'), 404);
    }
}
