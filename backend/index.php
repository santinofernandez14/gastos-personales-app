<?php
// Redirige automáticamente el tráfico de la raíz a tu archivo de la API
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Location: /api/login.php");
exit;
?>