<?php

namespace Source\App;

use Source\Core\Controller;
use Source\Core\Session;
use Source\Models\Auth;
use Source\Models\CafeApp\AppInvoice;
use Source\Models\Post;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Message;

/**
 * Class App
 * @package Source\App
 */
class App extends Controller
{
    /** @var User */
    private $user;

    /**
     * App constructor.
     */
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        if (!$this->user = Auth::user()) {
            $this->message->warning("Efetue login para acessar o APP.")->flash();
            redirect("/entrar");
        }

        (new Access())->report();
        (new Online())->report();
    }

    /**
     * APP HOME
     */
    public function home()
    {
        $head = $this->seo->render(
            "Olá {$this->user->first_name}. Vamos controlar? - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        /** Start CHART */

        $dateChart = [];
        for ($month = -4; $month <= 0; $month++) {
            $dateChart[] = date("m/Y", strtotime("{$month}month"));
        }

        $chartData = new \stdClass();
        $chartData->categories = "'" . implode("','", $dateChart) . "'";
        $chartData->expense = "0,0,0,0,0";
        $chartData->income = "0,0,0,0,0";

        $chart = (new AppInvoice())
            ->find("user_id = :user AND status = :status AND due_at >= DATE(NOW() - INTERVAL 4 MONTH) GROUP BY year(due_at) ASC, month(due_at) ASC",
                "user={$this->user->id}&status=paid",
                "
                    year(due_at) AS due_year, 
                    month(due_at) AS due_month,
                    DATE_FORMAT(due_at, '%m/%Y') AS due_date,
                    (
                        SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income' AND year(due_at) = due_year AND month(due_at) = due_month
                    ) AS income,
                    (
                        SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense' AND year(due_at) = due_year AND month(due_at) = due_month
                    ) AS expense
                "
            )->limit(5)->fetch(true);

        if ($chart) {
            $chartCategories = [];
            $chartIncome = [];
            $chartExpense = [];

            foreach ($chart as $chartItem) {
                $chartCategories[] = $chartItem->due_date;
                $chartIncome[] = $chartItem->income;
                $chartExpense[] = $chartItem->expense;
            }

            $chartData->categories = "'" . implode("','", $chartCategories) . "'";
            $chartData->income = implode(",", array_map("abs", $chartIncome));
            $chartData->expense = implode(",", array_map("abs", $chartExpense));
        }

        /** End CHART */

        /** Start INCOME & EXPENSE */

        $income = (new AppInvoice())
            ->find("user_id = :user AND type = 'income' AND status = 'unpaid' AND DATE(due_at) <= DATE(NOW()) + INTERVAL 1 MONTH", "user={$this->user->id}")
            ->order("due_at")
            ->fetch(true);

        $expense = (new AppInvoice())
            ->find("user_id = :user AND type = 'expense' AND status = 'unpaid' AND DATE(due_at) <= DATE(NOW()) + INTERVAL 1 MONTH", "user={$this->user->id}")
            ->order("due_at")
            ->fetch(true);

        /** End INCOME & EXPENSE */

        /** Start WALLET */

        $wallet = (new AppInvoice())
            ->find(
                "user_id = :user AND status = :status",
                "user={$this->user->id}&status=paid",
                "
                    (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income') AS income,
                    (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense') AS expense
                "
            )->fetch();

        if ($wallet) {
            $wallet->wallet = $wallet->income - $wallet->expense;
        }

        /** End WALLET */

        /** Start POSTS */

        $posts = (new Post())->find()->limit(3)->order("post_at DESC")->fetch(true);

        /** End POSTS */

        echo $this->view->render("home", [
            "head" => $head,
            "chart" => $chartData,
            "income" => $income,
            "expense" => $expense,
            "wallet" => $wallet,
            "posts" => $posts
        ]);
    }

    /**
     * APP INCOME (Receber)
     */
    public function income()
    {
        $head = $this->seo->render(
            "Minhas receitas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("income", [
            "head" => $head
        ]);
    }

    /**
     * APP EXPENSE (Pagar)
     */
    public function expense()
    {
        $head = $this->seo->render(
            "Minhas despesas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("expense", [
            "head" => $head
        ]);
    }

    /**
     * @param array $data
     */
    public function launch(array $data): void
    {
        if (request_limit("applaunch", 20)) {
            $json["message"] = $this->message->warning("Foi muito rápido {$this->user->first_name}! Por favor, aguarde 5 minutos para novos lançamentos.")->render();
            echo json_encode($json);
            return;
        }

        if (empty($data["enrollments"]) && $data["enrollments"] < 2 || $data["enrollments"] > 420) {
            $json["message"] = $this->message->warning("Ooops {$this->user->first_name}! Para lançar o número de parcelas deve ser entre 2 e 420.")->render();
            echo json_encode($json);
            return;
        }

        $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
        $status = (date($data["due_at"]) <= date("Y-m-d") ? "paid" : "unpaid");

        $invoice = (new AppInvoice());
        $invoice->user_id = $this->user->id;
        $invoice->wallet_id = $data["wallet"];
        $invoice->category_id = $data["category"];
        $invoice->invoice_of = null;
        $invoice->description = $data["description"];
        $invoice->type = ($data["repeat_when"] == "fixed" ? "fixed_{$data["type"]}" : $data["type"]);
        $invoice->value = str_replace([".", ","], ["", "."], $data["value"]);
        $invoice->currency = $data["currency"];
        $invoice->due_at = $data["due_at"];
        $invoice->repeat_when = $data["repeat_when"];
        $invoice->period = (!empty($data["period"]) ? $data["period"] : "month");
        $invoice->enrollments = (!empty($data["enrollments"]) ? $data["enrollments"] : 1);
        $invoice->enrollment_of = 1;
        $invoice->status = ($data["repeat_when"] == "fixed" ? "paid" : $status);

        if (!$invoice->save()) {
            $json["message"] = $invoice->message()->before("Ooops! ")->render();
            echo json_encode($json);
            return;
        }

        if ($invoice->repeat_when == "enrollment") {
            $invoiceOf = $invoice->id;
            for ($enrollment = 1; $enrollment < $invoice->enrollments; $enrollment++) {
                $invoice->id = null;
                $invoice->invoice_of = $invoiceOf;
                $invoice->due_at = date("Y-m-d", strtotime($data["due_at"] . "+{$enrollment}month"));
                $invoice->status = (date($invoice->due_at) <= date("Y-m-d") ? "paid" : "unpaid");
                $invoice->enrollment_of = $enrollment + 1;
                $invoice->save();
            }
        }

        if ($invoice->type == "income") {
            $this->message->success("Receita lançada com sucesso. Use o filtro para controlar.")->render();
        } else {
            $this->message->success("Despesa lançada com sucesso. Use o filtro para controlar")->render();
        }

        $json["reload"] = true;
        echo json_encode($json);
    }

    /**
     * APP INVOICE (Fatura)
     */
    public function invoice()
    {
        $head = $this->seo->render(
            "Aluguel - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("invoice", [
            "head" => $head
        ]);
    }

    /**
     * APP PROFILE (Perfil)
     */
    public function profile()
    {
        $head = $this->seo->render(
            "Meu perfil - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("profile", [
            "head" => $head
        ]);
    }

    /**
     * APP LOGOUT
     */
    public function logout()
    {
        (new Message())->info("Você saiu com sucesso " . Auth::user()->first_name . ". Volte logo :)")->flash();

        Auth::logout();
        redirect("/entrar");
    }
}