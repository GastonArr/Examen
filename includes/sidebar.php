<?php
require_once __DIR__ . '/functions.php';
$currentPage = $activePage ?? '';
?>
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'dashboard' ? '' : 'collapsed'; ?>" href="index.php">
                <i class="bi bi-grid"></i>
                <span>Panel</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-bs-target="#transportes-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-truck"></i><span>Transportes</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="transportes-nav" class="nav-content collapse <?php echo in_array($currentPage, ['choferes'], true) ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="chofer_carga.php" class="<?php echo $currentPage === 'choferes' ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-plus"></i><span>Cargar nuevo chofer</span>
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-bs-target="#viajes-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-globe2"></i><span>Viajes</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="viajes-nav" class="nav-content collapse <?php echo in_array($currentPage, ['viaje_carga', 'viajes_listado'], true) ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="viaje_carga.php" class="<?php echo $currentPage === 'viaje_carga' ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-plus"></i><span>Cargar nuevo</span>
                    </a>
                </li>
                <li>
                    <a href="viajes_listado.php" class="<?php echo $currentPage === 'viajes_listado' ? 'active' : ''; ?>">
                        <i class="bi bi-layout-text-window-reverse"></i><span>Listado de viajes</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>
