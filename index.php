<?php
/**
 * Controlador frontal. Todas las páginas pasan por aquí:
 *   index.php?r=modulo&a=accion&id=N
 * Funciona en una subcarpeta de XAMPP sin necesidad de mod_rewrite.
 */
require __DIR__ . '/app/bootstrap.php';

$r = preg_replace('/[^a-z_]/', '', strtolower($_GET['r'] ?? 'dashboard')) ?: 'dashboard';
$a = preg_replace('/[^a-zA-Z_]/', '', $_GET['a'] ?? 'index') ?: 'index';

if ($r === 'auth') {
    require APP_PATH . '/Controllers/AuthController.php';
    $ctl = new AuthController('auth');
    method_exists($ctl, $a) ? $ctl->$a() : $ctl->login();
    exit;
}

if (!Auth::check()) {
    redirect(url('auth', 'login'));
}

if (!Auth::can($r)) {
    abort(403, 'No tiene permiso para acceder a este módulo.');
}

$class = str_replace(' ', '', ucwords(str_replace('_', ' ', $r))) . 'Controller';
$file = APP_PATH . '/Controllers/' . $class . '.php';
if (is_file($file)) {
    require_once $file;
    $ctl = new $class($r);
} elseif (isset(resources()[$r])) {
    $ctl = new CrudController($r);
} else {
    abort(404, 'Página no encontrada.');
}

$noAcciones = ['options', 'display'];
if ($a[0] === '_' || !is_callable([$ctl, $a]) || in_array($a, $noAcciones, true) || in_array($a, get_class_methods('Controller'), true)) {
    abort(404, 'Acción no encontrada.');
}

try {
    $ctl->$a();
} catch (Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    abort(500, app_config('debug') ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : 'Ocurrió un error inesperado.');
}
