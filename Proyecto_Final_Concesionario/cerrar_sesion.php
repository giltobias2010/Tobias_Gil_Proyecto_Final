<?php
// Cierre de sesión seguro (solo por POST con token CSRF).
require 'funciones.php';
requerir_post();
csrf_validar();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
