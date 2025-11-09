<?php
require_once 'funciones/conexion.php';
require_once 'funciones/funciones.php';
RequiereSesion();

$pageTitle = 'Panel de Administración';
$activePage = 'dashboard';
$user = ObtenerUsuarioEnSesion();
$userFullName = NombreCompletoUsuario($user);
$userNivel = null;
if (isset($user['id_nivel'])) {
    $userNivel = $user['id_nivel'];
}
$userDenominacion = DenominacionNivel($userNivel);
$funcionesPermitidas = DescripcionFuncionesNivel($userNivel);

require_once 'includes/header.php';
require_once 'includes/topbar.php';
require_once 'includes/sidebar.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Bienvenido</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Panel</li>
            </ol>
        </nav>
    </div>
    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Hola, <?php echo htmlspecialchars($userFullName); ?> (<?php echo htmlspecialchars($userDenominacion); ?>)!</h5>
                        <p class="card-text">Desde este panel podrás gestionar la operación diaria del sistema. Según tu función, podrás gestionar: <?php echo htmlspecialchars($funcionesPermitidas); ?>.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once 'includes/footer.php'; ?>
