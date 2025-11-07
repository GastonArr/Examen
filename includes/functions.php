<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function ConexionBD($Host = DB_HOST, $User = DB_USER, $Password = DB_PASS, $BaseDeDatos = DB_NAME)
{
    static $ConexionActiva;

    if (!empty($ConexionActiva)) {
        return $ConexionActiva;
    }

    $linkConexion = mysqli_connect($Host, $User, $Password, $BaseDeDatos);
    if ($linkConexion === false) {
        die('No se pudo establecer la conexión.');
    }

    mysqli_set_charset($linkConexion, 'utf8');
    $ConexionActiva = $linkConexion;

    return $ConexionActiva;
}

function EjecutarConsulta($SQL, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();
    $Resultado = mysqli_query($Conexion, $SQL);

    if ($Resultado === false) {
        die('<h4>Consulta: ' . $SQL . '</h4><p style="color: #ff0000">' . mysqli_error($Conexion) . '</p>');
    }

    return $Resultado;
}

function TraerListado($SQL, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();
    $Listado = [];
    $Resultado = EjecutarConsulta($SQL, $Conexion);

    while ($Fila = mysqli_fetch_assoc($Resultado)) {
        $Listado[] = $Fila;
    }

    mysqli_free_result($Resultado);

    return $Listado;
}

function TraerRegistro($SQL, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();
    $Resultado = EjecutarConsulta($SQL, $Conexion);
    $Fila = mysqli_fetch_assoc($Resultado);
    mysqli_free_result($Resultado);

    if (empty($Fila)) {
        return null;
    }

    return $Fila;
}

function Redireccionar($Ruta)
{
    header('Location: ' . $Ruta);
    exit;
}

function DatosLogin($vUsuario, $vClave, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();
    $Usuario = [];

    $UsuarioSQL = mysqli_real_escape_string($Conexion, $vUsuario);
    $SQL = "SELECT u.id, u.apellido, u.nombre, u.usuario, u.clave, u.id_nivel, u.imagen, u.activo, " .
        "n.denominacion AS nivel_nombre " .
        "FROM usuarios u " .
        "INNER JOIN niveles n ON n.id = u.id_nivel " .
        "WHERE u.usuario = '" . $UsuarioSQL . "'";

    $Registro = TraerRegistro($SQL, $Conexion);

    if (!empty($Registro)) {
        $Usuario['ID'] = (int) $Registro['id'];
        $Usuario['APELLIDO'] = $Registro['apellido'];
        $Usuario['NOMBRE'] = $Registro['nombre'];
        $Usuario['USUARIO'] = $Registro['usuario'];
        $Usuario['CLAVE'] = $Registro['clave'];
        $Usuario['NIVEL'] = (int) $Registro['id_nivel'];
        $Usuario['NIVEL_NOMBRE'] = $Registro['nivel_nombre'];
        $Usuario['IMG'] = !empty($Registro['imagen']) ? $Registro['imagen'] : 'user.png';
        $Usuario['ACTIVO'] = (int) $Registro['activo'];

        $Usuario['SALUDO'] = 'Hola';
    }

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
    if ($NombreCompleto === ',') {
        return '';
    }

    return $NombreCompleto;
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
    $FechaNormalizada = date('Y-m-d', strtotime($FechaViaje));
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

function Listar_Choferes($vConexion = null)
{
    $SQL = "SELECT id, apellido, nombre, dni FROM usuarios WHERE id_nivel = 3 AND activo = 1 ORDER BY apellido ASC, nombre ASC";
    return TraerListado($SQL, $vConexion);
}

function Listar_Transportes($vConexion = null)
{
    $SQL = "SELECT t.id, m.denominacion AS marca, t.modelo, t.patente FROM transportes t " .
        "INNER JOIN marcas m ON m.id = t.marca_id WHERE t.disponible = 1 ORDER BY m.denominacion ASC, t.modelo ASC, t.patente ASC";
    return TraerListado($SQL, $vConexion);
}

function Listar_Marcas($vConexion = null)
{
    $SQL = "SELECT id, denominacion FROM marcas ORDER BY denominacion ASC";
    return TraerListado($SQL, $vConexion);
}

function Listar_Destinos($vConexion = null)
{
    $SQL = "SELECT id, denominacion FROM destinos ORDER BY denominacion ASC";
    return TraerListado($SQL, $vConexion);
}

function NormalizarImporte($Valor)
{
    $Valor = str_replace('.', '', $Valor);
    $Valor = str_replace(',', '.', $Valor);

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

function GenerarUsuarioPorDefecto($Apellido, $Nombre)
{
    $Base = strtolower($Nombre);
    $Limpio = '';

    for ($i = 0; $i < strlen($Base); $i++) {
        $Caracter = $Base[$i];
        if (ctype_alnum($Caracter)) {
            $Limpio .= $Caracter;
        }
    }

    if ($Limpio === '') {
        $Limpio = 'usuario';
    }

    return $Limpio;
}

function GenerarUsuarioUnico($Base, $vConexion = null)
{
    $Usuario = $Base;
    $Contador = 1;

    while (ExisteUsuario($Usuario, $vConexion)) {
        $Usuario = $Base . $Contador;
        $Contador++;
    }

    return $Usuario;
}

function Insertar_Chofer($Datos, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();

    $Apellido = mysqli_real_escape_string($Conexion, $Datos['apellido']);
    $Nombre = mysqli_real_escape_string($Conexion, $Datos['nombre']);
    $Dni = mysqli_real_escape_string($Conexion, $Datos['dni']);
    $Usuario = mysqli_real_escape_string($Conexion, strtolower(trim($Datos['usuario'])));
    $Clave = mysqli_real_escape_string($Conexion, trim($Datos['clave']));

    $SQL = "INSERT INTO usuarios (apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion) VALUES ('" . $Apellido . "', '" . $Nombre . "', '" . $Dni . "', '" . $Usuario . "', '" . $Clave . "', 1, 3, NOW())";
    EjecutarConsulta($SQL, $Conexion);

    return [
        'id' => mysqli_insert_id($Conexion),
        'usuario' => $Usuario,
        'clave' => $Clave,
    ];
}

function Insertar_Transporte($Datos, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();

    $Marca = (int) $Datos['marca_id'];
    $Modelo = mysqli_real_escape_string($Conexion, $Datos['modelo']);
    $Patente = mysqli_real_escape_string($Conexion, $Datos['patente']);
    $Anio = (int) $Datos['anio'];
    $Disponible = (int) $Datos['disponible'];

    $SQL = "INSERT INTO transportes (marca_id, modelo, patente, anio, disponible, fecha_creacion) VALUES (" . $Marca . ", '" . $Modelo . "', '" . $Patente . "', " . $Anio . ", " . $Disponible . ", NOW())";
    EjecutarConsulta($SQL, $Conexion);

    return mysqli_insert_id($Conexion);
}

function Insertar_Viaje($Datos, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();

    $Chofer = (int) $Datos['chofer_id'];
    $Transporte = (int) $Datos['transporte_id'];
    $Fecha = mysqli_real_escape_string($Conexion, $Datos['fecha_programada']);
    $Destino = (int) $Datos['destino_id'];
    $Costo = (float) $Datos['costo'];
    $Porcentaje = (int) $Datos['porcentaje_chofer'];
    $CreadoPor = !empty($Datos['creado_por']) ? (int) $Datos['creado_por'] : 'NULL';

    $SQL = "INSERT INTO viajes (chofer_id, transporte_id, fecha_programada, destino_id, costo, porcentaje_chofer, creado_por, fecha_creacion) " .
        "VALUES (" . $Chofer . ", " . $Transporte . ", '" . $Fecha . "', " . $Destino . ", " . $Costo . ", " . $Porcentaje . ", " . $CreadoPor . ", NOW())";
    EjecutarConsulta($SQL, $Conexion);

    return mysqli_insert_id($Conexion);
}

function Listar_Viajes($ChoferId = null, $vConexion = null)
{
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

    return TraerListado($SQL, $vConexion);
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
    $Conexion = $vConexion ?? ConexionBD();
    $UsuarioSQL = mysqli_real_escape_string($Conexion, $Usuario);
    $SQL = "SELECT id FROM usuarios WHERE usuario = '" . $UsuarioSQL . "'";
    $Registro = TraerRegistro($SQL, $Conexion);

    return !empty($Registro);
}

function ExisteDNI($Dni, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();
    $DniSQL = mysqli_real_escape_string($Conexion, $Dni);
    $SQL = "SELECT id FROM usuarios WHERE dni = '" . $DniSQL . "'";
    $Registro = TraerRegistro($SQL, $Conexion);

    return !empty($Registro);
}

function ExistePatente($Patente, $vConexion = null)
{
    $Conexion = $vConexion ?? ConexionBD();
    $PatenteSQL = mysqli_real_escape_string($Conexion, $Patente);
    $SQL = "SELECT id FROM transportes WHERE patente = '" . $PatenteSQL . "'";
    $Registro = TraerRegistro($SQL, $Conexion);

    return !empty($Registro);
}

function Usuario_DenominacionNivel()
{
    return DenominacionNivel($_SESSION['Usuario_Nivel'] ?? null);
}

function Usuario_FuncionesPermitidas()
{
    return DescripcionFuncionesNivel($_SESSION['Usuario_Nivel'] ?? null);
}

// Funciones auxiliares con los nombres originales para compatibilidad interna
function db_connect()
{
    return ConexionBD();
}

function db_query($sql)
{
    return EjecutarConsulta($sql);
}

function db_fetch_all($sql)
{
    return TraerListado($sql);
}

function db_fetch_one($sql)
{
    return TraerRegistro($sql);
}

function redirect($path)
{
    Redireccionar($path);
}

function authenticate_user($username, $password)
{
    return DatosLogin($username, $password);
}

function login_user($user)
{
    GuardarSesionUsuario([
        'ID' => $user['id'] ?? null,
        'APELLIDO' => $user['apellido'] ?? null,
        'NOMBRE' => $user['nombre'] ?? null,
        'USUARIO' => $user['usuario'] ?? null,
        'NIVEL' => $user['id_nivel'] ?? null,
        'NIVEL_NOMBRE' => DenominacionNivel($user['id_nivel'] ?? null),
        'IMG' => $user['imagen'] ?? null,
        'SALUDO' => $_SESSION['Usuario_Saludo'] ?? 'Hola',
        'ACTIVO' => 1,
    ]);
}

function logout_user()
{
    CerrarSesionUsuario();
}

function current_user()
{
    return ObtenerUsuarioEnSesion();
}

function require_login()
{
    RequiereSesion();
}

function user_full_name($user)
{
    return NombreCompletoUsuario($user);
}

function es_admin()
{
    return EsAdministrador();
}

function es_operador()
{
    return EsOperador();
}

function es_chofer()
{
    return EsChofer();
}

function nivel_denominacion($id)
{
    return DenominacionNivel($id);
}

function descripcion_funciones_por_nivel($id)
{
    return DescripcionFuncionesNivel($id);
}

function format_date_spanish($date)
{
    return FormatearFechaEspaniol($date);
}

function obtener_clase_fila($fechaViaje)
{
    return ObtenerClaseFila($fechaViaje);
}

function calcular_monto_chofer($costo, $porcentaje)
{
    return CalcularMontoChofer($costo, $porcentaje);
}

function obtener_choferes()
{
    return Listar_Choferes();
}

function obtener_transportes()
{
    return Listar_Transportes();
}

function obtener_marcas()
{
    return Listar_Marcas();
}

function obtener_destinos()
{
    return Listar_Destinos();
}

function normalizar_importe($valor)
{
    return NormalizarImporte($valor);
}

function convertir_fecha_formulario($fecha)
{
    return ConvertirFechaFormulario($fecha);
}

function generar_usuario_por_defecto($apellido, $nombre)
{
    return GenerarUsuarioPorDefecto($apellido, $nombre);
}

function generar_usuario_unico($base)
{
    return GenerarUsuarioUnico($base);
}

function guardar_chofer($datos)
{
    return Insertar_Chofer($datos);
}

function guardar_transporte($datos)
{
    return Insertar_Transporte($datos);
}

function guardar_viaje($datos)
{
    return Insertar_Viaje($datos);
}

function obtener_viajes($choferId = null)
{
    return Listar_Viajes($choferId);
}

function campo_requerido($valor)
{
    return CampoRequerido($valor);
}

function validar_dni($dni)
{
    return ValidarDNI($dni);
}

function validar_porcentaje($valor)
{
    return ValidarPorcentaje($valor);
}

function usuario_existe($usuario)
{
    return ExisteUsuario($usuario);
}

function dni_existe($dni)
{
    return ExisteDNI($dni);
}

function patente_existe($patente)
{
    return ExistePatente($patente);
}
