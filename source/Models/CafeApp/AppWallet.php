<?php


namespace Source\Models\CafeApp;


use Source\Core\Model;

/**
 * Class AppWallet
 * @package Source\Models\CafeApp
 */
class AppWallet extends Model
{
    /**
     * AppWallet constructor.
     */
    public function __construct()
    {
        parent::__construct("app_wallets", ["id"], ["user_id", "wallet"]);
    }
}