<?php
namespace Akti\Controllers;

class HomeController extends BaseController {
    public function index() {
        $this->render('home/index');
    }
}
