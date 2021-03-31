<?php


namespace Source\App;


use Source\Core\Controller;
use Source\Models\Auth;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Message;

class App extends Controller
{
    /** @var User */
    private $user;

    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        if (!$this->user = Auth::user()) {
            $this->message->warning("Efetue login para acessar o App")->flash();
            redirect("/entrar");
        }

        (new Access())->report();
        (new Online())->report();
    }

    public function home(): void
    {

        echo $this->view->render("home", [

        ]);
    }

    public function logout(): void
    {
        (new Message())->info("Voccê saiu com sucesso " . Auth::user()->first_name . ". Volte logo :)")->flash();

        Auth::logout();
        redirect("/entrar");
    }
}