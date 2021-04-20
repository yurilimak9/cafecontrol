<?php


namespace Source\App\Admin;


use Source\Models\User;
use Source\Support\Pager;

/**
 * Class Users
 * @package Source\App\Admin
 */
class Users extends Admin
{
    /**
     * Users constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array|null $data
     */
    public function home(?array $data)
    {
        /** Search Redirect */
        if (!empty($data["s"])) {
            $s = filter_var($data["s"], FILTER_SANITIZE_STRIPPED);
            echo json_encode(["redirect" => url("/admin/users/home/{$s}/1")]);
            return;
        }

        $search = null;
        $users = (new User())->find();

        if (!empty($data["search"]) && $data["search"] != "all") {
            $search = filter_var($data["search"], FILTER_SANITIZE_STRIPPED);
            $users = (new User())->find("MATCH(first_name, last_name, email) AGAINST(:s)", "s={$search}");
        }

        if (!$users->count()) {
            $this->message->warning("Não foi encontrado nenhum usuário")->flash();
            redirect("/admin/users/home");
        }

        $all = ($search ?? "all");
        $pager = new Pager(url("/admin/users/home/{$all}/"));
        $pager->pager($users->count(), 6, (!empty($data["page"]) ? $data["page"] : 1));

        $head = $this->seo->render(
            "Usuários | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/users/home", [
            "app" => "users/home",
            "head" => $head,
            "search" => $search,
            "users" => $users->order("first_name, last_name")->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);
    }

    /**
     * @param array|null $data
     */
    public function user(?array $data): void
    {
        $userEdit = null;
        if (!empty($data["user_id"])) {
            $userId = filter_var($data["user_id"], FILTER_VALIDATE_INT);
            $userEdit = (new User())->findById($userId);
        }

        $head = $this->seo->render(
            ($userEdit ? "Perfil de {$userEdit->fullName()}" : "Novo Usuário") . " | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/users/user", [
            "app" => "users/user",
            "head" => $head,
            "user" => $userEdit
        ]);
    }
}