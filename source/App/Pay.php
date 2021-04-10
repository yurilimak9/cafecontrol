<?php


namespace Source\App;


use Source\Core\Controller;

/**
 * Class Pay
 * @package Source\App
 */
class Pay extends Controller
{
    /**
     * Pay constructor.
     */
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../shared/pagarme/");
    }

    /**
     * @param array $data
     */
    public function create(array $data): void
    {

    }

    /**
     * @param array $data
     */
    public function update(array $data): void
    {

    }
}