<?php
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Obtiene una conexión a la base de datos utilizando mysqli.
 * Lanza una excepción si la conexión falla para detener el script principal.
 */
function db_connect(): mysqli
{
    static $connection;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($connection->connect_errno) {
        throw new RuntimeException('Error de conexión: ' . $connection->connect_error);
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}

/**
 * Ejecuta una consulta preparada y retorna el resultado.
 */
function db_query(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $connection = db_connect();
    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException('Error al preparar la consulta: ' . $connection->error);
    }

    if ($types !== '' && $params !== []) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new RuntimeException('Error al ejecutar la consulta: ' . $stmt->error);
    }

    return $stmt;
}

function db_fetch_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_query($sql, $types, $params);
    $result = $stmt->get_result();

    if ($result === false) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function db_fetch_one(string $sql, string $types = '', array $params = []): ?array
{
    $stmt = db_query($sql, $types, $params);
    $result = $stmt->get_result();

    if ($result === false) {
        return null;
    }

    $row = $result->fetch_assoc();

    return $row ?: null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function authenticate_user(string $username, string $password): ?array
{
    $user = db_fetch_one(
        'SELECT id, apellido, nombre, usuario, clave, id_nivel, imagen FROM usuarios WHERE usuario = ? AND activo = 1',
        's',
        [$username]
    );

    if (!$user) {
        return null;
    }

    if (!password_verify($password, $user['clave'])) {
        return null;
    }

    return $user;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'apellido' => $user['apellido'],
        'nombre' => $user['nombre'],
        'usuario' => $user['usuario'],
        'id_nivel' => (int) $user['id_nivel'],
        'imagen' => $user['imagen'] ?? null,
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function user_full_name(array $user): string
{
    return trim(($user['apellido'] ?? '') . ', ' . ($user['nombre'] ?? ''));
}

function es_admin(): bool
{
    return (current_user()['id_nivel'] ?? null) === 1;
}

function es_operador(): bool
{
    return (current_user()['id_nivel'] ?? null) === 2;
}

function es_chofer(): bool
{
    return (current_user()['id_nivel'] ?? null) === 3;
}

function nivel_denominacion(?int $id): string
{
    return match ($id) {
        1 => 'Administrador',
        2 => 'Operador',
        3 => 'Chofer',
        default => 'Usuario',
    };
}

function format_date_spanish(?string $date): string
{
    if (!$date) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    return date('d/m/Y', $timestamp);
}

function obtener_clase_fila(string $fechaViaje): string
{
    $fechaViaje = date('Y-m-d', strtotime($fechaViaje));
    $hoy = date('Y-m-d');
    $maniana = date('Y-m-d', strtotime($hoy . ' +1 day'));

    if ($fechaViaje < $hoy) {
        return 'fila-realizado';
    }

    if ($fechaViaje === $hoy) {
        return 'fila-hoy';
    }

    if ($fechaViaje === $maniana) {
        return 'fila-maniana';
    }

    return '';
}

function calcular_monto_chofer(float $costo, int $porcentaje): float
{
    return round($costo * $porcentaje / 100, 2);
}

function obtener_choferes(): array
{
    return db_fetch_all(
        'SELECT id, apellido, nombre, dni FROM usuarios WHERE id_nivel = 3 AND activo = 1 ORDER BY apellido ASC, nombre ASC'
    );
}

function obtener_transportes(): array
{
    return db_fetch_all( // Se ejecuta una consulta preparada que recupera los transportes disponibles junto a su marca descriptiva.
        'SELECT t.id, m.denominacion AS marca, t.modelo, t.patente FROM transportes t INNER JOIN marcas m ON m.id = t.marca_id WHERE t.disponible = 1 ORDER BY m.denominacion ASC, t.modelo ASC, t.patente ASC' // La sentencia SQL une la tabla de transportes con la de marcas y ordena alfabéticamente la información solicitada.
    ); // Se retorna el arreglo asociativo con los datos obtenidos.
}

function obtener_marcas(): array
{
    return db_fetch_all( // Se consulta la base para traer todas las marcas ordenadas alfabéticamente.
        'SELECT id, denominacion FROM marcas ORDER BY denominacion ASC' // Se selecciona el identificador y la denominación porque son los campos necesarios para el selector.
    ); // Se devuelve el listado como arreglo asociativo.
}

function obtener_destinos(): array
{
    return db_fetch_all( // Se consulta la tabla de destinos para alimentar el selector del formulario de viajes.
        'SELECT id, denominacion FROM destinos ORDER BY denominacion ASC' // Se ordena alfabéticamente para que sea más fácil de leer al usuario.
    ); // Se retornan los destinos disponibles.
}

function normalizar_importe(string $valor): ?float
{
    $valor = str_replace(['.', ','], ['', '.'], $valor);
    $valor = preg_replace('/[^0-9.]/', '', $valor ?? '');
    return $valor === '' ? null : (float) $valor;
}

function convertir_fecha_formulario(string $fecha): ?string
{
    $fecha = trim($fecha);
    if ($fecha === '') {
        return null;
    }

    $partes = explode('/', $fecha);
    if (count($partes) !== 3) {
        return null;
    }

    [$dia, $mes, $anio] = $partes;
    if (!checkdate((int) $mes, (int) $dia, (int) $anio)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
}

function generar_usuario_por_defecto(string $apellido, string $nombre): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)); // Se genera una base en minúsculas quitando caracteres no alfanuméricos del nombre del chofer para respetar el formato solicitado.
    if ($base === '') { // Se verifica si la base quedó vacía para evitar crear un usuario sin caracteres.
        $base = 'usuario'; // Se define una cadena genérica en caso de que el nombre no provea caracteres válidos.
    }

    return $base; // Se devuelve la base que luego se ajustará para garantizar unicidad.
}

function generar_usuario_unico(string $base): string
{
    $usuario = $base; // Se comienza utilizando la base recibida como primer intento de usuario.
    $contador = 1; // Se prepara un contador para anexar un número en caso de encontrar duplicados.

    while (usuario_existe($usuario)) { // Se repite mientras el usuario generado ya exista en la base de datos.
        $usuario = $base . $contador; // Se concatena el contador para crear una nueva variante de usuario.
        $contador++; // Se incrementa el contador para la siguiente iteración si aún hubiese duplicados.
    }

    return $usuario; // Se devuelve el usuario final garantizando que sea único.
}

function guardar_chofer(array $datos): array
{
    $usuarioIngresado = trim($datos['usuario'] ?? ''); // Se almacena el usuario ingresado para analizar si se debe generar uno nuevo automáticamente.
    $usuarioBase = $usuarioIngresado !== '' ? strtolower($usuarioIngresado) : generar_usuario_por_defecto($datos['apellido'], $datos['nombre']); // Se determina la base del usuario: se usa el valor provisto o se genera desde el nombre y apellido.
    $usuarioNormalizado = preg_replace('/[^a-z0-9._-]/', '', $usuarioBase); // Se limpian caracteres no permitidos para cumplir con el formato requerido por el login.
    if ($usuarioNormalizado === '') { // Se valida que tras la limpieza exista contenido en el usuario.
        $usuarioNormalizado = 'usuario'; // Se establece una palabra base en caso de que el usuario haya quedado vacío.
    }
    $usuarioFinal = generar_usuario_unico($usuarioNormalizado); // Se obtiene un usuario único verificando que no exista previamente en la tabla.

    $claveIngresada = trim($datos['clave'] ?? ''); // Se guarda la clave recibida para saber si se debe usar la ingresada o la predefinida.
    $claveEnTextoPlano = $claveIngresada !== '' ? $claveIngresada : '12345'; // Se define la clave en texto plano, utilizando la provista o la solicitada por la consigna.
    $claveHasheada = password_hash($claveEnTextoPlano, PASSWORD_BCRYPT); // Se cifra la clave utilizando password_hash para almacenar un valor seguro en la base de datos.

    db_query( // Se ejecuta la inserción del nuevo chofer en la tabla de usuarios.
        'INSERT INTO usuarios (apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion) VALUES (?, ?, ?, ?, ?, 1, 3, NOW())', // La consulta prepara los campos definidos para los choferes, fijando el nivel en 3 y activándolos por defecto.
        'sssss', // Se especifican los tipos de datos de los parámetros enviados a la consulta.
        [
            $datos['apellido'], // Se envía el apellido proporcionado en el formulario.
            $datos['nombre'], // Se envía el nombre del chofer.
            $datos['dni'], // Se asigna el DNI validado previamente.
            $usuarioFinal, // Se almacena el usuario definitivo calculado.
            $claveHasheada // Se almacena la clave cifrada para proteger las credenciales.
        ]
    );

    return [ // Se devuelven datos útiles del nuevo registro para mostrar mensajes informativos en pantalla.
        'id' => db_connect()->insert_id, // Se entrega el identificador generado automáticamente para el chofer.
        'usuario' => $usuarioFinal, // Se informa el usuario final que deberá utilizar para ingresar al sistema.
        'clave' => $claveEnTextoPlano // Se entrega la clave en texto plano para recordarla al usuario administrador.
    ];
}

function guardar_transporte(array $datos): int
{
    db_query( // Se ejecuta la inserción del transporte utilizando una consulta preparada para evitar inyecciones SQL.
        'INSERT INTO transportes (marca_id, modelo, patente, anio, disponible, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())', // La consulta guarda la marca seleccionada, el modelo, patente, año y estado de disponibilidad.
        'issii', // Se indican los tipos de datos correspondientes a cada parámetro enviado.
        [
            $datos['marca_id'], // Se guarda el identificador de la marca elegida.
            $datos['modelo'], // Se guarda el modelo ingresado en el formulario.
            $datos['patente'], // Se registra la patente normalizada del transporte.
            $datos['anio'], // Se almacena el año del vehículo (o cero si no se informó).
            $datos['disponible'] // Se registra si el transporte está habilitado para asignarse a viajes.
        ]
    );

    return db_connect()->insert_id; // Se retorna el identificador generado para el transporte, útil para mensajes posteriores.
}

function guardar_viaje(array $datos): int
{
    db_query( // Se prepara y ejecuta la consulta de inserción para almacenar un nuevo viaje.
        'INSERT INTO viajes (chofer_id, transporte_id, fecha_programada, destino_id, costo, porcentaje_chofer, creado_por, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())', // La consulta incorpora el destino como identificador y conserva los demás campos solicitados en la consigna.
        'iisidii', // Se definen los tipos de cada parámetro: enteros para identificadores, cadena para la fecha y decimal para el costo.
        [
            $datos['chofer_id'], // Se pasa el identificador del chofer asignado.
            $datos['transporte_id'], // Se indica el transporte seleccionado para el viaje.
            $datos['fecha_programada'], // Se almacena la fecha en formato AAAA-MM-DD preparada previamente.
            $datos['destino_id'], // Se guarda el destino elegido utilizando su clave primaria.
            $datos['costo'], // Se registra el costo numérico del viaje.
            $datos['porcentaje_chofer'], // Se almacena el porcentaje que cobrará el chofer.
            $datos['creado_por'] // Se informa qué usuario registró la operación.
        ]
    );

    return db_connect()->insert_id; // Se devuelve el identificador del viaje para utilizarlo si se requiere.
}

function obtener_viajes(?int $choferId = null): array
{
    $sql = 'SELECT v.id, v.fecha_programada, d.denominacion AS destino, v.costo, v.porcentaje_chofer, ' . // Se arma la sentencia SQL seleccionando la fecha, destino y datos económicos del viaje.
        'c.apellido AS chofer_apellido, c.nombre AS chofer_nombre, c.dni AS chofer_dni, ' . // Se agregan los datos personales del chofer para mostrarlos en el listado.
        'm.denominacion AS marca, t.modelo, t.patente ' . // Se incorporan los datos del camión combinando marca, modelo y patente.
        'FROM viajes v ' . // Se establece la tabla principal de la consulta.
        'INNER JOIN usuarios c ON c.id = v.chofer_id ' . // Se une la tabla de usuarios para obtener los datos del chofer.
        'INNER JOIN transportes t ON t.id = v.transporte_id ' . // Se une la tabla de transportes para acceder al modelo y la patente.
        'INNER JOIN marcas m ON m.id = t.marca_id ' . // Se vincula la tabla de marcas para recuperar la denominación correspondiente.
        'INNER JOIN destinos d ON d.id = v.destino_id'; // Se relaciona la tabla de destinos para mostrar su nombre.

    $tipos = ''; // Se inicializa la cadena de tipos de parámetros para la consulta preparada.
    $parametros = []; // Se inicializa el arreglo que contendrá los valores a filtrar.

    if ($choferId !== null) { // Se verifica si se solicitó limitar los viajes al chofer logueado.
        $sql .= ' WHERE v.chofer_id = ?'; // Se agrega la cláusula WHERE para filtrar por el chofer recibido.
        $tipos .= 'i'; // Se añade el tipo de dato entero para el parámetro.
        $parametros[] = $choferId; // Se incorpora el identificador del chofer al arreglo de parámetros.
    }

    $sql .= ' ORDER BY v.fecha_programada ASC, d.denominacion ASC'; // Se completa la consulta ordenando por fecha y luego por destino como exige la consigna.

    return db_fetch_all($sql, $tipos, $parametros); // Se ejecuta la consulta armada dinámicamente y se devuelven los resultados obtenidos.
}

function campo_requerido(string $valor): bool
{
    return trim($valor) !== '';
}

function validar_dni(string $dni): bool
{
    return preg_match('/^\d{7,8}$/', $dni) === 1;
}

function validar_porcentaje(string $valor): bool
{
    if (!preg_match('/^\d{1,3}$/', trim($valor))) {
        return false;
    }

    $numero = (int) $valor;
    return $numero >= 0 && $numero <= 100;
}

function usuario_existe(string $usuario): bool
{
    $row = db_fetch_one('SELECT id FROM usuarios WHERE usuario = ?', 's', [$usuario]);
    return $row !== null;
}

function dni_existe(string $dni): bool
{
    $row = db_fetch_one('SELECT id FROM usuarios WHERE dni = ?', 's', [$dni]);
    return $row !== null;
}

function patente_existe(string $patente): bool
{
    $row = db_fetch_one('SELECT id FROM transportes WHERE patente = ?', 's', [$patente]); // Se consulta la tabla de transportes para saber si la patente ya fue registrada.
    return $row !== null; // Se devuelve verdadero si existe al menos un registro con esa patente.
}

