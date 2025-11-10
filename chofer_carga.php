<?php
require_once 'funciones/conexion.php';
require_once 'funciones/funciones.php';
RequiereSesion();

if (!EsAdministrador()) { // Se verifica que solamente los administradores puedan cargar nuevos choferes, respetando los niveles de acceso.
    Redireccionar('index.php'); // Si el usuario no es administrador se lo redirige al panel principal para impedir el acceso.
}

function ValidarDatosChofer()
{
    $Mensaje = '';

    if (strlen($_POST['apellido']) < 3) {
        $Mensaje .= 'Debes ingresar un apellido con al menos 3 caracteres. <br />';
    }

    if (strlen($_POST['nombre']) < 3) {
        $Mensaje .= 'Debes ingresar un nombre con al menos 3 caracteres. <br />';
    }

    if (strlen($_POST['dni']) < 7) {
        $Mensaje .= 'Debes ingresar un DNI con al menos 7 caracteres. <br />';
    }

    if (strlen($_POST['usuario']) < 4) {
        $Mensaje .= 'Debes ingresar un usuario con al menos 4 caracteres. <br />';
    }

    if (strlen($_POST['clave']) == 0) {
        $Mensaje .= 'Debes ingresar la clave. <br />';
    }

    foreach ($_POST as $Id => $Valor) {
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $Mensaje;
}

$MiConexion = ConexionBD();

$pageTitle = 'Registrar un nuevo chofer';
$activePage = 'choferes';

$mensaje = '';
$estilo = '';
$apellido = '';
$nombre = '';
$dni = '';
$usuarioForm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = ValidarDatosChofer();

    if (!empty($mensaje)) {
        $estilo = 'warning';
        $apellido = isset($_POST['apellido']) ? $_POST['apellido'] : '';
        $nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
        $dni = isset($_POST['dni']) ? $_POST['dni'] : '';
        $usuarioForm = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    } else {
        $resultado = Insertar_Chofer(array(
            'apellido' => $_POST['apellido'],
            'nombre' => $_POST['nombre'],
            'dni' => $_POST['dni'],
            'usuario' => $_POST['usuario'],
            'clave' => $_POST['clave'],
        ), $MiConexion);

        if ($resultado != false) {
            $mensaje = 'Los datos se guardaron correctamente.';
            $estilo = 'success';
            $apellido = $nombre = $dni = $usuarioForm = '';
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/topbar.php';
require_once 'includes/sidebar.php';
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
                        <?php if (!empty($mensaje)): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($estilo); ?>" role="alert">
                                <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>
                        <form class="row g-3" method="post" action="" novalidate>
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
<?php require_once 'includes/footer.php'; ?>
