<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Registrar un nuevo viaje';
$activePage = 'viaje_carga';

$choferes = obtener_choferes();
$transportes = obtener_transportes();

$errors = [];
$success = false;
$choferId = '';
$transporteId = '';
$fechaProgramada = '';
$destino = '';
$costo = '';
$porcentaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choferId = $_POST['chofer_id'] ?? '';
    $transporteId = $_POST['transporte_id'] ?? '';
    $fechaProgramada = $_POST['fecha_programada'] ?? '';
    $destino = trim($_POST['destino'] ?? '');
    $costo = $_POST['costo'] ?? '';
    $porcentaje = $_POST['porcentaje_chofer'] ?? '';

    if (!$choferId || !in_array((string) $choferId, array_column($choferes, 'id'), true)) {
        $errors[] = 'Debes seleccionar un chofer válido.';
    }

    if (!$transporteId || !in_array((string) $transporteId, array_column($transportes, 'id'), true)) {
        $errors[] = 'Debes seleccionar un transporte válido.';
    }

    $fechaNormalizada = convertir_fecha_formulario($fechaProgramada);
    if (!$fechaNormalizada) {
        $errors[] = 'Debes ingresar una fecha programada válida.';
    }

    if (!campo_requerido($destino)) {
        $errors[] = 'El destino es obligatorio.';
    }

    $importeNormalizado = normalizar_importe((string) $costo);
    if ($importeNormalizado === null || $importeNormalizado <= 0) {
        $errors[] = 'El costo debe ser un valor numérico mayor a 0.';
    }

    if (!validar_porcentaje((string) $porcentaje)) {
        $errors[] = 'El porcentaje del chofer debe ser un número entre 0 y 100.';
    }

    if (!$errors) {
        guardar_viaje([
            'chofer_id' => (int) $choferId,
            'transporte_id' => (int) $transporteId,
            'fecha_programada' => $fechaNormalizada,
            'destino' => $destino,
            'costo' => (float) $importeNormalizado,
            'porcentaje_chofer' => (int) $porcentaje,
            'creado_por' => current_user()['id'] ?? null,
        ]);
        $success = true;
        $choferId = $transporteId = $fechaProgramada = $destino = $costo = $porcentaje = '';
        $fechaNormalizada = null;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Registrar un nuevo viaje</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">Viajes</li>
                <li class="breadcrumb-item active">Carga</li>
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
                                <i class="bi bi-check-circle me-1"></i> ¡El viaje se registró correctamente!
                            </div>
                        <?php endif; ?>
                        <form class="row g-3" method="post" action="">
                            <div class="col-12">
                                <label for="chofer_id" class="form-label">Chofer (*)</label>
                                <select class="form-select" id="chofer_id" name="chofer_id" required>
                                    <option value="">Selecciona una opción</option>
                                    <?php foreach ($choferes as $chofer): ?>
                                        <option value="<?php echo htmlspecialchars($chofer['id']); ?>" <?php echo (string) $chofer['id'] === (string) $choferId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($chofer['apellido'] . ', ' . $chofer['nombre'] . ' - DNI ' . $chofer['dni']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="transporte_id" class="form-label">Transporte (*)</label>
                                <select class="form-select" id="transporte_id" name="transporte_id" required>
                                    <option value="">Selecciona una opción</option>
                                    <?php foreach ($transportes as $transporte): ?>
                                        <option value="<?php echo htmlspecialchars($transporte['id']); ?>" <?php echo (string) $transporte['id'] === (string) $transporteId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($transporte['marca'] . ' - ' . $transporte['modelo'] . ' - ' . $transporte['patente']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="fecha_programada" class="form-label">Fecha programada (*)</label>
                                <input type="text" class="form-control" id="fecha_programada" name="fecha_programada" placeholder="dd/mm/aaaa" value="<?php echo htmlspecialchars($fechaProgramada); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="destino" class="form-label">Destino (*)</label>
                                <input type="text" class="form-control" id="destino" name="destino" value="<?php echo htmlspecialchars($destino); ?>" required>
                            </div>
                            <div class="col-6">
                                <label for="costo" class="form-label">Costo (*)</label>
                                <input type="text" class="form-control" id="costo" name="costo" value="<?php echo htmlspecialchars($costo); ?>" required>
                            </div>
                            <div class="col-6">
                                <label for="porcentaje_chofer" class="form-label">Porcentaje chofer (*)</label>
                                <input type="number" class="form-control" id="porcentaje_chofer" name="porcentaje_chofer" min="0" max="100" value="<?php echo htmlspecialchars($porcentaje); ?>" required>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-primary" type="submit">Registrar</button>
                                <a href="viaje_carga.php" class="btn btn-secondary">Limpiar Campos</a>
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
