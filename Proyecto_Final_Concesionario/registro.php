<?php
// Registro de usuarios nuevos. Siempre se crean con rol 'usuario'.
require 'conexion.php';
require 'funciones.php';

if (isset($_SESSION['usuario_id'])) { header('Location: panel.php'); exit; }

$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'dni' => '', 'correo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $datos['nombre']   = trim($_POST['nombre'] ?? '');
    $datos['apellido'] = trim($_POST['apellido'] ?? '');
    $datos['dni']      = trim($_POST['dni'] ?? '');
    $datos['correo']   = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $repetir    = $_POST['repetir'] ?? '';

    // Validaciones en el servidor
    if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 60)     $errores[] = 'El nombre es obligatorio.';
    if ($datos['apellido'] === '' || mb_strlen($datos['apellido']) > 60) $errores[] = 'El apellido es obligatorio.';
    if (!preg_match('/^\d{7,8}$/', $datos['dni']))                       $errores[] = 'El DNI debe tener 7 u 8 números.';
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL))            $errores[] = 'El correo no es válido.';
    if (strlen($contrasena) < 8)                                         $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($contrasena !== $repetir)                                        $errores[] = 'Las contraseñas no coinciden.';

    // Evitar duplicados de DNI y correo
    if (!$errores) {
        $stmt = $conexion->prepare('SELECT COUNT(*) FROM usuarios WHERE dni = :dni OR correo = :correo');
        $stmt->execute([':dni' => $datos['dni'], ':correo' => $datos['correo']]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'Ya existe un usuario con ese DNI o correo.';
        }
    }

    if (!$errores) {
        try {
            $stmt = $conexion->prepare(
                "INSERT INTO usuarios (nombre, apellido, dni, correo, contrasena, rol)
                VALUES (:nombre, :apellido, :dni, :correo, :hash, 'usuario')"
            );
            $stmt->execute([
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':dni'      => $datos['dni'],
                ':correo'   => $datos['correo'],
                ':hash'     => password_hash($contrasena, PASSWORD_DEFAULT),
            ]);
            mensaje_flash('Cuenta creada. Ya podés iniciar sesión.');
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                // Violación de UNIQUE (carrera entre dos registros iguales)
                $errores[] = 'Ya existe un usuario con ese DNI o correo.';
            } else {
                error_log('Registro: ' . $e->getMessage());
                $errores[] = 'No se pudo crear la cuenta. Intentá de nuevo.';
            }
        }
    }
}

$titulo = 'Registro';
require 'encabezado.php';
?>
<h1>Crear cuenta</h1>
<?php foreach ($errores as $e): ?><p class="aviso aviso-error"><?= escapar($e) ?></p><?php endforeach; ?>
<form method="POST" class="formulario">
  <?= csrf_campo() ?>
  <label>Nombre <input type="text" name="nombre" maxlength="60" required value="<?= escapar($datos['nombre']) ?>"></label>
  <label>Apellido <input type="text" name="apellido" maxlength="60" required value="<?= escapar($datos['apellido']) ?>"></label>
  <label>DNI <input type="text" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" required value="<?= escapar($datos['dni']) ?>"></label>
  <label>Correo <input type="email" name="correo" maxlength="120" required value="<?= escapar($datos['correo']) ?>"></label>
  <label>Contraseña (mínimo 8 caracteres) <input type="password" name="contrasena" required></label>
  <label>Repetir contraseña <input type="password" name="repetir" required></label>
  <button type="submit">Registrarme</button>
</form>
<p>¿Ya tenés cuenta? <a href="login.php">Ingresá acá</a>.</p>
<?php require 'pie.php'; ?>
