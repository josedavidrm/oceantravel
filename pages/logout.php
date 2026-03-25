<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/autoloader.php';

session_start();
$usuario = new Usuario();
$usuario->logout();
