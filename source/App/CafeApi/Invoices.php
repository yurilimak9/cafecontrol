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
                "?? preciso informar o ID da fatura que deseja consultar"
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
                "Voc?? tentou acessar uma fatura que n??o existe ou j?? foi removida"
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
        $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
        if (empty($data["invoice_id"]) || !$invoiceId = filter_var($data["invoice_id"], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "Informe o ID do lan??amento que deseja atualizar"
            )->back();

            return;
        }

        $invoice = (new AppInvoice())->find("user_id = :user AND id = :id", "user={$this->user->id}&id={$invoiceId}")->fetch();
        if (!$invoice) {
            $this->call(
                404,
                "not_found",
                "Voc?? tentou atualizar um lan??amento que n??o existe"
            )->back();

            return;
        }

        if (!empty($data["wallet_id"]) && $walletId = filter_var($data["wallet_id"], FILTER_VALIDATE_INT)) {
            $wallet = (new AppWallet())->find("user_id = :user AND id = :id", "user={$this->user->id}&id={$walletId}")->fetch();
            if (!$wallet) {
                $this->call(
                    400,
                    "invalid_data",
                    "Voc?? informou uma carteira que n??o existe"
                )->back();

                return;
            }
        }

        if (!empty($data["category_id"]) && $categoryId = filter_var($data["category_id"], FILTER_VALIDATE_INT)) {
            $category = (new AppCategory())->findById($categoryId);
            if (!$categoryId) {
                $this->call(
                    400,
                    "invalid_data",
                    "Voc?? informou uma categoria que n??o existe"
                )->back();

                return;
            }
        }

        if (!empty($data["due_day"]) && ($data["due_day"] < 1 || $data["due_day"] > 28)) {
            $this->call(
                400,
                "invalid_data",
                "O dia de vencimento deve estar entre 1 e 28"
            )->back();

            return;
        }

        $dueAt = date("Y-m", strtotime($invoice->due_at)) . "-" . $data["due_day"];
        $statusList = ["paid", "unpaid"];
        if (!empty($data["status"]) && !in_array($data["status"], $statusList)) {
            $this->call(
                400,
                "invalid_data",
                "O status do lan??amento deve ser pago ou n??o pago"
            )->back();

            return;
        }

        $invoice->wallet_id = (!empty($data["wallet_id"]) ? $data["wallet_id"] : $invoice->wallet_id);
        $invoice->category_id = (!empty($data["category_id"]) ? $data["category_id"] : $invoice->category_id);
        $invoice->description = (!empty($data["description"]) ? $data["description"] : $invoice->description);
        $invoice->value = (!empty($data["value"]) ? $data["value"] : $invoice->value);
        $invoice->due_at = (!empty($dueAt) ? date("Y-m-d", strtotime($dueAt)) : $invoice->due_at);
        $invoice->status = (!empty($data["status"]) ? $data["status"] : $invoice->status);

        if (!$invoice->save()) {
            $this->call(
                400,
                "invalid_data",
                $invoice->message()->getText()
            )->back();

            return;
        }

        $this->back(["invoice" => $invoice->data()]);
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
                "Informe o ID do lana??amento que deseja deletar"
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
                "Voc?? tentou excluir uma fatura que n??o existe"
            )->back();

            return;
        }

        $invoice->destroy();

        $this->call(
            200,
            "success",
            "O lan??amento foi exclu??do com sucesso",
            "accepted"
        )->back();
    }
}