<?php
require_once 'funciones/conexion.php';

if (!isset($_SESSION)) {
    session_start();
}

function Redireccionar($Ruta)
{
    header('Location: ' . $Ruta);
    exit;
}

function DatosLogin($vUsuario, $vClave, $vConexion)
{
    $Usuario = array();

    $SQL = "SELECT U.id, U.apellido, U.nombre, U.usuario, U.clave, U.id_nivel, U.imagen, U.activo,"
        . " N.denominacion AS NombreNivel"
        . " FROM usuarios U, niveles N"
        . " WHERE U.id_nivel = N.id"
        . " AND U.usuario = '" . $vUsuario . "'"
        . " AND U.clave = '" . $vClave . "'";

    $rs = mysqli_query($vConexion, $SQL);

    if ($rs != false) {
        $data = mysqli_fetch_array($rs);

        if (!empty($data)) {
            $Usuario['ID'] = $data['id'];
            $Usuario['APELLIDO'] = $data['apellido'];
            $Usuario['NOMBRE'] = $data['nombre'];
            $Usuario['USUARIO'] = $data['usuario'];
            $Usuario['CLAVE'] = $data['clave'];
            $Usuario['NIVEL'] = $data['id_nivel'];
            $Usuario['NIVEL_NOMBRE'] = $data['NombreNivel'];
            $Usuario['ACTIVO'] = $data['activo'];

            if (empty($data['imagen'])) {
                $Usuario['IMG'] = 'user.png';
            } else {
                $Usuario['IMG'] = $data['imagen'];
            }

            $Usuario['SALUDO'] = 'Hola';
        }

        mysqli_free_result($rs);
    }

    return $Usuario;
}

function GuardarSesionUsuario($DatosUsuario)
{
    $_SESSION['Usuario_ID'] = !empty($DatosUsuario['ID']) ? $DatosUsuario['ID'] : 0;
    $_SESSION['Usuario_Nombre'] = !empty($DatosUsuario['NOMBRE']) ? $DatosUsuario['NOMBRE'] : '';
    $_SESSION['Usuario_Apellido'] = !empty($DatosUsuario['APELLIDO']) ? $DatosUsuario['APELLIDO'] : '';
    $_SESSION['Usuario_Usuario'] = !empty($DatosUsuario['USUARIO']) ? $DatosUsuario['USUARIO'] : '';
    $_SESSION['Usuario_Nivel'] = !empty($DatosUsuario['NIVEL']) ? $DatosUsuario['NIVEL'] : 0;
    $_SESSION['Usuario_NombreNivel'] = !empty($DatosUsuario['NIVEL_NOMBRE']) ? $DatosUsuario['NIVEL_NOMBRE'] : '';
    $_SESSION['Usuario_Img'] = !empty($DatosUsuario['IMG']) ? $DatosUsuario['IMG'] : 'user.png';
    $_SESSION['Usuario_Saludo'] = !empty($DatosUsuario['SALUDO']) ? $DatosUsuario['SALUDO'] : 'Hola';
    $_SESSION['Usuario_Activo'] = !empty($DatosUsuario['ACTIVO']) ? $DatosUsuario['ACTIVO'] : 0;
}

function CerrarSesionUsuario()
{
    $_SESSION = array();
    session_destroy();
}

function ObtenerUsuarioEnSesion()
{
    if (!empty($_SESSION['Usuario_ID'])) {
        $Usuario = array();
        $Usuario['id'] = $_SESSION['Usuario_ID'];
        $Usuario['apellido'] = !empty($_SESSION['Usuario_Apellido']) ? $_SESSION['Usuario_Apellido'] : '';
        $Usuario['nombre'] = !empty($_SESSION['Usuario_Nombre']) ? $_SESSION['Usuario_Nombre'] : '';
        $Usuario['usuario'] = !empty($_SESSION['Usuario_Usuario']) ? $_SESSION['Usuario_Usuario'] : '';
        $Usuario['id_nivel'] = !empty($_SESSION['Usuario_Nivel']) ? $_SESSION['Usuario_Nivel'] : 0;
        $Usuario['imagen'] = !empty($_SESSION['Usuario_Img']) ? $_SESSION['Usuario_Img'] : 'user.png';
        return $Usuario;
    }

    return array();
}

function RequiereSesion()
{
    if (empty($_SESSION['Usuario_ID'])) {
        Redireccionar('login.php');
    }
}

function UsuarioEstaLogueado()
{
    return !empty($_SESSION['Usuario_ID']);
}

function NombreCompletoUsuario($Usuario)
{
    $Apellido = isset($Usuario['apellido']) ? $Usuario['apellido'] : '';
    $Nombre = isset($Usuario['nombre']) ? $Usuario['nombre'] : '';

    if ($Apellido != '' && $Nombre != '') {
        return $Apellido . ', ' . $Nombre;
    }

    return trim($Apellido . ' ' . $Nombre);
}

function DenominacionNivel($IdNivel)
{
    if ($IdNivel == 1) {
        return 'Administrador';
    }

    if ($IdNivel == 2) {
        return 'Operador';
    }

    if ($IdNivel == 3) {
        return 'Chofer';
    }

    return 'Usuario';
}

function DescripcionFuncionesNivel($IdNivel)
{
    if ($IdNivel == 1) {
        return 'transportes, choferes y viajes';
    }

    if ($IdNivel == 2) {
        return 'transportes y viajes';
    }

    if ($IdNivel == 3) {
        return 'el seguimiento de los viajes asignados';
    }

    return 'la información disponible en el panel';
}

function EsAdministrador()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && $Usuario['id_nivel'] == 1;
}

function EsOperador()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && $Usuario['id_nivel'] == 2;
}

function EsChofer()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && $Usuario['id_nivel'] == 3;
}

function ObtenerPermisosListadoViajes($Usuario = null)
{
    if ($Usuario === null) {
        $Usuario = ObtenerUsuarioEnSesion();
    }

    $Nivel = isset($Usuario['id_nivel']) ? (int) $Usuario['id_nivel'] : 0;

    return array(
        'mostrar_costo' => $Nivel !== 3,
        'mostrar_monto_chofer' => $Nivel !== 2,
        'mostrar_porcentaje_monto' => $Nivel !== 3,
    );
}

function Listar_Choferes($vConexion)
{
    $Listado = array();

    $SQL = "SELECT id, apellido, nombre, dni FROM usuarios WHERE id_nivel = 3 AND activo = 1 ORDER BY apellido, nombre";
    $rs = mysqli_query($vConexion, $SQL);

    if ($rs != false) {
        $i = 0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['id'] = $data['id'];
            $Listado[$i]['apellido'] = $data['apellido'];
            $Listado[$i]['nombre'] = $data['nombre'];
            $Listado[$i]['dni'] = $data['dni'];
            $i++;
        }
        mysqli_free_result($rs);
    }

    return $Listado;
}

function Listar_Transportes($vConexion)
{
    $Listado = array();

    $SQL = "SELECT t.id, m.denominacion AS marca, t.modelo, t.patente"
        . " FROM transportes t, marcas m"
        . " WHERE m.id = t.marca_id"
        . " AND t.disponible = 1"
        . " ORDER BY m.denominacion, t.modelo, t.patente";

    $rs = mysqli_query($vConexion, $SQL);

    if ($rs != false) {
        $i = 0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['id'] = $data['id'];
            $Listado[$i]['marca'] = $data['marca'];
            $Listado[$i]['modelo'] = $data['modelo'];
            $Listado[$i]['patente'] = $data['patente'];
            $i++;
        }
        mysqli_free_result($rs);
    }

    return $Listado;
}

function Listar_Marcas($vConexion)
{
    $Listado = array();

    $SQL = "SELECT id, denominacion FROM marcas ORDER BY denominacion";
    $rs = mysqli_query($vConexion, $SQL);

    if ($rs != false) {
        $i = 0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['id'] = $data['id'];
            $Listado[$i]['denominacion'] = $data['denominacion'];
            $i++;
        }
        mysqli_free_result($rs);
    }

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

    if (count($Partes) != 3) {
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

function Listar_Destinos($vConexion)
{
    $Listado = array();

    $SQL = "SELECT id, denominacion FROM destinos ORDER BY denominacion";
    $rs = mysqli_query($vConexion, $SQL);

    if ($rs != false) {
        $i = 0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['id'] = $data['id'];
            $Listado[$i]['denominacion'] = $data['denominacion'];
            $i++;
        }
        mysqli_free_result($rs);
    }

    return $Listado;
}

function Insertar_Chofer($Datos, $vConexion)
{

    $Apellido = isset($Datos['apellido']) ? $Datos['apellido'] : '';
    $Nombre = isset($Datos['nombre']) ? $Datos['nombre'] : '';
    $Dni = isset($Datos['dni']) ? $Datos['dni'] : '';
    $Usuario = isset($Datos['usuario']) ? strtolower($Datos['usuario']) : '';
    $Clave = isset($Datos['clave']) ? $Datos['clave'] : '';

    $SQL = "INSERT INTO usuarios (apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion)"
        . " VALUES ('" . $Apellido . "', '" . $Nombre . "', '" . $Dni . "', '" . $Usuario . "', '" . $Clave . "', 1, 3, NOW())";

    $Insertado = mysqli_query($vConexion, $SQL);

    if ($Insertado == false) {
        die('No se pudo ejecutar la inserción.');
    }

    return array(
        'id' => mysqli_insert_id($vConexion),
        'usuario' => $Usuario,
        'clave' => $Clave,
    );
}

function Insertar_Transporte($Datos, $vConexion)
{

    $Marca = isset($Datos['marca_id']) ? $Datos['marca_id'] : 0;
    $Modelo = isset($Datos['modelo']) ? $Datos['modelo'] : '';
    $Patente = isset($Datos['patente']) ? $Datos['patente'] : '';
    $Anio = isset($Datos['anio']) ? $Datos['anio'] : 0;
    $Disponible = isset($Datos['disponible']) ? $Datos['disponible'] : 0;

    $SQL = "INSERT INTO transportes (marca_id, modelo, patente, anio, disponible, fecha_creacion)"
        . " VALUES (" . $Marca . ", '" . $Modelo . "', '" . $Patente . "', " . $Anio . ", " . $Disponible . ", NOW())";

    $Insertado = mysqli_query($vConexion, $SQL);

    if ($Insertado == false) {
        die('No se pudo ejecutar la inserción.');
    }

    return mysqli_insert_id($vConexion);
}

function Insertar_Viaje($Datos, $vConexion)
{

    $Chofer = isset($Datos['chofer_id']) ? $Datos['chofer_id'] : 0;
    $Transporte = isset($Datos['transporte_id']) ? $Datos['transporte_id'] : 0;
    $Fecha = isset($Datos['fecha_programada']) ? $Datos['fecha_programada'] : '';
    $Destino = isset($Datos['destino_id']) ? $Datos['destino_id'] : 0;
    $Costo = isset($Datos['costo']) ? $Datos['costo'] : 0;
    $Porcentaje = isset($Datos['porcentaje_chofer']) ? $Datos['porcentaje_chofer'] : 0;
    $CreadoPor = !empty($Datos['creado_por']) ? $Datos['creado_por'] : 'NULL';

    $SQL = "INSERT INTO viajes (chofer_id, transporte_id, fecha_programada, destino_id, costo, porcentaje_chofer, creado_por, fecha_creacion)"
        . " VALUES (" . $Chofer . ", " . $Transporte . ", '" . $Fecha . "', " . $Destino . ", " . $Costo . ", " . $Porcentaje . ", " . $CreadoPor . ", NOW())";

    $Insertado = mysqli_query($vConexion, $SQL);

    if ($Insertado == false) {
        die('No se pudo ejecutar la inserción.');
    }

    return mysqli_insert_id($vConexion);
}

function Listar_Viajes($vConexion, $ChoferId = null)
{
    $Listado = array();

    $SQL = "SELECT v.id, v.fecha_programada, d.denominacion AS destino, v.costo, v.porcentaje_chofer,"
        . " c.apellido AS chofer_apellido, c.nombre AS chofer_nombre, c.dni AS chofer_dni,"
        . " m.denominacion AS marca, t.modelo, t.patente"
        . " FROM viajes v, usuarios c, transportes t, marcas m, destinos d"
        . " WHERE c.id = v.chofer_id"
        . " AND t.id = v.transporte_id"
        . " AND m.id = t.marca_id"
        . " AND d.id = v.destino_id";

    if (!empty($ChoferId)) {
        $SQL .= " AND v.chofer_id = " . $ChoferId;
    }

    $SQL .= " ORDER BY v.fecha_programada, d.denominacion";

    $rs = mysqli_query($vConexion, $SQL);

    if ($rs != false) {
        $i = 0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['id'] = $data['id'];
            $Listado[$i]['fecha_programada'] = $data['fecha_programada'];
            $Listado[$i]['destino'] = $data['destino'];
            $Listado[$i]['costo'] = $data['costo'];
            $Listado[$i]['porcentaje_chofer'] = $data['porcentaje_chofer'];
            $Listado[$i]['monto_chofer'] = CalcularMontoChofer((float) $data['costo'], (int) $data['porcentaje_chofer']);
            $Listado[$i]['chofer_apellido'] = $data['chofer_apellido'];
            $Listado[$i]['chofer_nombre'] = $data['chofer_nombre'];
            $Listado[$i]['chofer_dni'] = $data['chofer_dni'];
            $Listado[$i]['marca'] = $data['marca'];
            $Listado[$i]['modelo'] = $data['modelo'];
            $Listado[$i]['patente'] = $data['patente'];
            $i++;
        }
        mysqli_free_result($rs);
    }

    return $Listado;
}

function CampoRequerido($Valor)
{
    return trim($Valor) != '';
}

function ValidarDNI($Dni)
{
    $Dni = trim($Dni);
    return $Dni != '' && ctype_digit($Dni) && strlen($Dni) >= 7 && strlen($Dni) <= 8;
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

function ExisteUsuario($Usuario, $vConexion)
{
    $SQL = "SELECT id FROM usuarios WHERE usuario = '" . $Usuario . "'";
    $rs = mysqli_query($vConexion, $SQL);

    if ($rs == false) {
        return false;
    }

    $Existe = mysqli_fetch_array($rs);
    mysqli_free_result($rs);

    return !empty($Existe);
}

function ExisteDNI($Dni, $vConexion)
{
    $SQL = "SELECT id FROM usuarios WHERE dni = '" . $Dni . "'";
    $rs = mysqli_query($vConexion, $SQL);

    if ($rs == false) {
        return false;
    }

    $Existe = mysqli_fetch_array($rs);
    mysqli_free_result($rs);

    return !empty($Existe);
}

function ExistePatente($Patente, $vConexion)
{
    $SQL = "SELECT id FROM transportes WHERE patente = '" . $Patente . "'";
    $rs = mysqli_query($vConexion, $SQL);

    if ($rs == false) {
        return false;
    }

    $Existe = mysqli_fetch_array($rs);
    mysqli_free_result($rs);

    return !empty($Existe);
}

function FormatearFechaEspaniol($Fecha)
{
    if ($Fecha == '') {
        return '';
    }

    $Timestamp = strtotime($Fecha);

    if ($Timestamp == false) {
        return '';
    }

    return date('d/m/Y', $Timestamp);
}

function ObtenerClaseFila($FechaViaje)
{
    $Timestamp = strtotime($FechaViaje);

    if ($Timestamp == false) {
        return '';
    }

    $FechaNormalizada = date('Y-m-d', $Timestamp);
    $Hoy = date('Y-m-d');
    $Maniana = date('Y-m-d', strtotime($Hoy . ' +1 day'));

    if ($FechaNormalizada < $Hoy) {
        return 'fila-realizado';
    }

    if ($FechaNormalizada == $Hoy) {
        return 'fila-hoy';
    }

    if ($FechaNormalizada == $Maniana) {
        return 'fila-maniana';
    }

    return '';
}

function CalcularMontoChofer($Costo, $Porcentaje)
{
    return round($Costo * $Porcentaje / 100, 2);
}
