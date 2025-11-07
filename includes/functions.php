<?php
require_once __DIR__ . '/config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function db_connect()
{
    static $connection;

    if (!empty($connection)) {
        return $connection;
    }

    $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($connection === false) {
        die('No se pudo establecer la conexión.');
    }

    mysqli_set_charset($connection, 'utf8');

    return $connection;
}

function db_query($sql)
{
    $connection = db_connect();
    $result = mysqli_query($connection, $sql);

    if ($result === false) {
        die('<h4>Consulta: ' . $sql . '</h4><p style="color: #ff0000">' . mysqli_error($connection) . '</p>');
    }

    return $result;
}

function db_fetch_all($sql)
{
    $result = db_query($sql);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_free_result($result);

    return $rows;
}

function db_fetch_one($sql)
{
    $result = db_query($sql);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    if (empty($row)) {
        return null;
    }

    return $row;
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function authenticate_user($username, $password)
{
    $connection = db_connect();
    $usuario = mysqli_real_escape_string($connection, $username);

    $sql = "SELECT id, apellido, nombre, usuario, clave, id_nivel, imagen FROM usuarios WHERE usuario = '" . $usuario . "' AND activo = 1";
    $user = db_fetch_one($sql);

    if (!$user) {
        return null;
    }

    if ($password !== $user['clave']) {
        return null;
    }

    return $user;
}

function login_user($user)
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'apellido' => $user['apellido'],
        'nombre' => $user['nombre'],
        'usuario' => $user['usuario'],
        'id_nivel' => (int) $user['id_nivel'],
        'imagen' => !empty($user['imagen']) ? $user['imagen'] : null,
    ];
}

function logout_user()
{
    $_SESSION = [];
    session_destroy();
}

function current_user()
{
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    return null;
}

function require_login()
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function user_full_name($user)
{
    $apellido = !empty($user['apellido']) ? $user['apellido'] : '';
    $nombre = !empty($user['nombre']) ? $user['nombre'] : '';

    return trim($apellido . ', ' . $nombre);
}

function es_admin()
{
    $user = current_user();
    return !empty($user['id_nivel']) && (int) $user['id_nivel'] === 1;
}

function es_operador()
{
    $user = current_user();
    return !empty($user['id_nivel']) && (int) $user['id_nivel'] === 2;
}

function es_chofer()
{
    $user = current_user();
    return !empty($user['id_nivel']) && (int) $user['id_nivel'] === 3;
}

function nivel_denominacion($id)
{
    switch ((int) $id) {
        case 1:
            return 'Administrador';
        case 2:
            return 'Operador';
        case 3:
            return 'Chofer';
        default:
            return 'Usuario';
    }
}

function descripcion_funciones_por_nivel($id)
{
    switch ((int) $id) {
        case 1:
            return 'transportes, choferes y viajes';
        case 2:
            return 'transportes y viajes';
        case 3:
            return 'el seguimiento de los viajes asignados';
        default:
            return 'la información disponible en el panel';
    }
}

function format_date_spanish($date)
{
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    return date('d/m/Y', $timestamp);
}

function obtener_clase_fila($fechaViaje)
{
    $fechaViaje = date('Y-m-d', strtotime($fechaViaje));
    $hoy = date('Y-m-d');
    $maniana = date('Y-m-d', strtotime($hoy . ' +1 day'));

    if ($fechaViaje < $hoy) {
        return 'fila-realizado';
    }

    if ($fechaViaje == $hoy) {
        return 'fila-hoy';
    }

    if ($fechaViaje == $maniana) {
        return 'fila-maniana';
    }

    return '';
}

function calcular_monto_chofer($costo, $porcentaje)
{
    return round($costo * $porcentaje / 100, 2);
}

function obtener_choferes()
{
    $sql = "SELECT id, apellido, nombre, dni FROM usuarios WHERE id_nivel = 3 AND activo = 1 ORDER BY apellido ASC, nombre ASC";
    return db_fetch_all($sql);
}

function obtener_transportes()
{
    $sql = "SELECT t.id, m.denominacion AS marca, t.modelo, t.patente FROM transportes t INNER JOIN marcas m ON m.id = t.marca_id WHERE t.disponible = 1 ORDER BY m.denominacion ASC, t.modelo ASC, t.patente ASC";
    return db_fetch_all($sql);
}

function obtener_marcas()
{
    $sql = "SELECT id, denominacion FROM marcas ORDER BY denominacion ASC";
    return db_fetch_all($sql);
}

function obtener_destinos()
{
    $sql = "SELECT id, denominacion FROM destinos ORDER BY denominacion ASC";
    return db_fetch_all($sql);
}

function normalizar_importe($valor)
{
    $valor = str_replace('.', '', $valor);
    $valor = str_replace(',', '.', $valor);

    if ($valor === '') {
        return null;
    }

    if (!is_numeric($valor)) {
        return null;
    }

    return (float) $valor;
}

function convertir_fecha_formulario($fecha)
{
    $fecha = trim($fecha);
    if ($fecha === '') {
        return null;
    }

    $partes = explode('/', $fecha);
    if (count($partes) != 3) {
        return null;
    }

    $dia = (int) $partes[0];
    $mes = (int) $partes[1];
    $anio = (int) $partes[2];

    if (!checkdate($mes, $dia, $anio)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
}

function generar_usuario_por_defecto($apellido, $nombre)
{
    $base = strtolower($nombre);
    $limpio = '';

    for ($i = 0; $i < strlen($base); $i++) {
        $caracter = $base[$i];
        if (ctype_alnum($caracter)) {
            $limpio .= $caracter;
        }
    }

    if ($limpio === '') {
        $limpio = 'usuario';
    }

    return $limpio;
}

function generar_usuario_unico($base)
{
    $usuario = $base;
    $contador = 1;

    while (usuario_existe($usuario)) {
        $usuario = $base . $contador;
        $contador++;
    }

    return $usuario;
}

function guardar_chofer($datos)
{
    $connection = db_connect();

    $apellido = mysqli_real_escape_string($connection, $datos['apellido']);
    $nombre = mysqli_real_escape_string($connection, $datos['nombre']);
    $dni = mysqli_real_escape_string($connection, $datos['dni']);
    $usuario = mysqli_real_escape_string($connection, strtolower(trim($datos['usuario'])));
    $clave = mysqli_real_escape_string($connection, trim($datos['clave']));

    $sql = "INSERT INTO usuarios (apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion) VALUES ('" . $apellido . "', '" . $nombre . "', '" . $dni . "', '" . $usuario . "', '" . $clave . "', 1, 3, NOW())";
    db_query($sql);

    return [
        'id' => mysqli_insert_id($connection),
        'usuario' => $usuario,
        'clave' => $clave,
    ];
}

function guardar_transporte($datos)
{
    $connection = db_connect();

    $marca = (int) $datos['marca_id'];
    $modelo = mysqli_real_escape_string($connection, $datos['modelo']);
    $patente = mysqli_real_escape_string($connection, $datos['patente']);
    $anio = (int) $datos['anio'];
    $disponible = (int) $datos['disponible'];

    $sql = "INSERT INTO transportes (marca_id, modelo, patente, anio, disponible, fecha_creacion) VALUES (" . $marca . ", '" . $modelo . "', '" . $patente . "', " . $anio . ", " . $disponible . ", NOW())";
    db_query($sql);

    return mysqli_insert_id($connection);
}

function guardar_viaje($datos)
{
    $connection = db_connect();

    $chofer = (int) $datos['chofer_id'];
    $transporte = (int) $datos['transporte_id'];
    $fecha = mysqli_real_escape_string($connection, $datos['fecha_programada']);
    $destino = (int) $datos['destino_id'];
    $costo = (float) $datos['costo'];
    $porcentaje = (int) $datos['porcentaje_chofer'];
    $creadoPor = !empty($datos['creado_por']) ? (int) $datos['creado_por'] : 'NULL';

    $sql = "INSERT INTO viajes (chofer_id, transporte_id, fecha_programada, destino_id, costo, porcentaje_chofer, creado_por, fecha_creacion) VALUES (" . $chofer . ", " . $transporte . ", '" . $fecha . "', " . $destino . ", " . $costo . ", " . $porcentaje . ", " . $creadoPor . ", NOW())";
    db_query($sql);

    return mysqli_insert_id($connection);
}

function obtener_viajes($choferId = null)
{
    $sql = "SELECT v.id, v.fecha_programada, d.denominacion AS destino, v.costo, v.porcentaje_chofer, " .
        "c.apellido AS chofer_apellido, c.nombre AS chofer_nombre, c.dni AS chofer_dni, " .
        "m.denominacion AS marca, t.modelo, t.patente " .
        "FROM viajes v " .
        "INNER JOIN usuarios c ON c.id = v.chofer_id " .
        "INNER JOIN transportes t ON t.id = v.transporte_id " .
        "INNER JOIN marcas m ON m.id = t.marca_id " .
        "INNER JOIN destinos d ON d.id = v.destino_id";

    if (!empty($choferId)) {
        $sql .= " WHERE v.chofer_id = " . (int) $choferId;
    }

    $sql .= " ORDER BY v.fecha_programada ASC, d.denominacion ASC";

    return db_fetch_all($sql);
}

function campo_requerido($valor)
{
    return trim($valor) !== '';
}

function validar_dni($dni)
{
    $dni = trim($dni);
    return ctype_digit($dni) && strlen($dni) >= 7 && strlen($dni) <= 8;
}

function validar_porcentaje($valor)
{
    if ($valor === '') {
        return false;
    }

    if (!is_numeric($valor)) {
        return false;
    }

    $numero = (int) $valor;
    return $numero >= 0 && $numero <= 100;
}

function usuario_existe($usuario)
{
    $connection = db_connect();
    $usuario = mysqli_real_escape_string($connection, $usuario);
    $sql = "SELECT id FROM usuarios WHERE usuario = '" . $usuario . "'";
    $row = db_fetch_one($sql);

    return !empty($row);
}

function dni_existe($dni)
{
    $connection = db_connect();
    $dni = mysqli_real_escape_string($connection, $dni);
    $sql = "SELECT id FROM usuarios WHERE dni = '" . $dni . "'";
    $row = db_fetch_one($sql);

    return !empty($row);
}

function patente_existe($patente)
{
    $connection = db_connect();
    $patente = mysqli_real_escape_string($connection, $patente);
    $sql = "SELECT id FROM transportes WHERE patente = '" . $patente . "'";
    $row = db_fetch_one($sql);

    return !empty($row);
}
