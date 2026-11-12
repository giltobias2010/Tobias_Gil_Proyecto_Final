<?php
// Gestión de usuarios (solo admin): listado, búsqueda, registro, edición,
// archivado, reactivación y eliminación. Todo en un solo archivo.
require 'conexion.php';
require 'funciones.php';
requerir_admin();

$ver = $_GET['ver'] ?? 'activos';
if (!in_array($ver, ['activos', 'archivados', 'nuevo', 'editar'], true)) $ver = 'activos';

$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'dni' => '', 'correo' => ''];
$rol = 'usuario';
$editId = 0;
$formEnviado = false;

// ---------- Acciones (solo POST + CSRF) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';

    if (in_array($accion, ['archivar', 'reactivar', 'eliminar'], true)) {
        $id = id_post();
        try {
            if ($accion === 'archivar' && $id === (int) $_SESSION['usuario_id']) {
                mensaje_flash('No podés archivar tu propia cuenta.', 'error');
            } elseif ($accion === 'archivar') {
                $stmt = $conexion->prepare('UPDATE usuarios SET fecha_archivado = CURRENT_TIMESTAMP WHERE id = :id AND fecha_archivado IS NULL');
                $stmt->execute([':id' => $id]);
                mensaje_flash($stmt->rowCount() ? 'Usuario archivado.' : 'El usuario no existe o ya estaba archivado.', $stmt->rowCount() ? 'ok' : 'error');
            } elseif ($accion === 'reactivar') {
                // No cambia contraseña, correo ni rol
                $stmt = $conexion->prepare('UPDATE usuarios SET fecha_archivado = NULL WHERE id = :id AND fecha_archivado IS NOT NULL');
                $stmt->execute([':id' => $id]);
                mensaje_flash($stmt->rowCount() ? 'Usuario reactivado.' : 'El usuario no existe o no estaba archivado.', $stmt->rowCount() ? 'ok' : 'error');
            } else {
                // Borrado definitivo: solo usuarios ya archivados y sin compras
                $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id = :id AND fecha_archivado IS NOT NULL');
                $stmt->execute([':id' => $id]);
                mensaje_flash($stmt->rowCount() ? 'Usuario eliminado definitivamente.' : 'El usuario no existe o no está archivado.', $stmt->rowCount() ? 'ok' : 'error');
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                mensaje_flash('No se puede eliminar: el usuario tiene compras registradas.', 'error');
            } else {
                error_log('Admin usuarios: ' . $e->getMessage());
                mensaje_flash('No se pudo completar la acción.', 'error');
            }
        }
        header('Location: admin_usuarios.php?ver=' . ($accion === 'archivar' ? 'activos' : 'archivados'));
        exit;

    } elseif ($accion === 'crear' || $accion === 'editar') {
        $formEnviado = true;
        [$datos, $errores] = validar_usuario($_POST);

        if ($accion === 'crear') {
            // Registro administrativo: el rol se valida contra una lista fija
            $ver = 'nuevo';
            $contrasena = $_POST['contrasena'] ?? '';
            $rol = $_POST['rol'] ?? '';
            if (!in_array($rol, ['usuario', 'admin'], true)) $errores[] = 'El rol no es válido.';
            if (strlen($contrasena) < 8)                     $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
            $id = 0;
        } else {
            // Edición: nunca modifica rol ni contraseña
            $ver = 'editar';
            $id = id_post();
            $editId = $id;
        }

        // Unicidad de DNI y correo (al editar se excluye al mismo usuario)
        if (!$errores) {
            $stmt = $conexion->prepare('SELECT COUNT(*) FROM usuarios WHERE (dni = :dni OR correo = :correo) AND id <> :id');
            $stmt->execute([':dni' => $datos['dni'], ':correo' => $datos['correo'], ':id' => $id]);
            if ($stmt->fetchColumn() > 0) $errores[] = 'Ya existe un usuario con ese DNI o correo.';
        }

        if (!$errores) {
            try {
                if ($accion === 'crear') {
                    $stmt = $conexion->prepare(
                        'INSERT INTO usuarios (nombre, apellido, dni, correo, contrasena, rol)
                         VALUES (:nombre, :apellido, :dni, :correo, :hash, :rol)'
                    );
                    $stmt->execute([
                        ':nombre' => $datos['nombre'], ':apellido' => $datos['apellido'],
                        ':dni' => $datos['dni'], ':correo' => $datos['correo'],
                        ':hash' => password_hash($contrasena, PASSWORD_DEFAULT), ':rol' => $rol,
                    ]);
                    mensaje_flash('Usuario registrado correctamente.');
                } else {
                    $stmt = $conexion->prepare(
                        'UPDATE usuarios SET nombre = :nombre, apellido = :apellido, dni = :dni, correo = :correo
                         WHERE id = :id AND fecha_archivado IS NULL'
                    );
                    $stmt->execute([
                        ':nombre' => $datos['nombre'], ':apellido' => $datos['apellido'],
                        ':dni' => $datos['dni'], ':correo' => $datos['correo'], ':id' => $id,
                    ]);
                    mensaje_flash('Usuario actualizado.');
                }
                header('Location: admin_usuarios.php');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errores[] = 'Ya existe un usuario con ese DNI o correo.';
                } else {
                    error_log('Admin usuarios: ' . $e->getMessage());
                    $errores[] = 'No se pudo guardar el usuario.';
                }
            }
        }
    } else {
        http_response_code(400);
        exit('Solicitud inválida.');
    }
}

// ---------- Formulario de edición: carga el usuario activo ----------
if ($ver === 'editar') {
    $editId = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($editId === false || $editId === null || $editId < 1) {
        http_response_code(400);
        exit('Identificador inválido.');
    }
    $stmt = $conexion->prepare('SELECT nombre, apellido, dni, correo FROM usuarios WHERE id = :id AND fecha_archivado IS NULL LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $usuario = $stmt->fetch();
    if (!$usuario) {
        http_response_code(404);
        exit('El usuario no existe o está archivado.');
    }
    if (!$formEnviado) $datos = $usuario;
}

// ---------- Listado con búsqueda ----------
$usuarios = [];
$buscar = trim($_GET['buscar'] ?? '');
if ($ver === 'activos' || $ver === 'archivados') {
    // Nunca se selecciona la contraseña
    $sql = 'SELECT id, nombre, apellido, dni, correo, rol, fecha_archivado FROM usuarios WHERE fecha_archivado '
         . ($ver === 'activos' ? 'IS NULL' : 'IS NOT NULL');
    $params = [];
    if ($buscar !== '') {
        $sql .= ' AND (nombre LIKE :nombre OR apellido LIKE :apellido OR dni LIKE :dni OR correo LIKE :correo)';
        $termino = '%' . $buscar . '%';
        $params = [':nombre' => $termino, ':apellido' => $termino, ':dni' => $termino, ':correo' => $termino];
    }
    $sql .= $ver === 'activos' ? ' ORDER BY id' : ' ORDER BY fecha_archivado DESC';
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll();
}

$titulo = 'Administrar usuarios';
require 'encabezado.php';
?>
<?php if ($ver === 'nuevo' || $ver === 'editar'): ?>
  <h1><?= $ver === 'nuevo' ? 'Registrar usuario' : 'Editar usuario' ?></h1>
  <?php foreach ($errores as $e): ?><p class="aviso aviso-error"><?= escapar($e) ?></p><?php endforeach; ?>
  <form method="POST" action="admin_usuarios.php?ver=<?= $ver ?>" class="formulario">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="<?= $ver === 'nuevo' ? 'crear' : 'editar' ?>">
    <?php if ($ver === 'editar'): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
    <label>Nombre <input type="text" name="nombre" maxlength="60" required value="<?= escapar($datos['nombre']) ?>"></label>
    <label>Apellido <input type="text" name="apellido" maxlength="60" required value="<?= escapar($datos['apellido']) ?>"></label>
    <label>DNI <input type="text" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" required value="<?= escapar($datos['dni']) ?>"></label>
    <label>Correo <input type="email" name="correo" maxlength="120" required value="<?= escapar($datos['correo']) ?>"></label>
    <?php if ($ver === 'nuevo'): ?>
      <label>Contraseña (mínimo 8 caracteres) <input type="password" name="contrasena" required></label>
      <label>Rol
        <select name="rol">
          <option value="usuario" <?= $rol === 'usuario' ? 'selected' : '' ?>>usuario</option>
          <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>admin</option>
        </select>
      </label>
    <?php endif; ?>
    <button type="submit"><?= $ver === 'nuevo' ? 'Registrar' : 'Guardar cambios' ?></button>
    <a href="admin_usuarios.php">Cancelar</a>
  </form>

<?php else: ?>
  <h1>Usuarios <?= $ver === 'archivados' ? 'archivados' : 'activos' ?></h1>
  <p class="acciones">
    <a class="boton" href="admin_usuarios.php?ver=nuevo">Registrar usuario</a>
    <a href="admin_usuarios.php?ver=activos">Activos</a>
    <a href="admin_usuarios.php?ver=archivados">Archivados</a>
  </p>
  <form method="GET" class="busqueda">
    <input type="hidden" name="ver" value="<?= escapar($ver) ?>">
    <input type="search" name="buscar" maxlength="100" placeholder="Nombre, apellido, DNI o correo" value="<?= escapar($buscar) ?>">
    <button type="submit">Buscar</button>
  </form>

  <?php if (!$usuarios): ?>
    <p>No se encontraron usuarios.</p>
  <?php else: ?>
  <p><strong>Total:</strong> <?= count($usuarios) ?></p>
  <div class="tabla-scroll">
  <table>
    <thead><tr><th>ID</th><th>Nombre</th><th>DNI</th><th>Correo</th><th>Rol</th><?php if ($ver === 'archivados'): ?><th>Archivado el</th><?php endif; ?><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><?= (int) $u['id'] ?></td>
        <td><?= escapar($u['nombre'] . ' ' . $u['apellido']) ?></td>
        <td><?= escapar($u['dni']) ?></td>
        <td><?= escapar($u['correo']) ?></td>
        <td><?= escapar($u['rol']) ?></td>
        <?php if ($ver === 'archivados'): ?><td><?= escapar($u['fecha_archivado']) ?></td><?php endif; ?>
        <td>
          <?php if ($ver === 'activos'): ?>
            <a href="admin_usuarios.php?ver=editar&amp;id=<?= (int) $u['id'] ?>">Editar</a>
            <?php if ((int) $u['id'] !== (int) $_SESSION['usuario_id']): ?>
              <form method="POST" class="en-linea" onsubmit="return confirm('¿Archivar este usuario?');">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="accion" value="archivar">
                <button type="submit" class="boton-link">Archivar</button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <form method="POST" class="en-linea" onsubmit="return confirm('¿Reactivar este usuario?');">
              <?= csrf_campo() ?>
              <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
              <input type="hidden" name="accion" value="reactivar">
              <button type="submit" class="boton-link">Reactivar</button>
            </form>
            <form method="POST" class="en-linea" onsubmit="return confirm('Se eliminará DEFINITIVAMENTE. ¿Continuar?');">
              <?= csrf_campo() ?>
              <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
              <input type="hidden" name="accion" value="eliminar">
              <button type="submit" class="boton-link peligro">Eliminar</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php require 'pie.php'; ?>
