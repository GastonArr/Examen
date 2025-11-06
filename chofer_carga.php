<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

if (!es_admin()) { // Se verifica que solamente los administradores puedan cargar nuevos choferes, respetando los niveles de acceso.
    redirect('index.php'); // Si el usuario no es administrador se lo redirige al panel principal para impedir el acceso.
}

$pageTitle = 'Registrar un nuevo chofer';
$activePage = 'choferes';

$errors = [];
$success = false;
$successData = null; // Se inicializa la variable que almacenará los datos del registro exitoso para mostrarlos en pantalla.
$apellido = '';
$nombre = '';
$dni = '';
$usuarioForm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apellido = trim($_POST['apellido'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $usuarioForm = trim($_POST['usuario'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    if (!campo_requerido($apellido)) {
        $errors[] = 'El apellido es obligatorio.';
    }

    if (!campo_requerido($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (!campo_requerido($dni)) {
        $errors[] = 'El DNI es obligatorio.';
    } elseif (!validar_dni($dni)) {
        $errors[] = 'El DNI debe tener 7 u 8 dígitos numéricos.';
    } elseif (dni_existe($dni)) {
        $errors[] = 'El DNI ingresado ya se encuentra registrado.';
    }

    if ($usuarioForm !== '') { // Se valida el usuario solo si fue proporcionado, porque puede omitirse según la consigna.
        $usuarioValidado = strtolower($usuarioForm); // Se convierte a minúsculas para comparar y guardar siguiendo el formato del login.
        if (!preg_match('/^[a-z0-9._-]{3,}$/', $usuarioValidado)) { // Se controla que el formato contenga únicamente caracteres válidos y un largo mínimo.
            $errors[] = 'El usuario debe tener al menos 3 caracteres y solo puede incluir letras, números, puntos, guiones o guiones bajos.'; // Se agrega un mensaje descriptivo cuando el formato no es correcto.
        } elseif (usuario_existe($usuarioValidado)) { // Se consulta la base de datos para evitar duplicados en el nombre de usuario.
            $errors[] = 'El usuario ingresado ya existe.'; // Se notifica que el usuario elegido no está disponible.
        } else {
            $usuarioForm = $usuarioValidado; // Se conserva el usuario normalizado para reutilizarlo al guardar.
        }
    }

    if ($clave !== '' && $clave !== '12345') { // Se valida que la clave ingresada cumpla con el valor solicitado para facilitar las pruebas del login.
        $errors[] = 'La clave permitida para los choferes debe ser 12345.'; // Se informa al usuario administrador cuál es la clave válida.
    }

    if (!$errors) {
        $resultado = guardar_chofer([ // Se llama a la función que inserta el chofer y devuelve información complementaria.
            'apellido' => $apellido, // Se envía el apellido ya validado.
            'nombre' => $nombre, // Se envía el nombre proporcionado.
            'dni' => $dni, // Se envía el DNI confirmado como único.
            'usuario' => $usuarioForm, // Se envía el usuario (que puede estar vacío para generar uno automático).
            'clave' => $clave, // Se envía la clave opcional (o vacía) para que la función la normalice.
        ]);
        $success = true; // Se marca el registro como exitoso para mostrar el mensaje correspondiente.
        $successData = $resultado; // Se almacenan los datos retornados (usuario y clave final) para comunicarlos al administrador.
        $apellido = $nombre = $dni = $usuarioForm = '';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
require_once __DIR__ . '/includes/sidebar.php';
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
                        <?php if ($errors): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-1"></i> ¡Los datos se guardaron correctamente! <?php if ($successData): ?>Usuario generado: <strong><?php echo htmlspecialchars($successData['usuario']); ?></strong> - Clave: <strong><?php echo htmlspecialchars($successData['clave']); ?></strong><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <form class="row g-3" method="post" action="">
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
                                <label for="usuario" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuarioForm); ?>">
                                <div class="form-text">Si lo dejas vacío el sistema generará el usuario automáticamente.</div>
                            </div>
                            <div class="col-12">
                                <label for="clave" class="form-label">Clave</label>
                                <input type="password" class="form-control" id="clave" name="clave">
                                <div class="form-text">Si se deja vacío se asignará la clave 12345 requerida para las pruebas.</div>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
