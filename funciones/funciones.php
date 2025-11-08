<?php

require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!date_default_timezone_set('America/Argentina/Cordoba')) {
    date_default_timezone_set('UTC');
}

function ObtenerConexionActiva($vConexion = null)
{
    if ($vConexion instanceof mysqli) {
        return $vConexion;
    }

    if ($vConexion) {
        return $vConexion;
    }

    return ConexionBD();
}

function Redireccionar($Ruta)
{
    header('Location: ' . $Ruta);
    exit;
}

function DatosLogin($vUsuario, $vClave, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $Usuario = [];

    $UsuarioSQL = mysqli_real_escape_string($Conexion, $vUsuario);
    $SQL = "SELECT u.id, u.apellido, u.nombre, u.usuario, u.clave, u.id_nivel, u.imagen, u.activo, " .
        "n.denominacion AS nivel_nombre " .
        "FROM usuarios u " .
        "INNER JOIN niveles n ON n.id = u.id_nivel " .
        "WHERE u.usuario = '" . $UsuarioSQL . "'";

    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    if ($registro = mysqli_fetch_assoc($rs)) {
        $Usuario['ID'] = (int) $registro['id'];
        $Usuario['APELLIDO'] = $registro['apellido'];
        $Usuario['NOMBRE'] = $registro['nombre'];
        $Usuario['USUARIO'] = $registro['usuario'];
        $Usuario['CLAVE'] = $registro['clave'];
        $Usuario['NIVEL'] = (int) $registro['id_nivel'];
        $Usuario['NIVEL_NOMBRE'] = $registro['nivel_nombre'];
        $Usuario['IMG'] = !empty($registro['imagen']) ? $registro['imagen'] : 'user.png';
        $Usuario['ACTIVO'] = (int) $registro['activo'];
        $Usuario['SALUDO'] = 'Hola';
    }

    mysqli_free_result($rs);

    if (empty($Usuario) || $Usuario['CLAVE'] !== $vClave || $Usuario['ACTIVO'] !== 1) {
        return [];
    }

    return $Usuario;
}

function GuardarSesionUsuario($DatosUsuario)
{
    $_SESSION['Usuario_ID'] = $DatosUsuario['ID'] ?? null;
    $_SESSION['Usuario_Nombre'] = $DatosUsuario['NOMBRE'] ?? null;
    $_SESSION['Usuario_Apellido'] = $DatosUsuario['APELLIDO'] ?? null;
    $_SESSION['Usuario_Usuario'] = $DatosUsuario['USUARIO'] ?? null;
    $_SESSION['Usuario_Nivel'] = $DatosUsuario['NIVEL'] ?? null;
    $_SESSION['Usuario_NombreNivel'] = $DatosUsuario['NIVEL_NOMBRE'] ?? null;
    $_SESSION['Usuario_Img'] = $DatosUsuario['IMG'] ?? null;
    $_SESSION['Usuario_Saludo'] = $DatosUsuario['SALUDO'] ?? null;
    $_SESSION['Usuario_Activo'] = $DatosUsuario['ACTIVO'] ?? null;

    $_SESSION['user'] = [
        'id' => $DatosUsuario['ID'] ?? null,
        'apellido' => $DatosUsuario['APELLIDO'] ?? null,
        'nombre' => $DatosUsuario['NOMBRE'] ?? null,
        'usuario' => $DatosUsuario['USUARIO'] ?? null,
        'id_nivel' => $DatosUsuario['NIVEL'] ?? null,
        'imagen' => $DatosUsuario['IMG'] ?? null,
    ];
}

function CerrarSesionUsuario()
{
    $_SESSION = [];

    if (session_status() !== PHP_SESSION_NONE) {
        session_destroy();
    }
}

function ObtenerUsuarioEnSesion()
{
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    if (!empty($_SESSION['Usuario_ID'])) {
        return [
            'id' => (int) $_SESSION['Usuario_ID'],
            'apellido' => $_SESSION['Usuario_Apellido'] ?? '',
            'nombre' => $_SESSION['Usuario_Nombre'] ?? '',
            'usuario' => $_SESSION['Usuario_Usuario'] ?? '',
            'id_nivel' => isset($_SESSION['Usuario_Nivel']) ? (int) $_SESSION['Usuario_Nivel'] : null,
            'imagen' => $_SESSION['Usuario_Img'] ?? null,
        ];
    }

    return null;
}

function RequiereSesion()
{
    if (!ObtenerUsuarioEnSesion()) {
        Redireccionar('login.php');
    }
}

function UsuarioEstaLogueado()
{
    return ObtenerUsuarioEnSesion() !== null;
}

function NombreCompletoUsuario($Usuario)
{
    $Apellido = !empty($Usuario['apellido']) ? $Usuario['apellido'] : '';
    $Nombre = !empty($Usuario['nombre']) ? $Usuario['nombre'] : '';
    $NombreCompleto = trim($Apellido . ', ' . $Nombre);

    return $NombreCompleto === ',' ? '' : $NombreCompleto;
}

function DenominacionNivel($IdNivel)
{
    switch ((int) $IdNivel) {
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

function DescripcionFuncionesNivel($IdNivel)
{
    switch ((int) $IdNivel) {
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

function EsAdministrador()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && (int) $Usuario['id_nivel'] === 1;
}

function EsOperador()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && (int) $Usuario['id_nivel'] === 2;
}

function EsChofer()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && (int) $Usuario['id_nivel'] === 3;
}

function Usuario_DenominacionNivel()
{
    return DenominacionNivel($_SESSION['Usuario_Nivel'] ?? null);
}

function Usuario_FuncionesPermitidas()
{
    return DescripcionFuncionesNivel($_SESSION['Usuario_Nivel'] ?? null);
}

function Listar_Choferes($vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $Listado = [];

    $SQL = "SELECT id, apellido, nombre, dni FROM usuarios WHERE id_nivel = 3 AND activo = 1 ORDER BY apellido ASC, nombre ASC";
    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    while ($data = mysqli_fetch_assoc($rs)) {
        $Fila = [];
        $Fila['id'] = (int) $data['id'];
        $Fila['apellido'] = $data['apellido'];
        $Fila['nombre'] = $data['nombre'];
        $Fila['dni'] = $data['dni'];
        $Listado[] = $Fila;
    }

    mysqli_free_result($rs);

    return $Listado;
}

function Listar_Transportes($vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $Listado = [];

    $SQL = "SELECT t.id, m.denominacion AS marca, t.modelo, t.patente FROM transportes t " .
        "INNER JOIN marcas m ON m.id = t.marca_id " .
        "WHERE t.disponible = 1 ORDER BY m.denominacion ASC, t.modelo ASC, t.patente ASC";
    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    while ($data = mysqli_fetch_assoc($rs)) {
        $Fila = [];
        $Fila['id'] = (int) $data['id'];
        $Fila['marca'] = $data['marca'];
        $Fila['modelo'] = $data['modelo'];
        $Fila['patente'] = $data['patente'];
        $Listado[] = $Fila;
    }

    mysqli_free_result($rs);

    return $Listado;
}

function Listar_Marcas($vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $Listado = [];

    $SQL = "SELECT id, denominacion FROM marcas ORDER BY denominacion ASC";
    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    while ($data = mysqli_fetch_assoc($rs)) {
        $Fila = [];
        $Fila['id'] = (int) $data['id'];
        $Fila['denominacion'] = $data['denominacion'];
        $Listado[] = $Fila;
    }

    mysqli_free_result($rs);

    return $Listado;
}

function Listar_Destinos($vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $Listado = [];

    $SQL = "SELECT id, denominacion FROM destinos ORDER BY denominacion ASC";
    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    while ($data = mysqli_fetch_assoc($rs)) {
        $Fila = [];
        $Fila['id'] = (int) $data['id'];
        $Fila['denominacion'] = $data['denominacion'];
        $Listado[] = $Fila;
    }

    mysqli_free_result($rs);

    return $Listado;
}

function NormalizarImporte($Valor)
{
    $Valor = str_replace('.', '', $Valor);
    $Valor = str_replace(',', '.', $Valor);
    $Valor = trim($Valor);

    if ($Valor === '') {
        return null;
    }

    if (!is_numeric($Valor)) {
        return null;
    }

    return (float) $Valor;
}

function ConvertirFechaFormulario($Fecha)
{
    $Fecha = trim($Fecha);
    if ($Fecha === '') {
        return null;
    }

    $Partes = explode('/', $Fecha);
    if (count($Partes) !== 3) {
        return null;
    }

    $Dia = (int) $Partes[0];
    $Mes = (int) $Partes[1];
    $Anio = (int) $Partes[2];

    if (!checkdate($Mes, $Dia, $Anio)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $Anio, $Mes, $Dia);
}

function Insertar_Chofer($Datos, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);

    $Apellido = mysqli_real_escape_string($Conexion, trim($Datos['apellido'] ?? ''));
    $Nombre = mysqli_real_escape_string($Conexion, trim($Datos['nombre'] ?? ''));
    $Dni = mysqli_real_escape_string($Conexion, trim($Datos['dni'] ?? ''));
    $Usuario = mysqli_real_escape_string($Conexion, strtolower(trim($Datos['usuario'] ?? '')));
    $Clave = mysqli_real_escape_string($Conexion, trim($Datos['clave'] ?? ''));

    $SQL = "INSERT INTO usuarios (apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion) VALUES (" .
        "'" . $Apellido . "', '" . $Nombre . "', '" . $Dni . "', '" . $Usuario . "', '" . $Clave . "', 1, 3, NOW())";

    if (!mysqli_query($Conexion, $SQL)) {
        die('No se pudo ejecutar la inserción.');
    }

    return [
        'id' => mysqli_insert_id($Conexion),
        'usuario' => $Usuario,
        'clave' => $Clave,
    ];
}

function Insertar_Transporte($Datos, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);

    $Marca = isset($Datos['marca_id']) ? (int) $Datos['marca_id'] : 0;
    $Modelo = mysqli_real_escape_string($Conexion, trim($Datos['modelo'] ?? ''));
    $Patente = mysqli_real_escape_string($Conexion, trim($Datos['patente'] ?? ''));
    $Anio = isset($Datos['anio']) ? (int) $Datos['anio'] : 0;
    $Disponible = isset($Datos['disponible']) ? (int) $Datos['disponible'] : 0;


    $SQL = "INSERT INTO transportes (marca_id, modelo, patente, anio, disponible, fecha_creacion) VALUES (" .
        $Marca . ", '" . $Modelo . "', '" . $Patente . "', " . $Anio . ", " . $Disponible . ", NOW())";

    if (!mysqli_query($Conexion, $SQL)) {
        die('No se pudo ejecutar la inserción.');
    }

    return mysqli_insert_id($Conexion);
}

function Insertar_Viaje($Datos, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);

    $Chofer = isset($Datos['chofer_id']) ? (int) $Datos['chofer_id'] : 0;
    $Transporte = isset($Datos['transporte_id']) ? (int) $Datos['transporte_id'] : 0;
    $Fecha = mysqli_real_escape_string($Conexion, trim($Datos['fecha_programada'] ?? ''));
    $Destino = isset($Datos['destino_id']) ? (int) $Datos['destino_id'] : 0;
    $Costo = isset($Datos['costo']) ? (float) $Datos['costo'] : 0;
    $Porcentaje = isset($Datos['porcentaje_chofer']) ? (int) $Datos['porcentaje_chofer'] : 0;
    $CreadoPor = !empty($Datos['creado_por']) ? (int) $Datos['creado_por'] : 'NULL';

    $SQL = "INSERT INTO viajes (chofer_id, transporte_id, fecha_programada, destino_id, costo, porcentaje_chofer, creado_por, fecha_creacion) " .
        "VALUES (" . $Chofer . ", " . $Transporte . ", '" . $Fecha . "', " . $Destino . ", " . $Costo . ", " . $Porcentaje . ", " . $CreadoPor . ", NOW())";

    if (!mysqli_query($Conexion, $SQL)) {
        die('No se pudo ejecutar la inserción.');
    }

    return mysqli_insert_id($Conexion);
}

function Listar_Viajes($ChoferId = null, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $Listado = [];

    $SQL = "SELECT v.id, v.fecha_programada, d.denominacion AS destino, v.costo, v.porcentaje_chofer, " .
        "c.apellido AS chofer_apellido, c.nombre AS chofer_nombre, c.dni AS chofer_dni, " .
        "m.denominacion AS marca, t.modelo, t.patente " .
        "FROM viajes v " .
        "INNER JOIN usuarios c ON c.id = v.chofer_id " .
        "INNER JOIN transportes t ON t.id = v.transporte_id " .
        "INNER JOIN marcas m ON m.id = t.marca_id " .
        "INNER JOIN destinos d ON d.id = v.destino_id";

    if (!empty($ChoferId)) {
        $SQL .= " WHERE v.chofer_id = " . (int) $ChoferId;
    }

    $SQL .= " ORDER BY v.fecha_programada ASC, d.denominacion ASC";

    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    while ($data = mysqli_fetch_assoc($rs)) {
        $Fila = [];
        $Fila['id'] = (int) $data['id'];
        $Fila['fecha_programada'] = $data['fecha_programada'];
        $Fila['destino'] = $data['destino'];
        $Fila['costo'] = (float) $data['costo'];
        $Fila['porcentaje_chofer'] = (int) $data['porcentaje_chofer'];
        $Fila['chofer_apellido'] = $data['chofer_apellido'];
        $Fila['chofer_nombre'] = $data['chofer_nombre'];
        $Fila['chofer_dni'] = $data['chofer_dni'];
        $Fila['marca'] = $data['marca'];
        $Fila['modelo'] = $data['modelo'];
        $Fila['patente'] = $data['patente'];
        $Listado[] = $Fila;
    }

    mysqli_free_result($rs);

    return $Listado;
}

function CampoRequerido($Valor)
{
    return trim($Valor) !== '';
}

function ValidarDNI($Dni)
{
    $Dni = trim($Dni);
    return ctype_digit($Dni) && strlen($Dni) >= 7 && strlen($Dni) <= 8;
}

function ValidarPorcentaje($Valor)
{
    $Valor = trim($Valor);

    if ($Valor === '') {
        return false;
    }

    if (!is_numeric($Valor)) {
        return false;
    }

    $Numero = (int) $Valor;
    return $Numero >= 0 && $Numero <= 100;
}

function ExisteUsuario($Usuario, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $UsuarioSQL = mysqli_real_escape_string($Conexion, $Usuario);
    $SQL = "SELECT id FROM usuarios WHERE usuario = '" . $UsuarioSQL . "'";

    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    $Existe = mysqli_fetch_assoc($rs) ? true : false;
    mysqli_free_result($rs);

    return $Existe;
}

function ExisteDNI($Dni, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $DniSQL = mysqli_real_escape_string($Conexion, $Dni);
    $SQL = "SELECT id FROM usuarios WHERE dni = '" . $DniSQL . "'";

    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    $Existe = mysqli_fetch_assoc($rs) ? true : false;
    mysqli_free_result($rs);

    return $Existe;
}

function ExistePatente($Patente, $vConexion = null)
{
    $Conexion = ObtenerConexionActiva($vConexion);
    $PatenteSQL = mysqli_real_escape_string($Conexion, $Patente);
    $SQL = "SELECT id FROM transportes WHERE patente = '" . $PatenteSQL . "'";

    $rs = mysqli_query($Conexion, $SQL);
    if ($rs === false) {
        die('No se pudo ejecutar la consulta.');
    }

    $Existe = mysqli_fetch_assoc($rs) ? true : false;
    mysqli_free_result($rs);

    return $Existe;
}

function FormatearFechaEspaniol($Fecha)
{
    if (empty($Fecha)) {
        return '';
    }

    $Timestamp = strtotime($Fecha);
    if ($Timestamp === false) {
        return '';
    }

    return date('d/m/Y', $Timestamp);
}

function ObtenerClaseFila($FechaViaje)
{
    $Timestamp = strtotime($FechaViaje);
    if ($Timestamp === false) {
        return '';
    }

    $FechaNormalizada = date('Y-m-d', $Timestamp);
    $Hoy = date('Y-m-d');
    $Maniana = date('Y-m-d', strtotime($Hoy . ' +1 day'));

    if ($FechaNormalizada < $Hoy) {
        return 'fila-realizado';
    }

    if ($FechaNormalizada === $Hoy) {
        return 'fila-hoy';
    }

    if ($FechaNormalizada === $Maniana) {
        return 'fila-maniana';
    }

    return '';
}

function CalcularMontoChofer($Costo, $Porcentaje)
{
    return round($Costo * $Porcentaje / 100, 2);
}
