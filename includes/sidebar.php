<?php
$currentPage = '';
if (isset($activePage)) { // Se obtiene la página actual para asignar estilos activos en el menú.
    $currentPage = $activePage;
}
$esAdminActual = EsAdministrador(); // Se determina si el usuario autenticado es administrador.
$esOperadorActual = EsOperador(); // Se verifica si el usuario pertenece al nivel de operadores.

$mostrarTransportes = $esAdminActual || $esOperadorActual; // Se habilita el menú de transportes para administradores y operadores.
$mostrarChoferes = $esAdminActual; // Solo los administradores pueden cargar choferes.
$mostrarCargaViajes = $esAdminActual || $esOperadorActual; // Administradores y operadores pueden crear viajes.

$transportNavPages = array(); // Se arma el arreglo de páginas que pertenecen al grupo Transportes.
if ($mostrarTransportes) { // Se incluye la carga de camiones si el usuario tiene permiso.
    $transportNavPages[] = 'camion_carga';
}
if ($mostrarChoferes) { // Se incluye la carga de choferes únicamente para administradores.
    $transportNavPages[] = 'choferes';
}

$viajesNavPages = array(); // Se arma el arreglo de páginas correspondientes al grupo Viajes.
if ($mostrarCargaViajes) { // Se incluye la carga de viajes según los permisos.
    $viajesNavPages[] = 'viaje_carga';
}
$viajesNavPages[] = 'viajes_listado'; // Siempre se incluye el listado porque todos los niveles pueden verlo.

$transportNavAbierto = in_array($currentPage, $transportNavPages, true); // Se determina si el submenú de transportes debe mostrarse expandido.
$viajesNavAbierto = in_array($currentPage, $viajesNavPages, true); // Se evalúa si el submenú de viajes debe mostrarse expandido.
?>
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'dashboard' ? '' : 'collapsed'; ?>" href="index.php">
                <i class="bi bi-grid"></i>
                <span>Panel</span>
            </a>
        </li>

        <?php if ($mostrarTransportes || $mostrarChoferes): // Se muestra el bloque de transportes solo si el usuario tiene permiso. ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $transportNavAbierto ? '' : 'collapsed'; ?>" data-bs-target="#transportes-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-truck"></i><span>Transportes</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="transportes-nav" class="nav-content collapse <?php echo $transportNavAbierto ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
                    <?php if ($mostrarTransportes): // Se agrega la opción de cargar transportes para administradores y operadores. ?>
                        <li>
                            <a href="camion_carga.php" class="<?php echo $currentPage === 'camion_carga' ? 'active' : ''; ?>">
                                <i class="bi bi-file-earmark-plus"></i><span>Cargar nuevo transporte</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($mostrarChoferes): // Se agrega la opción de cargar choferes únicamente a los administradores. ?>
                        <li>
                            <a href="chofer_carga.php" class="<?php echo $currentPage === 'choferes' ? 'active' : ''; ?>">
                                <i class="bi bi-person-plus"></i><span>Cargar nuevo chofer</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link <?php echo $viajesNavAbierto ? '' : 'collapsed'; ?>" data-bs-target="#viajes-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-globe2"></i><span>Viajes</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="viajes-nav" class="nav-content collapse <?php echo $viajesNavAbierto ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
                <?php if ($mostrarCargaViajes): // Se muestra el enlace de carga de viajes para administradores y operadores. ?>
                    <li>
                        <a href="viaje_carga.php" class="<?php echo $currentPage === 'viaje_carga' ? 'active' : ''; ?>">
                            <i class="bi bi-file-earmark-plus"></i><span>Cargar nuevo</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="viajes_listado.php" class="<?php echo $currentPage === 'viajes_listado' ? 'active' : ''; ?>">
                        <i class="bi bi-layout-text-window-reverse"></i><span>Listado de viajes</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>
