<?php
namespace Akti\Controllers;

/**
 * Class HomeController.
 */
class HomeController extends BaseController {
    /**
     * Exibe a página de listagem.
     */
    public function index() {
        $this->render('home/index');
    }
}
