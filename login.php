<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('index.php');
}

$pageTitle = 'Panel de Administración - Login';
$errors = [];
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = strtolower(trim($_POST['usuario'] ?? ''));
    $clave = trim($_POST['clave'] ?? '');

    if (!campo_requerido($usuario)) {
        $errors[] = 'El usuario es obligatorio.';
    }

    if (!campo_requerido($clave)) {
        $errors[] = 'La clave es obligatoria.';
    } elseif (!preg_match('/^[A-Za-z0-9]{5,}$/', $clave)) {
        $errors[] = 'La clave debe tener al menos 5 caracteres y solo puede contener letras o números.';
    }

    if (!$errors) {
        $user = authenticate_user($usuario, $clave);
        if ($user) {
            login_user($user);
            redirect('index.php');
        } else {
            $errors[] = 'Los datos son incorrectos. Intenta nuevamente.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
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
                                <div class="alert alert-info" role="alert">
                                    <i class="bi bi-info-circle me-1"></i> Los campos indicados con (*) son requeridos
                                    <?php if ($errors): ?>
                                        <ul class="mb-0 mt-2 text-danger">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <form class="row g-3" method="post" action="">
                                    <div class="col-12">
                                        <label for="usuario" class="form-label">Usuario (*)</label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text" id="inputGroupPrepend">@</span>
                                            <input type="text" name="usuario" class="form-control" id="usuario" value="<?php echo htmlspecialchars($usuario ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="clave" class="form-label">Clave (*)</label>
                                        <input type="password" name="clave" class="form-control" id="clave" required>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100" type="submit">Login</button>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
