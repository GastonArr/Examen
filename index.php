<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Panel de Administración';
$activePage = 'dashboard';
$user = current_user();
$userFullName = user_full_name($user);
$userDenominacion = nivel_denominacion($user['id_nivel'] ?? null);
$funcionesPermitidas = descripcion_funciones_por_nivel($user['id_nivel'] ?? null);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
require_once __DIR__ . '/includes/sidebar.php';
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
