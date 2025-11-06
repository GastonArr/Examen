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
    return db_fetch_all(
        'SELECT id, marca, modelo, patente FROM transportes WHERE disponible = 1 ORDER BY marca ASC, modelo ASC, patente ASC'
    );
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

function guardar_chofer(array $datos): int
{
    $stmt = db_query(
        'INSERT INTO usuarios (apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion) VALUES (?, ?, ?, ?, ?, 1, 3, NOW())',
        'sssss',
        [
            $datos['apellido'],
            $datos['nombre'],
            $datos['dni'],
            $datos['usuario'],
            password_hash($datos['clave'], PASSWORD_BCRYPT)
        ]
    );

    return db_connect()->insert_id;
}

function guardar_viaje(array $datos): int
{
    $stmt = db_query(
        'INSERT INTO viajes (chofer_id, transporte_id, fecha_programada, destino, costo, porcentaje_chofer, creado_por, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        'iissdii',
        [
            $datos['chofer_id'],
            $datos['transporte_id'],
            $datos['fecha_programada'],
            $datos['destino'],
            $datos['costo'],
            $datos['porcentaje_chofer'],
            $datos['creado_por']
        ]
    );

    return db_connect()->insert_id;
}

function obtener_viajes(): array
{
    return db_fetch_all(
        'SELECT v.id, v.fecha_programada, v.destino, v.costo, v.porcentaje_chofer, ' .
        'c.apellido AS chofer_apellido, c.nombre AS chofer_nombre, c.dni AS chofer_dni, ' .
        't.marca, t.modelo, t.patente ' .
        'FROM viajes v ' .
        'INNER JOIN usuarios c ON c.id = v.chofer_id ' .
        'INNER JOIN transportes t ON t.id = v.transporte_id ' .
        'ORDER BY v.fecha_programada ASC, v.destino ASC'
    );
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

