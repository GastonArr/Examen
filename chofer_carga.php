<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Registrar un nuevo chofer';
$activePage = 'choferes';

$errors = [];
$success = false;
$apellido = '';
$nombre = '';
$dni = '';
$usuarioForm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apellido = trim($_POST['apellido'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $usuarioForm = trim($_POST['usuario'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    if (!campo_requerido($apellido)) {
        $errors[] = 'El apellido es obligatorio.';
    }

    if (!campo_requerido($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (!campo_requerido($dni)) {
        $errors[] = 'El DNI es obligatorio.';
    } elseif (!validar_dni($dni)) {
        $errors[] = 'El DNI debe tener 7 u 8 dígitos numéricos.';
    } elseif (dni_existe($dni)) {
        $errors[] = 'El DNI ingresado ya se encuentra registrado.';
    }

    if (!campo_requerido($usuarioForm)) {
        $errors[] = 'El usuario es obligatorio.';
    } elseif (usuario_existe($usuarioForm)) {
        $errors[] = 'El usuario ingresado ya existe.';
    }

    if (!campo_requerido($clave)) {
        $errors[] = 'La clave es obligatoria.';
    } elseif (strlen($clave) < 6) {
        $errors[] = 'La clave debe tener al menos 6 caracteres.';
    }

    if (!$errors) {
        guardar_chofer([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => $dni,
            'usuario' => $usuarioForm,
            'clave' => $clave,
        ]);
        $success = true;
        $apellido = $nombre = $dni = $usuarioForm = '';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Registrar un nuevo chofer</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">Transportes</li>
                <li class="breadcrumb-item active">Carga Chofer</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ingresa los datos</h5>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-1"></i> Los campos indicados con (*) son requeridos
                        </div>
                        <?php if ($errors): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-1"></i> ¡Los datos se guardaron correctamente!
                            </div>
                        <?php endif; ?>
                        <form class="row g-3" method="post" action="">
                            <div class="col-12">
                                <label for="apellido" class="form-label">Apellido (*)</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="nombre" class="form-label">Nombre (*)</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="dni" class="form-label">DNI (*)</label>
                                <input type="text" class="form-control" id="dni" name="dni" value="<?php echo htmlspecialchars($dni); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="usuario" class="form-label">Usuario (*)</label>
                                <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuarioForm); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="clave" class="form-label">Clave (*)</label>
                                <input type="password" class="form-control" id="clave" name="clave" required>
                                <div class="form-text">Debe contener al menos 6 caracteres.</div>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-primary" type="submit">Registrar</button>
                                <a href="chofer_carga.php" class="btn btn-secondary">Limpiar Campos</a>
                                <a href="index.php" class="text-primary fw-bold">Volver al index</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
