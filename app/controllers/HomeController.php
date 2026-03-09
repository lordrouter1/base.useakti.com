<?php
namespace Akti\Controllers;

class HomeController {
    public function index() {
        // Lógica do Dashboard poderia vir aqui
        
        // Carregar views
        require 'app/views/layout/header.php';
        require 'app/views/home/index.php';
        require 'app/views/layout/footer.php';
    }
}
