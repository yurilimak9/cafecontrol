<?php


namespace Source\App\Admin;


use Source\Models\Auth;
use Source\Models\CafeApp\AppPlan;
use Source\Models\CafeApp\AppSubscription;
use Source\Models\Category;
use Source\Models\Post;
use Source\Models\Report\Online;
use Source\Models\User;

/**
 * Class Dash
 * @package Source\App\Admin
 */
class Dash extends Admin
{
    /**
     * Dash constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function dash(): void
    {
        redirect("/admin/dash/home");
    }

    /**
     * ADMIN HOME
     * @param array|null $data
     * @throws \Exception
     */
    public function home(?array $data): void
    {
        /** Real time access */
        if (!empty($data["refresh"])) {
            $list = null;
            $items = (new Online())->findByActive();
            if ($items) {
                foreach ($items as $item) {
                    $list[] = [
                        "dates" => date_fmt($item->created_at, "H\hi") . " - " . date_fmt($item->updated_at, "H\hi"),
                        "user" => ($item->user ? $item->user()->fullName() : "Guest User"),
                        "pages" => $item->pages,
                        "url" => $item->url
                    ];
                }
            }
            echo json_encode([
                "count" => (new Online())->findByActive(true),
                "list" => $list
            ]);
            return;
        }

        $head = $this->seo->render(
            "Dashboard | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/dash/home", [
            "app" => "dash",
            "head" => $head,
            "control" => (object)[
                "subscribers" => (new AppSubscription())->find("pay_status = :status", "status=active")->count(),
                "plans" => (new AppPlan())->find("status = :status", "status=active")->count(),
                "recurrence" => (new AppSubscription())->recurrence()
            ],
            "blog" => (object)[
                "posts" => (new Post())->find("status = :status", "status=post")->count(),
                "drafts" => (new Post())->find("status = :status", "status=draft")->count(),
                "categories" => (new Category())->find("type = :type", "type=post")->count(),
            ],
            "users" => (object)[
                "users" => (new User())->find("level < 5")->count(),
                "admins" => (new User())->find("level >= 5")->count()
            ],
            "online" => (new Online())->findByActive(),
            "onlineCount" => (new Online())->findByActive(true)
        ]);
    }

    public function logoff(): void
    {
        $this->message->info("Você saiu com sucesso {$this->user->first_name}.")->flash();

        Auth::logout();

        redirect("/admin/login");
    }
}