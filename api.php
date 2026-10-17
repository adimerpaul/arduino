<?php
// Devuelve en JSON las ultimas lecturas guardadas por guardar_datos.py
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$archivo = __DIR__ . '/sensores_general/datos_sensores.txt';
$limite = isset($_GET['n']) ? (int)$_GET['n'] : 60;
$limite = max(1, min($limite, 600));

clearstatcache();
if (!is_file($archivo)) {
    echo json_encode(['ok' => false, 'error' => 'Todavia no existe datos_sensores.txt. Ejecuta iniciar_guardado.bat', 'datos' => []]);
    exit;
}

// Leer solo el final del archivo (asi no se pone lento cuando crece)
$tam = filesize($archivo);
$f = fopen($archivo, 'r');
$inicio = max(0, $tam - $limite * 200);
fseek($f, $inicio);
$texto = stream_get_contents($f);
fclose($f);

$lineas = explode("\n", trim($texto));
if ($inicio > 0) array_shift($lineas); // primera linea puede venir cortada

$campos = ['fecha', 'temp', 'hum', 'luz', 'gas', 'ax', 'ay', 'az', 'gx', 'gy', 'gz'];
$datos = [];
foreach ($lineas as $linea) {
    $partes = explode("\t", trim($linea));
    if (count($partes) !== 11 || $partes[0] === 'fecha_hora') continue;
    $fila = ['fecha' => $partes[0]];
    for ($i = 1; $i < 11; $i++) $fila[$campos[$i]] = (float)$partes[$i];
    $datos[] = $fila;
}
$datos = array_slice($datos, -$limite);

echo json_encode([
    'ok' => true,
    'segundos_sin_datos' => time() - filemtime($archivo),
    'total_bytes' => $tam,
    'datos' => $datos,
]);
