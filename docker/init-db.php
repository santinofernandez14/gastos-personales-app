<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';
$name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'gastos_personales';
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
$pass = getenv('DB_PASS') !== false
    ? getenv('DB_PASS')
    : (getenv('MYSQLPASSWORD') ?: '');

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

$pdo = null;
$ultimoError = null;
$limite = time() + 60;

while (time() < $limite) {
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        break;
    } catch (PDOException $e) {
        $ultimoError = $e->getMessage();
        error_log('[inicio] MySQL todavía no está disponible. Reintentando...');
        sleep(3);
    }
}

if (!$pdo instanceof PDO) {
    error_log('[inicio] No se pudo conectar a MySQL: ' . $ultimoError);
    fwrite(STDERR, "No se pudo establecer conexión con MySQL después de 60 segundos.\n");
    exit(1);
}

$archivoSchema = __DIR__ . '/schema.sql';

if (!is_file($archivoSchema)) {
    fwrite(STDERR, "No se encontró el archivo docker/schema.sql.\n");
    exit(1);
}

$sql = file_get_contents($archivoSchema);

if ($sql === false) {
    fwrite(STDERR, "No se pudo leer docker/schema.sql.\n");
    exit(1);
}

try {
    // El esquema contiene sentencias CREATE TABLE separadas por punto y coma.
    // CREATE TABLE IF NOT EXISTS permite reiniciar sin borrar los datos existentes.
    $sentencias = preg_split('/;\s*(?:\r?\n|$)/', $sql);

    if ($sentencias === false) {
        throw new RuntimeException('No se pudo procesar el esquema SQL.');
    }

    foreach ($sentencias as $sentencia) {
        $sentencia = trim($sentencia);

        if ($sentencia === '' || preg_match('/^(?:\s*--.*(?:\r?\n|$)\s*)*$/', $sentencia)) {
            continue;
        }

        $pdo->exec($sentencia);
    }

    echo "[inicio] Esquema de base de datos verificado correctamente.\n";
} catch (Throwable $e) {
    error_log('[inicio] Error al preparar el esquema: ' . $e->getMessage());
    fwrite(STDERR, "No se pudo inicializar el esquema de la base de datos.\n");
    exit(1);
}