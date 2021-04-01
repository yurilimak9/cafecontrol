<?php


namespace Source\Models\CafeApp;


use Source\Core\Model;

/**
 * Class AppInvoice
 * @package Source\Models\CafeApp
 */
class AppInvoice extends Model
{
    /**
     * AppInvoice constructor.
     */
    public function __construct()
    {
        parent::__construct(
            "app_invoices",
            ["id"],
            ["user_id", "wallet_id", "category_id", "description", "type", "value", "due_at", "repeat_when"]
        );
    }
}