<?php
// Encabezado común. Usar: $titulo = 'Texto'; require 'encabezado.php';
$rolActual = $_SESSION['usuario_rol'] ?? null;
$flash = leer_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= escapar($titulo ?? 'Concesionario') ?> · Concesionario</title>
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="barra">
  <a class="marca" href="panel.php">Concesionario <span>de autos</span></a>
  <nav>
    <?php if (isset($_SESSION['usuario_id'])): ?>
      <a href="panel.php">Mi panel</a>
      <?php if ($rolActual === 'admin'): ?>
        <a href="admin_autos.php">Autos</a>
        <a href="admin_usuarios.php">Usuarios</a>
        <a href="admin_compras.php">Compras</a>
      <?php endif; ?>
      <form method="POST" action="cerrar_sesion.php" class="en-linea">
        <?= csrf_campo() ?>
        <button type="submit" class="boton-link">Cerrar sesión</button>
      </form>
    <?php else: ?>
      <a href="login.php">Ingresar</a>
      <a href="registro.php">Registrarse</a>
    <?php endif; ?>
  </nav>
</header>
<main class="contenedor">
<?php if ($flash): ?>
  <p class="aviso <?= $flash['tipo'] === 'ok' ? 'aviso-ok' : 'aviso-error' ?>"><?= escapar($flash['texto']) ?></p>
<?php endif; ?>
