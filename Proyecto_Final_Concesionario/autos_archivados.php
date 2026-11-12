<?php
// Listado de autos archivados con opción de reactivar. Solo admin.
require 'conexion.php';
require 'funciones.php';
requerir_admin();

// Eliminación DEFINITIVA (solo POST + CSRF): únicamente autos ya archivados y sin compras
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $id = id_post();
    try {
        $stmt = $conexion->prepare('SELECT imagen FROM autos WHERE id = :id AND fecha_archivado IS NOT NULL LIMIT 1');
        $stmt->execute([':id' => $id]);
        $auto = $stmt->fetch();
        if (!$auto) {
            mensaje_flash('El auto no existe o no está archivado.', 'error');
        } else {
            $stmt = $conexion->prepare('DELETE FROM autos WHERE id = :id AND fecha_archivado IS NOT NULL');
            $stmt->execute([':id' => $id]);
            borrar_imagen($auto['imagen']);
            mensaje_flash('Auto eliminado definitivamente.');
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            mensaje_flash('No se puede eliminar: el auto tiene compras registradas.', 'error');
        } else {
            error_log('Eliminar auto: ' . $e->getMessage());
            mensaje_flash('No se pudo eliminar el auto.', 'error');
        }
    }
    header('Location: autos_archivados.php');
    exit;
}

$autos = $conexion->query(
    'SELECT id, marca, modelo, anio, patente, fecha_archivado FROM autos
     WHERE fecha_archivado IS NOT NULL ORDER BY fecha_archivado DESC'
)->fetchAll();

$titulo = 'Autos archivados';
require 'encabezado.php';
?>
<h1>Autos archivados</h1>
<p><a href="admin_autos.php">Volver a autos activos</a></p>
<?php if (!$autos): ?>
  <p>No hay autos archivados.</p>
<?php else: ?>
<div class="tabla-scroll">
<table>
  <thead><tr><th>ID</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Patente</th><th>Archivado el</th><th>Acción</th></tr></thead>
  <tbody>
  <?php foreach ($autos as $a): ?>
    <tr>
      <td><?= (int) $a['id'] ?></td>
      <td><?= escapar($a['marca']) ?></td>
      <td><?= escapar($a['modelo']) ?></td>
      <td><?= (int) $a['anio'] ?></td>
      <td><?= escapar($a['patente']) ?></td>
      <td><?= escapar($a['fecha_archivado']) ?></td>
      <td>
        <form method="POST" action="reactivar_auto.php" class="en-linea"
              onsubmit="return confirm('¿Reactivar este auto?');">
          <?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <button type="submit" class="boton-link">Reactivar</button>
        </form>
        <form method="POST" class="en-linea"
              onsubmit="return confirm('Se eliminará DEFINITIVAMENTE. ¿Continuar?');">
          <?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <input type="hidden" name="accion" value="eliminar">
          <button type="submit" class="boton-link peligro">Eliminar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require 'pie.php'; ?>
