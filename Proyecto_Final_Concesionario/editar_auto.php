<?php
// Edición de un auto activo. Solo admin.
require 'conexion.php';
require 'funciones.php';
requerir_admin();

// El id viene por GET (al abrir) o por POST (al guardar); se valida como entero
$id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador inválido.');
}

$stmt = $conexion->prepare('SELECT * FROM autos WHERE id = :id AND fecha_archivado IS NULL LIMIT 1');
$stmt->execute([':id' => $id]);
$auto = $stmt->fetch();
if (!$auto) {
    http_response_code(404);
    exit('El auto no existe o está archivado.');
}

$errores = [];
$datos = $auto;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    [$datos, $errores] = validar_auto($_POST);

    // Patente única (excluyendo este mismo auto)
    if (!$errores) {
        $stmt = $conexion->prepare('SELECT COUNT(*) FROM autos WHERE patente = :patente AND id <> :id');
        $stmt->execute([':patente' => $datos['patente'], ':id' => $id]);
        if ($stmt->fetchColumn() > 0) $errores[] = 'Otro auto ya usa esa patente.';
    }

    // Imagen nueva (opcional): si no se sube, se conserva la actual
    $rutaNueva = null;
    if (!$errores) {
        $errorImg = null;
        $rutaNueva = subir_imagen($_FILES['imagen'] ?? [], $errorImg);
        if ($errorImg) $errores[] = $errorImg;
    }

    if (!$errores) {
        try {
            $stmt = $conexion->prepare(
                'UPDATE autos SET marca = :marca, modelo = :modelo, anio = :anio,
                        precio = :precio, patente = :patente, estado = :estado,
                        color = :color, kilometraje = :km, combustible = :combustible,
                        transmision = :transmision, descripcion = :descripcion, imagen = :imagen
                 WHERE id = :id AND fecha_archivado IS NULL'
            );
            $stmt->execute([
                ':marca' => $datos['marca'], ':modelo' => $datos['modelo'],
                ':anio' => $datos['anio'], ':precio' => $datos['precio'],
                ':patente' => $datos['patente'], ':estado' => $datos['estado'],
                ':color' => $datos['color'], ':km' => $datos['kilometraje'],
                ':combustible' => $datos['combustible'], ':transmision' => $datos['transmision'],
                ':descripcion' => $datos['descripcion'],
                ':imagen' => $rutaNueva ?? $auto['imagen'],
                ':id' => $id,
            ]);
            if ($rutaNueva) borrar_imagen($auto['imagen']); // elimina la foto anterior
            mensaje_flash('Auto actualizado.');
            header('Location: admin_autos.php');
            exit;
        } catch (PDOException $e) {
            error_log('Editar auto: ' . $e->getMessage());
            borrar_imagen($rutaNueva);
            $errores[] = 'No se pudo actualizar el auto.';
        }
    }
    $datos['imagen'] = $auto['imagen'];
}

$titulo = 'Editar auto';
require 'encabezado.php';
?>
<h1>Editar auto</h1>
<?php foreach ($errores as $e): ?><p class="aviso aviso-error"><?= escapar($e) ?></p><?php endforeach; ?>
<form method="POST" enctype="multipart/form-data" class="formulario">
  <?= csrf_campo() ?>
  <input type="hidden" name="id" value="<?= (int) $id ?>">
  <label>Marca <input type="text" name="marca" maxlength="60" required value="<?= escapar($datos['marca']) ?>"></label>
  <label>Modelo <input type="text" name="modelo" maxlength="60" required value="<?= escapar($datos['modelo']) ?>"></label>
  <label>Año <input type="number" name="anio" min="1950" required value="<?= escapar((string) $datos['anio']) ?>"></label>
  <label>Precio <input type="number" name="precio" min="1" step="0.01" required value="<?= escapar((string) $datos['precio']) ?>"></label>
  <label>Patente <input type="text" name="patente" maxlength="10" required value="<?= escapar($datos['patente']) ?>"></label>
  <label>Estado
    <select name="estado">
      <?php foreach (ESTADOS_AUTO as $op): ?>
        <option value="<?= escapar($op) ?>" <?= $datos['estado'] === $op ? 'selected' : '' ?>><?= escapar($op) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Color <input type="text" name="color" maxlength="30" value="<?= escapar($datos['color'] ?? '') ?>"></label>
  <label>Kilometraje <input type="number" name="kilometraje" min="0" required value="<?= escapar((string) $datos['kilometraje']) ?>"></label>
  <label>Combustible
    <select name="combustible">
      <?php foreach (COMBUSTIBLES as $op): ?>
        <option value="<?= escapar($op) ?>" <?= $datos['combustible'] === $op ? 'selected' : '' ?>><?= escapar($op) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Transmisión
    <select name="transmision">
      <?php foreach (TRANSMISIONES as $op): ?>
        <option value="<?= escapar($op) ?>" <?= $datos['transmision'] === $op ? 'selected' : '' ?>><?= escapar($op) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Descripción (opcional, máximo 1000 caracteres)
    <textarea name="descripcion" rows="4" maxlength="1000"><?= escapar($datos['descripcion'] ?? '') ?></textarea>
  </label>
  <?php if (!empty($auto['imagen'])): ?>
    <p>Foto actual:<br><img class="miniatura" src="<?= escapar($auto['imagen']) ?>" alt="Foto actual"></p>
  <?php endif; ?>
  <label><?= empty($auto['imagen']) ? 'Foto' : 'Reemplazar foto' ?> (JPG, PNG o WEBP, máximo 2 MB, opcional)
    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
  </label>
  <button type="submit">Guardar cambios</button>
  <a href="admin_autos.php">Cancelar</a>
</form>
<?php require 'pie.php'; ?>
