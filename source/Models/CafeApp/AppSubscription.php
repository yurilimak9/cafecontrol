<?php


namespace Source\Models\CafeApp;


use Source\Core\Model;
use Source\Models\User;

/**
 * Class AppSubscription
 * @package Source\Models\CafeApp
 */
class AppSubscription extends Model
{
    /**
     * AppSubscription constructor.
     */
    public function __construct()
    {
        parent::__construct("app_subscriptions", ["id"],
            ["user_id", "plan_id", "card_id", "status", "pay_status", "started", "due_day", "next_due"]
        );
    }

    /**
     * @param User $user
     * @param AppPlan $plan
     * @param AppCreditCard $card
     * @return $this
     * @throws \Exception
     */
    public function subscribe(User $user, AppPlan $plan, AppCreditCard $card): AppSubscription
    {
        $this->user_id = $user->id;
        $this->plan_id = $plan->id;
        $this->card_id = $card->id;
        $this->status = "active";
        $this->pay_status = "active";
        $this->started = date("Y-m-d");

        $day = (new \DateTime($this->started))->format("d");

        if ($day <= 28) {
            $this->due_day = $day;
            $this->next_due = date("Y-m-d", strtotime("+{$plan->period}"));
        } else {
            $this->due_day = 5;
            $this->next_due = date("Y-m-{$this->due_day}", strtotime("+{$plan->period}"));
        }

        $this->last_charge = date("Y-m-d");
        $this->save();

        return $this;
    }

    /**
     * @return AppPlan|null
     */
    public function plan(): ?AppPlan
    {
        return (new AppPlan())->findById($this->plan_id);
    }

    /**
     * @return AppCreditCard|null
     */
    public function creditCard(): ?AppCreditCard
    {
        return (new AppCreditCard())->findById($this->card_id);
    }
}