<?php
// Panel personal: datos del usuario, sus autos y catálogo de autos activos.
require 'conexion.php';
require 'funciones.php';
requerir_login(); // también confirma que el usuario siga activo

$stmt = $conexion->prepare(
    'SELECT nombre, apellido, dni, correo, rol FROM usuarios
     WHERE id = :id AND fecha_archivado IS NULL LIMIT 1'
);
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Catálogo: autos activos con su información
$autos = $conexion->query(
    "SELECT id, marca, modelo, anio, precio, estado, color, kilometraje, combustible, transmision, descripcion, imagen
     FROM autos WHERE fecha_archivado IS NULL
     ORDER BY FIELD(estado, 'disponible', 'reservado', 'vendido'), creado_en DESC"
)->fetchAll();

// Autos que son de este usuario (sus compras)
$stmt = $conexion->prepare(
    'SELECT c.precio, c.fecha_compra, a.marca, a.modelo, a.anio, a.patente, a.imagen
     FROM compras c JOIN autos a ON a.id = c.auto_id
     WHERE c.usuario_id = :id ORDER BY c.fecha_compra DESC'
);
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$misAutos = $stmt->fetchAll();

$titulo = 'Mi panel';
require 'encabezado.php';
?>
<h1>Hola, <?= escapar($usuario['nombre']) ?></h1>
<section class="tarjeta">
  <h2>Mis datos</h2>
  <p><strong>Nombre:</strong> <?= escapar($usuario['nombre'] . ' ' . $usuario['apellido']) ?></p>
  <p><strong>DNI:</strong> <?= escapar($usuario['dni']) ?></p>
  <p><strong>Correo:</strong> <?= escapar($usuario['correo']) ?></p>
  <p><strong>Rol:</strong> <?= escapar($usuario['rol']) ?></p>
</section>

<h2>Mis autos</h2>
<?php if (!$misAutos): ?>
  <p>Todavía no compraste ningún auto.</p>
<?php else: ?>
  <div class="catalogo">
    <?php foreach ($misAutos as $c): ?>
      <article class="auto">
        <?php if ($c['imagen']): ?>
          <img src="<?= escapar($c['imagen']) ?>" alt="<?= escapar($c['marca'] . ' ' . $c['modelo']) ?>">
        <?php else: ?>
          <div class="sin-foto">Sin foto</div>
        <?php endif; ?>
        <div class="auto-info">
          <h3><?= escapar($c['marca'] . ' ' . $c['modelo']) ?></h3>
          <p class="datos-auto">Año <?= (int) $c['anio'] ?> · Patente <?= escapar($c['patente']) ?></p>
          <p class="precio"><?= formato_precio($c['precio']) ?></p>
          <p class="datos-auto">Comprado el <?= escapar($c['fecha_compra']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2>Catálogo del concesionario</h2>
<p><strong>Total:</strong> <?= count($autos) ?></p>
<?php if (empty($autos)): ?>
  <p>Todavía no hay autos cargados.</p>
<?php else: ?>
  <div class="catalogo">
    <?php foreach ($autos as $a): ?>
      <article class="auto">
        <?php if ($a['imagen']): ?>
          <img src="<?= escapar($a['imagen']) ?>" alt="<?= escapar($a['marca'] . ' ' . $a['modelo']) ?>">
        <?php else: ?>
          <div class="sin-foto">Sin foto</div>
        <?php endif; ?>
        <div class="auto-info">
          <span class="estado estado-<?= escapar($a['estado']) ?>"><?= escapar($a['estado']) ?></span>
          <h3><?= escapar($a['marca'] . ' ' . $a['modelo']) ?></h3>
          <p class="datos-auto">
            <?= (int) $a['anio'] ?> · <?= number_format((int) $a['kilometraje'], 0, ',', '.') ?> km<br>
            <?= escapar($a['combustible']) ?> · <?= escapar($a['transmision']) ?>
            <?php if ($a['color']): ?> · <?= escapar($a['color']) ?><?php endif; ?>
          </p>
          <?php if ($a['descripcion']): ?><p class="datos-auto"><?= escapar($a['descripcion']) ?></p><?php endif; ?>
          <p class="precio"><?= formato_precio($a['precio']) ?></p>
          <?php if ($a['estado'] === 'disponible'): ?>
            <form method="POST" action="comprar_auto.php"
                  onsubmit="return confirm('¿Confirmás la compra de este auto?');">
              <?= csrf_campo() ?>
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <button type="submit">Comprar</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require 'pie.php'; ?>
