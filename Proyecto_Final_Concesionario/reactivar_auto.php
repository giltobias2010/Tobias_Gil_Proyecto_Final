<?php
// Reactiva un auto archivado (fecha_archivado = NULL). Solo admin, POST y CSRF.
require 'conexion.php';
require 'funciones.php';
requerir_admin();
requerir_post();
csrf_validar();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador inválido.');
}

$stmt = $conexion->prepare(
    'UPDATE autos SET fecha_archivado = NULL
     WHERE id = :id AND fecha_archivado IS NOT NULL'
);
$stmt->execute([':id' => $id]);

mensaje_flash($stmt->rowCount() ? 'Auto reactivado.' : 'El auto no existe o no estaba archivado.', $stmt->rowCount() ? 'ok' : 'error');
header('Location: autos_archivados.php');
exit;
