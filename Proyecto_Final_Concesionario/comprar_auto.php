<?php
// Compra de un auto. Usuario logueado, solo POST, con CSRF y transacción.
require 'conexion.php';
require 'funciones.php';
requerir_login();
requerir_post();
csrf_validar();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador inválido.');
}

try {
    // Transacción: la compra y el cambio de estado se hacen juntos o no se hacen
    $conexion->beginTransaction();

    // FOR UPDATE bloquea la fila: evita que dos personas compren el mismo auto
    $stmt = $conexion->prepare(
        "SELECT id, precio FROM autos
         WHERE id = :id AND estado = 'disponible' AND fecha_archivado IS NULL
         FOR UPDATE"
    );
    $stmt->execute([':id' => $id]);
    $auto = $stmt->fetch();

    if (!$auto) {
        $conexion->rollBack();
        mensaje_flash('Ese auto ya no está disponible.', 'error');
    } else {
        // El precio sale de la base de datos, nunca del formulario
        $stmt = $conexion->prepare(
            'INSERT INTO compras (usuario_id, auto_id, precio) VALUES (:usuario, :auto, :precio)'
        );
        $stmt->execute([
            ':usuario' => $_SESSION['usuario_id'],
            ':auto'    => $auto['id'],
            ':precio'  => $auto['precio'],
        ]);

        $stmt = $conexion->prepare("UPDATE autos SET estado = 'vendido' WHERE id = :id");
        $stmt->execute([':id' => $auto['id']]);

        $conexion->commit();
        mensaje_flash('¡Compra realizada! Podés verla en "Mis compras".');
    }
} catch (PDOException $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    error_log('Compra: ' . $e->getMessage());
    mensaje_flash('No se pudo completar la compra.', 'error');
}

header('Location: panel.php');
exit;
