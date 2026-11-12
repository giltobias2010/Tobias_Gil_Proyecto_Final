<?php
// Borrado lógico: marca la fecha en fecha_archivado. Solo admin, solo POST, con CSRF.
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
    'UPDATE autos SET fecha_archivado = CURRENT_TIMESTAMP
     WHERE id = :id AND fecha_archivado IS NULL'
);
$stmt->execute([':id' => $id]);

mensaje_flash($stmt->rowCount() ? 'Auto archivado.' : 'El auto no existe o ya estaba archivado.', $stmt->rowCount() ? 'ok' : 'error');
header('Location: admin_autos.php');
exit;
