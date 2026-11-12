<?php
// Inicio de sesión: verifica el hash con password_verify() y crea la sesión.
require 'conexion.php';
require 'funciones.php';

if (isset($_SESSION['usuario_id'])) { header('Location: panel.php'); exit; }

$error = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    // Solo usuarios activos (no archivados)
    $stmt = $conexion->prepare(
        'SELECT id, nombre, rol, contrasena FROM usuarios
         WHERE correo = :correo AND fecha_archivado IS NULL LIMIT 1'
    );
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
        session_regenerate_id(true); // evita la fijación de sesión
        $_SESSION['usuario_id']  = (int) $usuario['id'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        header('Location: panel.php');
        exit;
    }
    // Mismo mensaje siempre: no revela si el correo existe
    $error = 'Correo o contraseña incorrectos.';
}

$titulo = 'Ingresar';
require 'encabezado.php';
?>
<h1>Iniciar sesión</h1>
<?php if ($error): ?><p class="aviso aviso-error"><?= escapar($error) ?></p><?php endif; ?>
<form method="POST" class="formulario">
  <?= csrf_campo() ?>
  <label>Correo <input type="email" name="correo" required value="<?= escapar($correo) ?>"></label>
  <label>Contraseña <input type="password" name="contrasena" required></label>
  <button type="submit">Ingresar</button>
</form>
<p>¿No tenés cuenta? <a href="registro.php">Registrate</a>.</p>
<?php require 'pie.php'; ?>
