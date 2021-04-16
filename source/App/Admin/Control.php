<?php


namespace Source\App\Admin;


use Source\Models\CafeApp\AppCreditCard;
use Source\Models\CafeApp\AppPlan;
use Source\Models\CafeApp\AppSubscription;
use Source\Support\Pager;

/**
 * Class Control
 * @package Source\App\Admin
 */
class Control extends Admin
{
    /**
     * Control constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function home(): void
    {
        $head = $this->seo->render(
            "Control | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/control/home", [
            "app" => "control/home",
            "head" => $head,
            "stats" => (object)[
                "subscriptions" => (new AppSubscription())->find("pay_status = :status", "status=active")->count(),
                "subscriptionsMonth" => (new AppSubscription())->find(
                    "pay_status = :status AND year(started) = year(NOW()) AND month(started) = month(NOW())", "status=active"
                )->count(),
                "recurrence" => (new AppSubscription())->recurrence(),
                "recurrenceMonth" => (new AppSubscription())->recurrenceMonth()
            ],
            "subscriptions" => (new AppSubscription())->find()->order("started DESC")->limit(10)->fetch(true)
        ]);
    }

    /**
     * @param array|null $data
     */
    public function subscriptions(?array $data): void
    {
        /** Search Redirect */
        if (!empty($data["s"])) {
            $s = filter_var($data["s"], FILTER_SANITIZE_STRIPPED);
            echo json_encode(["redirect" => url("/admin/control/subscriptions/{$s}/1")]);
            return;
        }

        $search = null;
        $subscriptions = (new AppSubscription())->find();

        if (!empty($data["search"]) && $data["search"] != "all") {
            $search = filter_var($data["search"], FILTER_SANITIZE_STRIPPED);
            $subscriptions = (new AppSubscription())->find(
                "user_id IN(
                    SELECT id FROM users WHERE MATCH(first_name, last_name, email) AGAINST(:s)
                )", "s={$search}"
            );
            if (!$subscriptions->count()) {
                $this->message->info("Sua pesquisa não retornou resultados")->flash();
                redirect("/admin/control/subscriptions");
            }
        }

        $all = ($search ?? "all");
        $pager = new Pager(url("/admin/control/subscriptions/{$all}/"));
        $pager->pager($subscriptions->count(), 12, (!empty($data["page"]) ? $data["page"] : 1));

        $head = $this->seo->render(
            "Assinantes | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/control/subscriptions", [
            "app" => "control/subscriptions",
            "head" => $head,
            "subscriptions" => $subscriptions->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render(),
            "search" => $search
        ]);
    }

    /**
     * @param array $data
     */
    public function subscription(array $data): void
    {
        /** Update Subscription */
        if (!empty($data["action"]) && $data["action"] == "update") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
            $subscriptionUpdate = (new AppSubscription())->findById($data["id"]);

            if (!$subscriptionUpdate) {
                $this->message->error("Você tentou atualizar uma assinatura que não existe!")->flash();
                echo json_encode(["redirect" => url("/admin/control/subscriptions")]);
                return;
            }

            $subscriptionUpdate->plan_id = $data["plan_id"];
            $subscriptionUpdate->card_id = $data["plan_id"];
            $subscriptionUpdate->status = $data["status"];
            $subscriptionUpdate->pay_status = $data["pay_status"];
            $subscriptionUpdate->due_day = $data["due_day"];
            $subscriptionUpdate->next_due = date_fmt_back($data["next_due"]);
            $subscriptionUpdate->last_charge = date_fmt_back($data["last_charge"]);

            if (!$subscriptionUpdate->save()) {
                $json["message"] = $subscriptionUpdate->message()->render();
                echo json_encode($json);
                return;
            }

            $json["message"] = $this->message->success("Assinatura atualizada com sucesso")->render();
            echo json_encode($json);
            return;
        }

        /** Read Subscription  */
        $id = filter_var($data["id"], FILTER_VALIDATE_INT);
        if (!$id) {
            redirect("/admin/control/subscriptions");
        }

        $subscription = (new AppSubscription())->findById($id);
        if (!$subscription) {
            $this->message->error("Você tentou editar uma assinatura que não existe")->flash();
            redirect("/admin/control/subscriptions");
        }

        $head = $this->seo->render(
            "Assinatura de {$subscription->user()->fullName()} | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/control/subscription", [
            "app" => "control/subscriptions",
            "head" => $head,
            "subscription" => $subscription,
            "plans" => (new AppPlan())->find("status = :status", "status=active")->fetch(true),
            "cards" => (new AppCreditCard())->find(
                "user_id = :user", "user={$subscription->user_id}"
            )->fetch(true)
        ]);
    }

    /**
     * @param array|null $data
     */
    public function plans(?array $data): void
    {
        $plans = (new AppPlan())->find();
        $pager = new Pager(url("/admin/control/plans/"));
        $pager->pager($plans->count(), 5, (!empty($data["page"]) ? $data["page"] : 1));

        $head = $this->seo->render(
            "Planos de Assinatura | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/control/plans", [
            "app" => "control/plans",
            "head" => $head,
            "plans" => $plans->order("status ASC, created_at DESC")
                ->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);
    }

    /**
     * @param array|null $data
     */
    public function plan(?array $data): void
    {
        $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);

        /** Create Plan */
        if (!empty($data["action"]) && $data["action"] == "create") {

            $planCreate = new AppPlan();
            $planCreate->name = $data["name"];
            $planCreate->period = $data["period"];
            $planCreate->period_str = $data["period_str"];
            $planCreate->price = str_replace([".", ","], ["", "."], $data["price"]);
            $planCreate->status = $data["status"];

            if (!$planCreate->save()) {
                $json["message"] = $planCreate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Plano criado com sucesso. Confira...")->flash();
            echo json_encode(["redirect" => url("/admin/control/plan/{$planCreate->id}")]);
            return;
        }

        /** Update Plan */
        if (!empty($data["action"]) && $data["action"] == "update") {
            $planEdit = (new AppPlan())->findById($data["plan_id"]);

            if (!$planEdit) {
                $this->message->error("Você tentou editar um plano que não existe ou já foi removido")->flash();
                echo json_encode(["redirect" => url("/admin/control/plans")]);
                return;
            }

            $planEdit->name = $data["name"];
            $planEdit->period = $data["period"];
            $planEdit->period_str = $data["period_str"];
            $planEdit->price = str_replace([".", ","], ["", "."], $data["price"]);
            $planEdit->status = $data["status"];

            if (!$planEdit->save()) {
                $json["message"] = $planEdit->message()->render();
                echo json_encode($json);
                return;
            }

            $json["message"] = $this->message->success("Plano atualizado com sucesso...")->render();
            echo json_encode($json);
            return;
        }

        /** Delete Plan */
        if (!empty($data["action"]) && $data["action"] == "delete") {

            $planDelete = (new AppPlan())->findById($data["plan_id"]);

            if (!$planDelete) {
                $this->message->error("Você tentou excluir um plano que não existe ou já foi removido")->flash();
                echo json_encode(["redirect" => url("/admin/control/plans")]);
                return;
            }

            if ($planDelete->subscribers(null)->count()) {
                $json["message"] = $this->message->warning("Não é possível remover planos com assinaturas...")->render();
                echo json_encode($json);
                return;
            }

            $planDelete->destroy();
            $this->message->success("Plano removido com sucesso...")->flash();

            echo json_encode(["redirect" => url("/admin/control/plans")]);
            return;
        }

        /** Read Plan */
        $planEdit = null;
        if (!empty($data["plan_id"])) {
            $planId = filter_var($data["plan_id"], FILTER_VALIDATE_INT);
            $planEdit = (new AppPlan())->findById($planId);
        }

        $head = $this->seo->render(
            "Gerenciar Plano | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/control/plan", [
            "app" => "control/plans",
            "head" => $head,
            "plan" => $planEdit,
            "subscribers" => ($planEdit ? $planEdit->subscribers(null)->count() : null)
        ]);
    }

}