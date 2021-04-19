<?php


namespace Source\Models;


use Source\Core\Model;

/**
 * Class Category
 * @package Source\Models
 */
class Category extends Model
{
    /**
     * Category constructor.
     */
    public function __construct()
    {
        parent::__construct("categories", ["id"], ["title", "uri", "description"]);
    }

    /**
     * @param string $uri
     * @param string $columns
     * @return Category|null
     */
    public function findByUri(string $uri, string $columns = "*"): ?Category
    {
        $find = $this->find("uri = :uri", "uri={$uri}", $columns);
        return $find->fetch();
    }

    /**
     * @return Post
     */
    public function posts(): Post
    {
        return (new Post())->find("category = :id", "id={$this->id}");
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        $checkUri = $this->find("uri = :uri AND id != :id", "uri={$this->uri}&id={$this->id}");
        if ($checkUri->count()) {
            $this->uri = "{$this->uri}-{$this->lastId()}";
        }

        return parent::save();
    }
}