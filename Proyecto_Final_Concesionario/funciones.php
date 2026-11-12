<?php
// Funciones compartidas: sesión, escapado, CSRF, permisos, validaciones e imágenes.

// Sesión con cookie protegida
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,      // JavaScript no puede leer la cookie
        'samesite' => 'Strict',  // no se envía desde otros sitios
        'secure'   => !empty($_SERVER['HTTPS']), // solo por HTTPS cuando está disponible
    ]);
    session_start();
}

// Listas controladas (se usan para validar y para armar los <select>)
const ESTADOS_AUTO  = ['disponible', 'reservado', 'vendido'];
const COMBUSTIBLES  = ['Nafta', 'Diésel', 'GNC', 'Híbrido', 'Eléctrico'];
const TRANSMISIONES = ['Manual', 'Automática'];

// Escapa texto antes de mostrarlo en HTML (evita XSS)
function escapar(?string $valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Precio con formato argentino: $ 28.500.000
function formato_precio($valor): string {
    return '$ ' . number_format((float) $valor, 0, ',', '.');
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string {
    return '<input type="hidden" name="csrf" value="' . escapar(csrf_token()) . '">';
}

function csrf_validar(): void {
    $enviado = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(403);
        exit('Token de seguridad inválido.');
    }
}

// ---------- Mensajes entre páginas ----------
function mensaje_flash(string $texto, string $tipo = 'ok'): void {
    $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
}

function leer_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---------- Control de acceso ----------
// Comprueba la sesión y confirma en la base que el usuario sigue activo.
// El rol se vuelve a leer de la base: si lo cambian o archivan, se nota enseguida.
function requerir_login(): void {
    global $conexion;
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
    if (isset($conexion)) {
        $stmt = $conexion->prepare('SELECT rol FROM usuarios WHERE id = :id AND fecha_archivado IS NULL LIMIT 1');
        $stmt->execute([':id' => $_SESSION['usuario_id']]);
        $fila = $stmt->fetch();
        if (!$fila) {
            $_SESSION = [];
            session_destroy();
            header('Location: login.php');
            exit;
        }
        $_SESSION['usuario_rol'] = $fila['rol'];
    }
}

function requerir_admin(): void {
    requerir_login();
    if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

// Solo permite el método POST (para acciones que modifican datos)
function requerir_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método no permitido.');
    }
}

// Lee y valida el id enviado por POST (entero mayor a cero)
function id_post(): int {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        http_response_code(400);
        exit('Identificador inválido.');
    }
    return $id;
}

// ---------- Validación de usuarios (alta y edición hechas por el admin) ----------
// Devuelve [datos limpios, lista de errores]
function validar_usuario(array $post): array {
    $errores  = [];
    $nombre   = trim($post['nombre'] ?? '');
    $apellido = trim($post['apellido'] ?? '');
    $dni      = trim($post['dni'] ?? '');
    $correo   = trim($post['correo'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 60)     $errores[] = 'El nombre es obligatorio (máximo 60 caracteres).';
    if ($apellido === '' || mb_strlen($apellido) > 60) $errores[] = 'El apellido es obligatorio (máximo 60 caracteres).';
    if (!preg_match('/^\d{7,8}$/', $dni))              $errores[] = 'El DNI debe tener 7 u 8 números.';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) $errores[] = 'El correo no es válido.';

    return [compact('nombre', 'apellido', 'dni', 'correo'), $errores];
}

// ---------- Validación de autos ----------
// Devuelve [datos limpios, lista de errores]
function validar_auto(array $post): array {
    $errores     = [];
    $marca       = trim($post['marca'] ?? '');
    $modelo      = trim($post['modelo'] ?? '');
    $anio        = filter_var($post['anio'] ?? '', FILTER_VALIDATE_INT);
    $precio      = filter_var($post['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $patente     = strtoupper(preg_replace('/[\s-]/', '', $post['patente'] ?? ''));
    $estado      = $post['estado'] ?? '';
    $color       = trim($post['color'] ?? '');
    $kilometraje = filter_var($post['kilometraje'] ?? '', FILTER_VALIDATE_INT);
    $combustible = $post['combustible'] ?? '';
    $transmision = $post['transmision'] ?? '';
    $descripcion = trim($post['descripcion'] ?? '');

    if ($marca === '' || mb_strlen($marca) > 60)   $errores[] = 'La marca es obligatoria (máximo 60 caracteres).';
    if ($modelo === '' || mb_strlen($modelo) > 60) $errores[] = 'El modelo es obligatorio (máximo 60 caracteres).';
    if ($anio === false || $anio < 1950 || $anio > (int) date('Y') + 1) $errores[] = 'El año no es válido.';
    if ($precio === false || $precio <= 0)         $errores[] = 'El precio debe ser mayor a cero.';
    if (!preg_match('/^[A-Z0-9]{6,8}$/', $patente)) $errores[] = 'La patente debe tener entre 6 y 8 letras o números.';
    if (!in_array($estado, ESTADOS_AUTO, true))    $errores[] = 'El estado no es válido.';
    if (mb_strlen($color) > 30)                    $errores[] = 'El color no puede superar 30 caracteres.';
    if ($kilometraje === false || $kilometraje < 0) $errores[] = 'El kilometraje no es válido.';
    if (!in_array($combustible, COMBUSTIBLES, true))   $errores[] = 'El combustible no es válido.';
    if (!in_array($transmision, TRANSMISIONES, true))  $errores[] = 'La transmisión no es válida.';
    if (mb_strlen($descripcion) > 1000)            $errores[] = 'La descripción no puede superar 1000 caracteres.';

    // Campos opcionales vacíos se guardan como NULL
    $color       = $color === '' ? null : $color;
    $descripcion = $descripcion === '' ? null : $descripcion;

    return [compact('marca', 'modelo', 'anio', 'precio', 'patente', 'estado',
                    'color', 'kilometraje', 'combustible', 'transmision', 'descripcion'), $errores];
}

// ---------- Subida segura de imágenes ----------
// Devuelve la ruta relativa guardada, o null si no se envió imagen.
// Si hay un problema, agrega el mensaje a $error.
function subir_imagen(array $archivo, ?string &$error): ?string {
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // la imagen es opcional
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'No se pudo recibir la imagen.';
        return null;
    }
    if ($archivo['size'] > 2 * 1024 * 1024) {
        $error = 'La imagen supera el máximo de 2 MB.';
        return null;
    }
    // Tipo REAL del archivo (no se confía en la extensión ni en el navegador)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $tipoReal = $finfo->file($archivo['tmp_name']);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($permitidos[$tipoReal])) {
        $error = 'Formato no permitido. Usá JPG, PNG o WEBP.';
        return null;
    }
    // Nombre aleatorio con extensión de la lista controlada
    $nombre  = bin2hex(random_bytes(16)) . '.' . $permitidos[$tipoReal];
    $destino = __DIR__ . '/uploads/autos/' . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        $error = 'No se pudo guardar la imagen en el servidor.';
        return null;
    }
    return 'uploads/autos/' . $nombre;
}

// Borra una imagen anterior (solo dentro de uploads/autos/)
function borrar_imagen(?string $ruta): void {
    if ($ruta && str_starts_with($ruta, 'uploads/autos/')) {
        $archivo = __DIR__ . '/uploads/autos/' . basename($ruta);
        if (is_file($archivo)) {
            unlink($archivo);
        }
    }
}
