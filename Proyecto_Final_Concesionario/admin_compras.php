<?php
// Listado de todas las compras realizadas. Solo admin.
require 'conexion.php';
require 'funciones.php';
requerir_admin();

$compras = $conexion->query(
    'SELECT c.id, c.precio, c.fecha_compra, a.marca, a.modelo, a.patente,
            u.nombre, u.apellido, u.dni
     FROM compras c
     JOIN autos a ON a.id = c.auto_id
     JOIN usuarios u ON u.id = c.usuario_id
     ORDER BY c.fecha_compra DESC'
)->fetchAll();

$titulo = 'Compras';
require 'encabezado.php';
?>
<h1>Compras realizadas</h1>
<?php if (!$compras): ?>
  <p>Todavía no hay compras.</p>
<?php else: ?>
<div class="tabla-scroll">
<table>
  <thead><tr><th>ID</th><th>Fecha</th><th>Comprador</th><th>DNI</th><th>Auto</th><th>Patente</th><th>Precio</th></tr></thead>
  <tbody>
  <?php foreach ($compras as $c): ?>
    <tr>
      <td><?= (int) $c['id'] ?></td>
      <td><?= escapar($c['fecha_compra']) ?></td>
      <td><?= escapar($c['nombre'] . ' ' . $c['apellido']) ?></td>
      <td><?= escapar($c['dni']) ?></td>
      <td><?= escapar($c['marca'] . ' ' . $c['modelo']) ?></td>
      <td><?= escapar($c['patente']) ?></td>
      <td><?= formato_precio($c['precio']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require 'pie.php'; ?>
