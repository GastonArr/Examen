<?php
require_once 'funciones/conexion.php';
require_once 'funciones/funciones.php';

if (UsuarioEstaLogueado()) {
    Redireccionar('index.php');
}

$MiConexion = ConexionBD();

$pageTitle = 'Panel de Administración - Login';
$Mensaje = '';
$usuario = '';

if (!empty($_POST['BotonLogin'])) {
    if (isset($_POST['usuario'])) {
        $usuario = strtolower(trim($_POST['usuario']));
    } else {
        $usuario = '';
    }

    if (isset($_POST['clave'])) {
        $clave = trim($_POST['clave']);
    } else {
        $clave = '';
    }

    if ($usuario === '' || $clave === '') {
        $Mensaje = 'Debes ingresar el usuario y la clave.';
    } else {
        $UsuarioLogueado = DatosLogin($usuario, $clave, $MiConexion);

        if (!empty($UsuarioLogueado)) {
            if (isset($UsuarioLogueado['ACTIVO']) && $UsuarioLogueado['ACTIVO'] == 0) {
                $Mensaje = 'Ud. no se encuentra activo en el sistema.';
            } else {
                GuardarSesionUsuario($UsuarioLogueado);
                Redireccionar('index.php');
            }
        } else {
            $Mensaje = 'Datos incorrectos, ingresa nuevamente.';
        }
    }
}

require_once 'includes/header.php';
?>
<main>
    <div class="container">
        <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                        <div class="d-flex justify-content-center py-4">
                            <a href="login.php" class="logo d-flex align-items-center w-auto">
                                <img src="assets/img/logo.png" alt="">
                                <span class="d-none d-lg-block">Panel de Administración</span>
                            </a>
                        </div>
                        <div class="card mb-3 w-100">
                            <div class="card-body">
                                <div class="pt-4 pb-2">
                                    <h5 class="card-title text-center pb-0 fs-4">Ingresa tu cuenta</h5>
                                    <p class="text-center small">Ingresa tus datos de usuario y clave</p>
                                </div>
                                <?php if (!empty($Mensaje)): ?>
                                    <div class="alert alert-warning" role="alert">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        <?php echo htmlspecialchars($Mensaje); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info" role="alert">
                                        <i class="bi bi-info-circle me-1"></i> Los campos indicados con (*) son requeridos
                                    </div>
                                <?php endif; ?>
                                <form class="row g-3" method="post" action="" novalidate>
                                    <div class="col-12">
                                        <label for="usuario" class="form-label">Usuario (*)</label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text" id="inputGroupPrepend">@</span>
                                            <input type="text" name="usuario" class="form-control" id="usuario" value="<?php echo htmlspecialchars($usuario); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="clave" class="form-label">Clave (*)</label>
                                        <input type="password" name="clave" class="form-control" id="clave" required>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100" type="submit" name="BotonLogin" value="Login">Login</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="credits">
                            Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require_once 'includes/footer.php'; ?>
