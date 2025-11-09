<?php
require_once 'funciones/conexion.php';
require_once 'funciones/funciones.php';
RequiereSesion();

$MiConexion = ConexionBD();

$pageTitle = 'Listado de viajes registrados';
$activePage = 'viajes_listado';

$usuarioActual = ObtenerUsuarioEnSesion();
$esChofer = isset($usuarioActual['id_nivel']) && (int) $usuarioActual['id_nivel'] === 3;
$choferFiltradoId = null;
if ($esChofer && isset($usuarioActual['id'])) {
    $choferFiltradoId = $usuarioActual['id'];
} // Se determina si es necesario filtrar los viajes por el chofer autenticado.
$viajes = Listar_Viajes($MiConexion, $choferFiltradoId); // Se obtienen los viajes aplicando el filtro según el nivel del usuario.
$permisosListado = ObtenerPermisosListadoViajes($usuarioActual);
$mostrarCosto = !empty($permisosListado['mostrar_costo']);
$mostrarMontoChofer = !empty($permisosListado['mostrar_monto_chofer']);
$mostrarPorcentajeEnMonto = !empty($permisosListado['mostrar_porcentaje_monto']);

require_once 'includes/header.php';
require_once 'includes/topbar.php';
require_once 'includes/sidebar.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Lista de viajes registrados</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">Viajes</li>
                <li class="breadcrumb-item active">Listado</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Viajes cargados</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha viaje</th>
                                <th>Destino</th>
                                <th>Camión</th>
                                <th>Chofer</th>
                                <?php if ($mostrarCosto): ?>
                                    <th>Costo viaje</th>
                                <?php endif; ?>
                                <?php if ($mostrarMontoChofer): ?>
                                    <th>Monto Chofer</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$viajes): ?>
                                <tr>
                                    <td colspan="<?php echo 5 + (int) $mostrarCosto + (int) $mostrarMontoChofer; ?>" class="text-center">No hay viajes registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($viajes as $index => $viaje): ?>
                                    <?php
                                    $montoChofer = isset($viaje['monto_chofer'])
                                        ? (float) $viaje['monto_chofer']
                                        : CalcularMontoChofer((float) $viaje['costo'], (int) $viaje['porcentaje_chofer']);
                                    $filaClase = ObtenerClaseFila($viaje['fecha_programada']);
                                    ?>
                                    <tr class="<?php echo $filaClase; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars(FormatearFechaEspaniol($viaje['fecha_programada'])); ?></td>
                                        <td><?php echo htmlspecialchars($viaje['destino']); ?></td>
                                        <td><?php echo htmlspecialchars($viaje['marca'] . ' - ' . $viaje['modelo'] . ' - ' . $viaje['patente']); ?></td>
                                        <td><?php echo htmlspecialchars($viaje['chofer_apellido'] . ', ' . $viaje['chofer_nombre']); ?></td>
                                        <?php if ($mostrarCosto): ?>
                                            <td>$ <?php echo number_format((float) $viaje['costo'], 2, ',', '.'); ?></td>
                                        <?php endif; ?>
                                        <?php if ($mostrarMontoChofer): ?>
                                            <td>
                                                $ <?php echo number_format($montoChofer, 2, ',', '.'); ?>
                                                <?php if ($mostrarPorcentajeEnMonto): ?>
                                                    (<?php echo (int) $viaje['porcentaje_chofer']; ?>%)
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once 'includes/footer.php'; ?>
