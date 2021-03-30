<?php


namespace Source\App;


use Source\Core\Controller;
use Source\Models\Auth;
use Source\Support\Message;
use const http\Client\Curl\AUTH_ANY;

class App extends Controller
{
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP);

        if (!Auth::user()) {
            $this->message->warning("Efetue login para acessar o App")->flash();
            redirect("/entrar");
        }
    }

    public function home(): void
    {

        echo flash();
        var_dump(Auth::user());
        echo "<a title='Sair' href='" . url("/app/sair") . "'>Sair</a>";
    }

    public function logout(): void
    {
        (new Message())->info("Voccê saiu com sucesso " . Auth::user()->first_name . ". Volte logo :)")->flash();

        Auth::logout();
        redirect("/entrar");
    }
}