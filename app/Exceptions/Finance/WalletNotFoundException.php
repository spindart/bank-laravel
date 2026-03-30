<?php

namespace App\Exceptions\Finance;

class WalletNotFoundException extends FinanceException
{
    public function __construct()
    {
        parent::__construct('Carteira nao encontrada para o usuario.', 404);
    }
}

