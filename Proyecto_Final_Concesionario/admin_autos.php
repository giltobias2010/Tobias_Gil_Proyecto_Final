<?php
// Panel admin: tabla de autos activos con búsqueda. Solo admin.
require 'conexion.php';
require 'funciones.php';
requerir_admin();

$buscar = trim($_GET['buscar'] ?? '');
$sql = 'SELECT id, marca, modelo, anio, precio, patente, estado, imagen
        FROM autos WHERE fecha_archivado IS NULL';
$params = [];
if ($buscar !== '') {
    $sql .= ' AND (marca LIKE :marca OR modelo LIKE :modelo OR patente LIKE :patente)';
    $termino = '%' . $buscar . '%';
    $params = [':marca' => $termino, ':modelo' => $termino, ':patente' => $termino];
}
$sql .= ' ORDER BY id DESC';
$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$autos = $stmt->fetchAll();

$titulo = 'Administrar autos';
require 'encabezado.php';
?>
<h1>Autos</h1>
<p class="acciones">
  <a class="boton" href="crear_auto.php">Cargar auto</a>
  <a href="autos_archivados.php">Ver archivados</a>
</p>
<form method="GET" class="busqueda">
  <input type="search" name="buscar" maxlength="100" placeholder="Marca, modelo o patente" value="<?= escapar($buscar) ?>">
  <button type="submit">Buscar</button>
</form>

<?php if (!$autos): ?>
  <p>No se encontraron autos.</p>
<?php else: ?>
<div class="tabla-scroll">
<table>
  <thead>
    <tr><th>ID</th><th>Foto</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Precio</th><th>Patente</th><th>Estado</th><th>Acciones</th></tr>
  </thead>
  <tbody>
  <?php foreach ($autos as $a): ?>
    <tr>
      <td><?= (int) $a['id'] ?></td>
      <td><?php if ($a['imagen']): ?><img class="miniatura" src="<?= escapar($a['imagen']) ?>" alt=""><?php else: ?>-<?php endif; ?></td>
      <td><?= escapar($a['marca']) ?></td>
      <td><?= escapar($a['modelo']) ?></td>
      <td><?= (int) $a['anio'] ?></td>
      <td><?= formato_precio($a['precio']) ?></td>
      <td><?= escapar($a['patente']) ?></td>
      <td><span class="estado estado-<?= escapar($a['estado']) ?>"><?= escapar($a['estado']) ?></span></td>
      <td>
        <a href="editar_auto.php?id=<?= (int) $a['id'] ?>">Editar</a>
        <form method="POST" action="archivar_auto.php" class="en-linea"
              onsubmit="return confirm('¿Archivar este auto?');">
          <?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <button type="submit" class="boton-link">Archivar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require 'pie.php'; ?>
