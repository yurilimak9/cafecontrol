<?php

require __DIR__ . "/../vendor/autoload.php";

$subscription = new \Source\Models\CafeApp\AppSubscription();
$email = new \Source\Support\Email();
$view = new \Source\Core\View(__DIR__ . "/../shared/views/email");


/**
 * CHARGE OR PAST DUE: Assinaturas de hoje
 */
$chargeNow = $subscription->find(
    "pay_status = :status AND next_due = DATE(NOW()) AND last_charge != DATE(NOW())",
    "status=active"
)->fetch(true);

if ($chargeNow) {
    foreach ($chargeNow as $subscribe) {
        $user = (new \Source\Models\User())->findById($subscribe->user_id);
        $plan = $subscribe->plan();
        $card = $subscribe->creditCard();
        $transaction = $card->transaction($plan->price);

        /** Charge control */
        $subscribe->last_charge = date("Y-m-d");

        if (!$transaction) {

            /** CHAEGE SUCCESS */
            $subscribe->next_due = date("Y-m-d", strtotime($subscribe->next_due . "+{$plan->period}"));
            (new \Source\Models\CafeApp\AppOrder())->byCreditCard($user, $card, $subscribe, $transaction);

            $subject = "[PAGAMENTO CONFIRMADO] Obrigado por assinar o CaféApp";
            $body = $view->render("mail", [
                "subject" => $subject,
                "message" => "<h3>Obrigado {$user->first_name}!</h3>
                    <p>Estamos passando apenas para agradecer por você ser um assinante CaféApp {$plan->name}.</p>
                    <p>Sua fatura deste mês venceu hoje e já está paga de acordo com seu plano. Qualquer dúvida estamos a disposição.</p>"
            ]);

            $email->bootstrap(
                $subject,
                $body,
                $user->email,
                "{$user->first_name} {$user->last_name}"
            )->queue();

        } else {

            /** CHAEGE FAIL */
            $subscribe->status = "past_due";
            (new \Source\Models\CafeApp\AppOrder())->byCreditCard($user, $card, $subscribe, $transaction);

            $subject = "[PAGAMENTO RECUSADO] Sua conta CaféApp precisa de atenção";
            $body = $view->render("mail", [
                "subject" => $subject,
                "message" => "<h3>Prezado {$user->first_name}!</h3>
                    <p>Não conseguimos cobrar seu cartão referente a fatura deste mês para sua assinatura CaféApp. Precisamos que você veja isso.</p>
                    <p>Acesse sua conta para atualizar seus dados de pagamento, você pode cadastrar outro cartão.</p>
                    <p>Se não fizer nada agora uma nova tentativa de cobrança será feita em 3 dias. Se não der certo, sua assinatura será cancelada :/</p>"
            ]);

            $email->bootstrap(
                $subject,
                $body,
                $user->email,
                "{$user->first_name} {$user->last_name}"
            )->queue();

        }

        /** Charge save */
        $subscribe->save();
    }
}