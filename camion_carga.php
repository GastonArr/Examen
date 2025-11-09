<?php
require_once 'funciones/conexion.php';
require_once 'funciones/funciones.php'; // Se incluye el archivo de funciones para disponer de la conexión, utilidades y sesiones.
RequiereSesion(); // Se asegura que solo usuarios autenticados puedan acceder a la pantalla.

if (EsChofer()) { // Se controla que los choferes no puedan cargar transportes como lo indica la consigna.
    Redireccionar('index.php'); // En caso de que un chofer llegue a la URL, se lo redirige al panel principal.
}

$MiConexion = ConexionBD();

$pageTitle = 'Registrar un nuevo transporte'; // Se define el título de la página para el encabezado HTML.
$activePage = 'camion_carga'; // Se establece el identificador de la página activa para resaltar el menú lateral correspondiente.

$marcas = Listar_Marcas($MiConexion); // Se obtienen todas las marcas registradas para alimentar el selector del formulario.

$errors = array(); // Se inicializa el arreglo que almacenará los mensajes de validación.
$success = false; // Se prepara un indicador para mostrar el mensaje de guardado exitoso.
$marcaId = ''; // Se guarda la selección actual de la marca para repoblar el formulario ante errores.
$modelo = ''; // Se inicializa el campo del modelo del camión.
$anio = ''; // Se inicializa el campo del año del vehículo.
$patente = ''; // Se inicializa la patente para mantener la entrada del usuario.
$disponible = true; // Se asume que el transporte se cargará habilitado salvo que el usuario desmarque la opción.

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Se detecta el envío del formulario para procesar los datos.
    if (isset($_POST['marca_id'])) { // Se obtiene la marca seleccionada desde el formulario.
        $marcaId = $_POST['marca_id'];
    } else {
        $marcaId = '';
    }

    if (isset($_POST['modelo'])) { // Se limpia el modelo eliminando espacios al inicio y final.
        $modelo = trim($_POST['modelo']);
    } else {
        $modelo = '';
    }

    if (isset($_POST['anio'])) { // Se obtiene el año del vehículo como cadena para validarlo manualmente.
        $anio = trim($_POST['anio']);
    } else {
        $anio = '';
    }

    if (isset($_POST['patente'])) { // Se normaliza la patente: se quitan espacios y se convierte a mayúsculas.
        $patente = strtoupper(str_replace(' ', '', $_POST['patente']));
    } else {
        $patente = '';
    }
    $disponible = isset($_POST['disponible']); // Se interpreta el estado del checkbox de disponibilidad.

    $marcaValida = false; // Se prepara una bandera para validar la marca seleccionada.
    for ($i = 0; $i < count($marcas); $i++) { // Se recorren las marcas disponibles para comparar el identificador recibido.
        if ((string) $marcas[$i]['id'] === (string) $marcaId) {
            $marcaValida = true;
            break;
        }
    }
    if (!$marcaId || !$marcaValida) { // Se verifica que se haya elegido una marca existente.
        $errors[] = 'Debes seleccionar una marca válida.'; // Se agrega un mensaje de error cuando la marca es inválida.
    }

    if (!CampoRequerido($modelo) || strlen($modelo) < 2) { // Se valida que el modelo tenga contenido y una longitud mínima para evitar valores sin sentido.
        $errors[] = 'El modelo es obligatorio y debe tener al menos 2 caracteres.'; // Se informa al usuario la regla aplicada.
    }

    $anioNormalizado = 0; // Se prepara una variable numérica para almacenar el año validado.
    if ($anio !== '') { // Se ingresa a validar solamente si el usuario escribió un año.
        if (!ctype_digit($anio) || strlen($anio) !== 4) { // Se verifica que el año contenga exactamente cuatro dígitos numéricos.
            $errors[] = 'El año debe estar compuesto por 4 dígitos.'; // Se detalla el motivo del error.
        } else {
            $anioEntero = (int) $anio; // Se convierte la cadena numérica en entero para aplicar reglas adicionales.
            $anioActual = (int) date('Y'); // Se obtiene el año actual para comparar límites lógicos.
            if ($anioEntero < 1990 || $anioEntero > $anioActual + 1) { // Se valida que el año esté dentro de un rango razonable.
                $errors[] = 'El año debe estar entre 1990 y ' . ($anioActual + 1) . '.'; // Se informa el rango permitido dinámicamente.
            } else {
                $anioNormalizado = $anioEntero; // Se conserva el año validado para guardarlo en la base de datos.
            }
        }
    }

    if ($patente === '') { // Se controla que la patente tenga contenido.
        $errors[] = 'La patente es obligatoria.'; // Se indica al usuario que debe completar el dato.
    } else {
        $longitudPatente = strlen($patente);
        $patenteValida = $longitudPatente >= 6 && $longitudPatente <= 7 && ctype_alnum($patente);
        if (!$patenteValida) { // Se controla que la patente cumpla con el formato alfanumérico de 6 o 7 caracteres.
            $errors[] = 'La patente debe tener entre 6 y 7 caracteres alfanuméricos.'; // Se comunica el problema al usuario.
        }
    }

    if (!$errors && ExistePatente($patente, $MiConexion)) { // Se verifica que la patente no esté duplicada en la base de datos.
        $errors[] = 'La patente ingresada ya se encuentra registrada.'; // Se detiene el proceso avisando que el dato ya existe.
    }

    $disponibleValor = $disponible ? 1 : 0; // Se transforma el valor booleano del checkbox en entero para almacenarlo.

    if (!$errors) { // Se procede a guardar el transporte únicamente cuando no se detectaron errores.
        Insertar_Transporte(array( // Se llama a la función que inserta el transporte en la base de datos.
            'marca_id' => (int) $marcaId, // Se envía la marca seleccionada casteada a entero.
            'modelo' => $modelo, // Se envía el modelo ya validado.
            'patente' => $patente, // Se envía la patente normalizada.
            'anio' => $anioNormalizado, // Se envía el año validado o cero si no se informó.
            'disponible' => $disponibleValor, // Se envía el estado de disponibilidad del transporte.
        ), $MiConexion);
        $success = true; // Se activa la bandera para mostrar el mensaje de éxito.
        $marcaId = $modelo = $anio = $patente = ''; // Se limpian los campos para evitar que queden valores previos en el formulario.
        $disponible = true; // Se restablece el checkbox como seleccionado luego de guardar.
    }
}

require_once 'includes/header.php'; // Se carga el encabezado común del panel.
require_once 'includes/topbar.php'; // Se incluye la barra superior con los datos del usuario logueado.
require_once 'includes/sidebar.php'; // Se incluye el menú lateral que respeta los permisos del usuario.
?>
<main id="main" class="main">
    <!-- Sección de título de la página -->
    <div class="pagetitle">
        <h1>Registrar un nuevo transporte</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">Transportes</li>
                <li class="breadcrumb-item active">Carga</li>
            </ol>
        </nav>
    </div>
    <!-- Contenido principal del formulario -->
    <section class="section">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ingresa los datos</h5>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-1"></i> Los campos indicados con (*) son requeridos
                        </div>
                        <?php if ($errors): // Se muestran las alertas solo cuando existen errores de validación. ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): // Se muestra el mensaje de confirmación cuando el guardado fue exitoso. ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-1"></i> ¡El transporte se registró correctamente!
                            </div>
                        <?php endif; ?>
                        <form class="row g-3" method="post" action="" novalidate>
                            <div class="col-12">
                                <label for="marca_id" class="form-label">Marca (*)</label>
                                <select class="form-select" id="marca_id" name="marca_id" required>
                                    <option value="">Selecciona una opción</option>
                                    <?php foreach ($marcas as $marca): // Se recorren las marcas para crear cada opción del selector. ?>
                                        <option value="<?php echo htmlspecialchars($marca['id']); ?>" <?php echo (string) $marca['id'] === (string) $marcaId ? 'selected' : ''; ?>><?php echo htmlspecialchars($marca['denominacion']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="modelo" class="form-label">Modelo (*)</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo htmlspecialchars($modelo); ?>" required>
                            </div>
                            <div class="col-6">
                                <label for="anio" class="form-label">Año</label>
                                <input type="text" class="form-control" id="anio" name="anio" value="<?php echo htmlspecialchars($anio); ?>" placeholder="AAAA">
                            </div>
                            <div class="col-6">
                                <label for="patente" class="form-label">Patente (*)</label>
                                <input type="text" class="form-control" id="patente" name="patente" value="<?php echo htmlspecialchars($patente); ?>" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="disponible" name="disponible" <?php echo $disponible ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="disponible">
                                        Habilitado
                                    </label>
                                </div>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-primary" type="submit">Registrar</button>
                                <a href="camion_carga.php" class="btn btn-secondary">Limpiar Campos</a>
                                <a href="index.php" class="text-primary fw-bold">Volver al index</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once 'includes/footer.php'; // Se agrega el pie común para cerrar el HTML. ?>
