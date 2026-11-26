<?php
require_once __DIR__.'/../../middleware/roles.php';
exigirRol('CLIENTE');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); header('Allow: GET'); exit; }
header('Location: '.urlAplicacion('views/cliente/index.php?vista=cuentas'), true, 303);
exit;
