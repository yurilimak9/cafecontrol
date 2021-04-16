<?php


namespace Source\App\Admin;


use Source\Models\Post;
use Source\Support\Pager;

/**
 * Class Blog
 * @package Source\App\Admin
 */
class Blog extends Admin
{
    /**
     * Blog constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array|null $data
     */
    public function home(?array $data): void
    {
        /** Search Redirect */
        if (!empty($data["s"])) {
            $s = filter_var($data["s"], FILTER_SANITIZE_STRIPPED);

            echo json_encode([
                "redirect" => url("/admin/blog/home/{$s}/1")
            ]);
            return;
        }

        $search = null;
        $posts = (new Post())->find();

        if (!empty($data["search"]) && $data["search"] != "all") {
            $search = filter_var($data["search"], FILTER_SANITIZE_STRIPPED);
            $posts = (new Post())->find("MATCH(title, subtitle) AGAINST(:s)", "s={$search}");
        }

        $all = ($search ?? "all");
        $pager = new Pager(url("/admin/blog/home/{$all}/"));
        $pager->pager($posts->count(), 12, (!empty($data["page"]) ? $data["page"] : 1));

        $head = $this->seo->render(
            "Blog | " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/blog/home", [
            "app" => "blog/home",
            "head" => $head,
            "posts" => $posts->limit($pager->limit())->offset($pager->offset())->order("post_at DESC")->fetch(true),
            "paginator" => $pager->render(),
            "search" => $search
        ]);
    }

    /**
     * @param array|null $data
     */
    public function post(?array $data): void
    {

    }

    /**
     * @param array|null $data
     */
    public function categories(?array $data): void
    {

    }

    /**
     * @param array|null $data
     */
    public function category(?array $data): void
    {

    }
}