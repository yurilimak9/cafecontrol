<?php


namespace Source\App\CafeApi;


use Source\Models\CafeApp\AppCategory;
use Source\Models\CafeApp\AppInvoice;
use Source\Models\CafeApp\AppWallet;
use Source\Support\Pager;

/**
 * Class Invoices
 * @package Source\App\CafeApi
 */
class Invoices extends CafeApi
{
    /**
     * Invoices constructor.
     * @throws \Exception
     */
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

    /**
     * @param array $data
     * @throws \Exception
     */
    public function create(array $data): void
    {
        $request = $this->requestLimit("invoiceCreate", 5, 60);
        if (!$request) {
            return;
        }

        $invoice = new AppInvoice();
        if (!$invoice->launch($this->user, $data)) {
            $this->call(
                400,
                "invalid_data",
                $invoice->message()->getText()
            )->back();

            return;
        }

        $invoice->fixed($this->user, 3);
        $this->back(["invoice" => $invoice->data()]);
    }

    /**
     * @param array $data
     */
    public function read(array $data): void
    {
        if (empty($data["invoice_id"]) || !$invoiceId = filter_var($data["invoice_id"], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "É preciso informar o ID da fatura que deseja consultar"
            )->back();

            return;
        }
        $invoice = (new AppInvoice())->find(
            "user_id = :user AND id = :id",
            "user={$this->user->id}&id={$invoiceId}"
        )->fetch();
        if (!$invoice) {
            $this->call(
                404,
                "not_found",
                "Você tentou acessar uma fatura que não existe ou já foi removida"
            )->back();

            return;
        }

        $response["invoice"] = $invoice->data();
        $response["invoice"]->wallet = (new AppWallet())->findById($invoice->wallet_id)->data();
        $response["invoice"]->category = (new AppCategory())->findById($invoice->category_id)->data();

        $this->back($response);
    }

    /**
     * @param array $data
     */
    public function update(array $data): void
    {

    }

    /**
     * @param array $data
     */
    public function delete(array $data): void
    {
        if (empty($data["invoice_id"]) || !$invoiceId = filter_var($data["invoice_id"], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "Informe o ID do lanaçamento que deseja deletar"
            )->back();

            return;
        }
        $invoice = (new AppInvoice())->find(
            "user_id = :user AND id = :id",
            "user={$this->user->id}&id={$invoiceId}"
        )->fetch();
        if (!$invoice) {
            $this->call(
                404,
                "not_found",
                "Você tentou excluir uma fatura que não existe"
            )->back();

            return;
        }

        $invoice->destroy();

        $this->call(
            200,
            "success",
            "O lançamento foi excluído com sucesso",
            "accepted"
        )->back();
    }
}