<?php
require_once 'funciones/conexion.php';
require_once 'funciones/funciones.php';
RequiereSesion();

if (EsChofer()) { // Se impide que los choferes accedan a la carga de viajes según los permisos definidos.
    Redireccionar('viajes_listado.php'); // Se redirige al listado para que solo vean sus propios viajes.
}

$MiConexion = ConexionBD();

$pageTitle = 'Registrar un nuevo viaje';
$activePage = 'viaje_carga';

$choferes = Listar_Choferes($MiConexion);
$transportes = Listar_Transportes($MiConexion);
$destinos = Listar_Destinos($MiConexion); // Se obtiene el listado de destinos para completar el selector correspondiente.
$usuarioSesion = ObtenerUsuarioEnSesion();
$creadoPorId = 0;
if (isset($usuarioSesion['id'])) {
    $creadoPorId = $usuarioSesion['id'];
}

$errors = array();
$success = false;
$choferId = '';
$transporteId = '';
$fechaProgramada = '';
$destinoId = ''; // Se guarda el destino seleccionado utilizando su identificador para validar y repoblar el formulario.
$costo = '';
$porcentaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['chofer_id'])) {
        $choferId = $_POST['chofer_id'];
    } else {
        $choferId = '';
    }

    if (isset($_POST['transporte_id'])) {
        $transporteId = $_POST['transporte_id'];
    } else {
        $transporteId = '';
    }

    if (isset($_POST['fecha_programada'])) {
        $fechaProgramada = $_POST['fecha_programada'];
    } else {
        $fechaProgramada = '';
    }

    if (isset($_POST['destino_id'])) { // Se captura el destino elegido en el formulario.
        $destinoId = $_POST['destino_id'];
    } else {
        $destinoId = '';
    }

    if (isset($_POST['costo'])) {
        $costo = $_POST['costo'];
    } else {
        $costo = '';
    }

    if (isset($_POST['porcentaje_chofer'])) {
        $porcentaje = $_POST['porcentaje_chofer'];
    } else {
        $porcentaje = '';
    }

    $choferValido = false; // Se prepara una bandera para verificar el chofer seleccionado.
    for ($i = 0; $i < count($choferes); $i++) { // Se recorren los choferes habilitados en busca del identificador enviado.
        if ((string) $choferes[$i]['id'] === (string) $choferId) {
            $choferValido = true;
            break;
        }
    }
    if (!$choferId || !$choferValido) { // Se valida que el chofer seleccionado exista y esté activo.
        $errors[] = 'Debes seleccionar un chofer válido.';
    }

    $transporteValido = false; // Se prepara una bandera para validar el transporte.
    for ($i = 0; $i < count($transportes); $i++) { // Se recorren los transportes activos para comprobar la selección recibida.
        if ((string) $transportes[$i]['id'] === (string) $transporteId) {
            $transporteValido = true;
            break;
        }
    }
    if (!$transporteId || !$transporteValido) { // Se comprueba que el transporte elegido sea válido y esté habilitado.
        $errors[] = 'Debes seleccionar un transporte válido.';
    }

    $fechaNormalizada = ConvertirFechaFormulario($fechaProgramada);
    if (!$fechaNormalizada) {
        $errors[] = 'Debes ingresar una fecha programada válida.';
    }

    $destinoValido = false; // Se prepara una bandera para validar el destino elegido.
    for ($i = 0; $i < count($destinos); $i++) { // Se recorren los destinos disponibles.
        if ((string) $destinos[$i]['id'] === (string) $destinoId) {
            $destinoValido = true;
            break;
        }
    }
    if (!$destinoId || !$destinoValido) { // Se valida que el destino exista en la base de datos.
        $errors[] = 'Debes seleccionar un destino válido.'; // Se notifica si la selección no es correcta.
    }

    $importeNormalizado = NormalizarImporte((string) $costo);
    if ($importeNormalizado === null || $importeNormalizado <= 0) {
        $errors[] = 'El costo debe ser un valor numérico mayor a 0.';
    }

    if (!ValidarPorcentaje((string) $porcentaje)) {
        $errors[] = 'El porcentaje del chofer debe ser un número entre 0 y 100.';
    }

    if (!$errors) {
        Insertar_Viaje(array(
            'chofer_id' => (int) $choferId,
            'transporte_id' => (int) $transporteId,
            'fecha_programada' => $fechaNormalizada,
            'destino_id' => (int) $destinoId, // Se envía el identificador del destino validado.
            'costo' => (float) $importeNormalizado,
            'porcentaje_chofer' => (int) $porcentaje,
            'creado_por' => $creadoPorId,
        ), $MiConexion);
        $success = true;
        $choferId = $transporteId = $fechaProgramada = $destinoId = $costo = $porcentaje = ''; // Se limpian los campos para permitir cargar un nuevo viaje inmediatamente.
        $fechaNormalizada = null;
    }
}

require_once 'includes/header.php';
require_once 'includes/topbar.php';
require_once 'includes/sidebar.php';
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
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-1"></i> ¡El viaje se registró correctamente!
                            </div>
                        <?php endif; ?>
                        <form class="row g-3" method="post" action="" novalidate>
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
                                <label for="destino_id" class="form-label">Destino (*)</label>
                                <select class="form-select" id="destino_id" name="destino_id" required>
                                    <option value="">Selecciona una opción</option>
                                    <?php foreach ($destinos as $destino): // Se recorre el listado de destinos para generar las opciones disponibles. ?>
                                        <option value="<?php echo htmlspecialchars($destino['id']); ?>" <?php echo (string) $destino['id'] === (string) $destinoId ? 'selected' : ''; ?>><?php echo htmlspecialchars($destino['denominacion']); ?></option>
                                    <?php endforeach; ?>
                                </select>
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
<?php require_once 'includes/footer.php'; ?>
