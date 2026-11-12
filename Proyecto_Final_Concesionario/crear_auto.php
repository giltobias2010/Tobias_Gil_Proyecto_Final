<?php
// Alta de un auto con imagen opcional. Solo admin.
require 'conexion.php';
require 'funciones.php';
requerir_admin();

$errores = [];
$datos = ['marca' => '', 'modelo' => '', 'anio' => '', 'precio' => '', 'patente' => '', 'estado' => 'disponible',
          'color' => '', 'kilometraje' => 0, 'combustible' => 'Nafta', 'transmision' => 'Manual', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    [$datos, $errores] = validar_auto($_POST);

    // Patente única
    if (!$errores) {
        $stmt = $conexion->prepare('SELECT COUNT(*) FROM autos WHERE patente = :patente');
        $stmt->execute([':patente' => $datos['patente']]);
        if ($stmt->fetchColumn() > 0) $errores[] = 'Ya existe un auto con esa patente.';
    }

    // Imagen (opcional)
    $ruta = null;
    if (!$errores) {
        $errorImg = null;
        $ruta = subir_imagen($_FILES['imagen'] ?? [], $errorImg);
        if ($errorImg) $errores[] = $errorImg;
    }

    if (!$errores) {
        try {
            $stmt = $conexion->prepare(
                'INSERT INTO autos (marca, modelo, anio, precio, patente, estado, color, kilometraje, combustible, transmision, descripcion, imagen)
                 VALUES (:marca, :modelo, :anio, :precio, :patente, :estado, :color, :km, :combustible, :transmision, :descripcion, :imagen)'
            );
            $stmt->execute([
                ':marca' => $datos['marca'], ':modelo' => $datos['modelo'],
                ':anio' => $datos['anio'], ':precio' => $datos['precio'],
                ':patente' => $datos['patente'], ':estado' => $datos['estado'],
                ':color' => $datos['color'], ':km' => $datos['kilometraje'],
                ':combustible' => $datos['combustible'], ':transmision' => $datos['transmision'],
                ':descripcion' => $datos['descripcion'], ':imagen' => $ruta,
            ]);
            mensaje_flash('Auto cargado correctamente.');
            header('Location: admin_autos.php');
            exit;
        } catch (PDOException $e) {
            error_log('Crear auto: ' . $e->getMessage());
            borrar_imagen($ruta);
            $errores[] = 'No se pudo guardar el auto.';
        }
    }
}

$titulo = 'Nuevo auto';
require 'encabezado.php';
?>
<h1>Cargar auto</h1>
<?php foreach ($errores as $e): ?><p class="aviso aviso-error"><?= escapar($e) ?></p><?php endforeach; ?>
<form method="POST" enctype="multipart/form-data" class="formulario">
  <?= csrf_campo() ?>
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
  <label>Foto (JPG, PNG o WEBP, máximo 2 MB, opcional)
    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
  </label>
  <button type="submit">Guardar auto</button>
  <a href="admin_autos.php">Cancelar</a>
</form>
<?php require 'pie.php'; ?>
