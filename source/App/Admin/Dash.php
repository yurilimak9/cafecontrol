<?php


namespace Source\App\Admin;


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

    /**
     *
     */
    public function dash(): void
    {
        redirect("/admin/dash/home");
    }

    /**
     *
     */
    public function home(): void
    {
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
                "subscribers" => "",
                "plans" => "",
                "recurrence" => ""
            ],
            "blog" => (object)[
                "posts" => "",
                "drafts" => "",
                "categories" => "",
            ],
            "users" => (object)[
                "users" => "",
                "admins" => ""
            ],
            "online" => "",
            "onlineCount" => ""
        ]);
    }
}