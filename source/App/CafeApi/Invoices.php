<?php


namespace Source\App\CafeApi;


use Source\Models\CafeApp\AppInvoice;
use Source\Support\Pager;

class Invoices extends CafeApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List all invoices
     */
    public function index(): void
    {
        $where = "";
        $params = "";
        $values = $this->headers;

        /** By Wallet */
        if (!empty($values["wallet_id"]) && $walletId = filter_var($values["wallet_id"], FILTER_VALIDATE_INT)) {
            $where .= "AND wallet_id = :wallet ";
            $params .= "&wallet={$walletId}";
        }

        /** By Type */
        $typeList = ["income", "expense", "fixed_income", "fixed_expense"];
        if (!empty($values["type"]) && in_array($values["type"], $typeList) && $type = $values["type"]) {
            $where .= "AND type = :type ";
            $params .= "&type={$type}";
        }

        /** By Status */
        $statusList = ["paid", "unpaid"];
        if (!empty($values["status"]) && in_array($values["status"], $statusList) && $status = $values["status"]) {
            $where .= "AND status = :status ";
            $params .= "&status={$status}";
        }

        /** Get Invoices */
        $invoices = (new AppInvoice())->find("user_id = :user {$where}", "user={$this->user->id}{$params}");

        if (!$invoices->count()) {
            $this->call(
                404,
                "not_found",
                "Nada encontrado para sua pesquisa. Tente outros termos."
            )->back(["results" => 0]);

            return;
        }

        $page = (!empty($values["page"]) ? $values["page"] : 1);
        $pager = new Pager(url("/invoices/"));
        $pager->pager($invoices->count(), 10, $page);

        $response["results"] = $invoices->count();
        $response["page"] = $pager->page();
        $response["pages"] = $pager->pages();

        $invoices = $invoices->limit($pager->limit())->order("due_at")->offset($pager->offset())->fetch(true);
        foreach ($invoices as $invoice) {
            $response["invoices"][] = $invoice->data();
        }

        $this->back($response);
    }

    public function create(?array $data): void
    {

    }

    public function read(?array $data): void
    {

    }

    public function update(?array $data): void
    {

    }

    public function delete(?array $data): void
    {

    }
}