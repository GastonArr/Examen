<?php
require_once 'funciones/conexion.php';

if (!isset($_SESSION)) {
    session_start();
}

/**
 * Archivo: funciones/funciones.php (línea 15).
 * Propósito: enviar al navegador a otra ruta cuando se requiere proteger una página o completar un flujo.
 * Funcionamiento: arma una cabecera HTTP Location con la ruta recibida y termina la ejecución para que
 *   no continúe procesándose el script actual.
 * Retorno: no devuelve un valor porque al llamar a exit() finaliza el script inmediatamente.
 */
function Redireccionar($Ruta)
{
    header('Location: ' . $Ruta);
    exit;
}

/**
 * Archivo: funciones/funciones.php (línea 30).
 * Propósito: obtener de la base de datos la información del usuario que intenta iniciar sesión.
 * Funcionamiento: consulta las tablas de usuarios y niveles filtrando por el usuario y clave provistos,
 *   arma un arreglo asociativo con los datos relevantes y asigna una imagen y saludo por defecto si no
 *   vienen definidos en la base.
 * Retorno: devuelve un array con los campos del usuario autenticado o un array vacío si no se encuentra
 *   coincidencia, lo que permite validar el acceso en el controlador que lo utilice.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 78).
 * Propósito: almacenar en la sesión PHP los datos principales del usuario autenticado.
 * Funcionamiento: toma el array armado al validar el login, asigna valores por defecto cuando faltan
 *   y los persiste en $_SESSION para que estén disponibles en todo el sitio.
 * Retorno: no devuelve nada porque su efecto es modificar el estado global de la sesión.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 98).
 * Propósito: finalizar la sesión activa del usuario cuando se cierra la aplicación o se desloguea.
 * Funcionamiento: vacía el arreglo $_SESSION y llama a session_destroy para eliminar los datos
 *   persistidos en el servidor.
 * Retorno: no retorna valores porque su efecto es borrar el contexto de sesión existente.
 */
function CerrarSesionUsuario()
{
    $_SESSION = array();
    session_destroy();
}

/**
 * Archivo: funciones/funciones.php (línea 112).
 * Propósito: recuperar los datos almacenados en la sesión para utilizarlos en las vistas.
 * Funcionamiento: verifica si existe un identificador de usuario en $_SESSION y, de ser así, arma
 *   un arreglo con los campos básicos (apellido, nombre, nivel e imagen). Si no hay usuario, devuelve
 *   un arreglo vacío.
 * Retorno: un array con los datos del usuario en sesión o uno vacío cuando nadie inició sesión.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 135).
 * Propósito: proteger páginas privadas forzando a que exista un usuario autenticado antes de continuar.
 * Funcionamiento: revisa si en la sesión está definido el identificador de usuario y, si falta, redirige
 *   inmediatamente al formulario de login.
 * Retorno: no devuelve valor; su efecto secundario es redirigir o permitir que el script siga su curso.
 */
function RequiereSesion()
{
    if (empty($_SESSION['Usuario_ID'])) {
        Redireccionar('login.php');
    }
}

/**
 * Archivo: funciones/funciones.php (línea 150).
 * Propósito: simplificar la verificación de si existe un usuario autenticado.
 * Funcionamiento: consulta la variable de sesión que almacena el identificador del usuario y evalúa
 *   si posee un valor no vacío.
 * Retorno: devuelve true cuando hay un ID en sesión y false en caso contrario para tomar decisiones
 *   en otras partes del sistema.
 */
function UsuarioEstaLogueado()
{
    return !empty($_SESSION['Usuario_ID']);
}

/**
 * Archivo: funciones/funciones.php (línea 162).
 * Propósito: mostrar el nombre de una persona siempre en un formato amigable para la interfaz.
 * Funcionamiento: combina apellido y nombre separados por coma cuando ambos están presentes, o
 *   los concatena con un espacio si falta alguno.
 * Retorno: cadena con el nombre completo formateado para utilizar en encabezados o listados.
 */
function NombreCompletoUsuario($Usuario)
{
    $Apellido = isset($Usuario['apellido']) ? $Usuario['apellido'] : '';
    $Nombre = isset($Usuario['nombre']) ? $Usuario['nombre'] : '';

    if ($Apellido != '' && $Nombre != '') {
        return $Apellido . ', ' . $Nombre;
    }

    return trim($Apellido . ' ' . $Nombre);
}

/**
 * Archivo: funciones/funciones.php (línea 181).
 * Propósito: traducir el identificador numérico de nivel a una etiqueta comprensible.
 * Funcionamiento: evalúa el valor recibido y devuelve la denominación correspondiente a cada rol
 *   (Administrador, Operador, Chofer) o una etiqueta genérica si el número no está contemplado.
 * Retorno: texto con la descripción del nivel para mostrar en la interfaz.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 205).
 * Propósito: detallar qué tareas puede realizar un usuario según su nivel en el sistema.
 * Funcionamiento: utiliza condicionales para devolver un texto personalizado para cada nivel y un
 *   mensaje genérico si el identificador no coincide con los casos conocidos.
 * Retorno: cadena con la descripción de responsabilidades para mostrar en la UI.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 229).
 * Propósito: saber si el usuario conectado tiene privilegios de administrador.
 * Funcionamiento: obtiene los datos de la sesión y compara el id_nivel con el valor 1 que identifica
 *   a los administradores.
 * Retorno: true cuando el usuario es administrador; false en los demás casos para condicionar accesos.
 */
function EsAdministrador()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && $Usuario['id_nivel'] == 1;
}

/**
 * Archivo: funciones/funciones.php (línea 241).
 * Propósito: identificar si el usuario pertenece al rol de operador.
 * Funcionamiento: recupera el usuario en sesión y compara el id_nivel contra el valor 2.
 * Retorno: booleano indicando si corresponde al perfil de operador, útil para habilitar opciones.
 */
function EsOperador()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && $Usuario['id_nivel'] == 2;
}

/**
 * Archivo: funciones/funciones.php (línea 254).
 * Propósito: comprobar si el usuario logueado es chofer.
 * Funcionamiento: obtiene los datos de sesión y revisa que el id_nivel sea 3, valor reservado para
 *   los choferes.
 * Retorno: true cuando el usuario es chofer; false en caso contrario para personalizar vistas.
 */
function EsChofer()
{
    $Usuario = ObtenerUsuarioEnSesion();
    return !empty($Usuario['id_nivel']) && $Usuario['id_nivel'] == 3;
}

/**
 * Archivo: funciones/funciones.php (línea 267).
 * Propósito: centralizar qué columnas puede ver cada rol en el listado de viajes.
 * Funcionamiento: toma el usuario recibido (o el que está en sesión) y calcula su nivel para armar
 *   un array de banderas que determinan si se muestran costos, montos de chofer y porcentajes.
 * Retorno: arreglo asociativo con claves booleanas que la vista utiliza para decidir qué mostrar.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 289).
 * Propósito: obtener todos los choferes activos para mostrarlos en formularios o listados.
 * Funcionamiento: ejecuta una consulta SQL filtrando usuarios con nivel 3 y activo, recorre el
 *   resultado y arma un array con los datos principales.
 * Retorno: arreglo de choferes disponible para poblar selectores o tablas; si no hay resultados, es un array vacío.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 318).
 * Propósito: listar los transportes disponibles para asignarlos a viajes.
 * Funcionamiento: realiza un JOIN entre transportes y marcas filtrando los que están marcados como
 *   disponibles y ordena el resultado por marca, modelo y patente.
 * Retorno: array con la información de cada transporte disponible; devuelve vacío si no hay registros.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 352).
 * Propósito: recuperar todas las marcas de transportes almacenadas para poblar selectores.
 * Funcionamiento: ejecuta una consulta simple sobre la tabla marcas ordenando alfabéticamente y
 *   construye un array con cada registro.
 * Retorno: arreglo de marcas disponible para utilizar en formularios; vacío si no hay datos.
 */
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


/**
 * Archivo: funciones/funciones.php (línea 380).
 * Propósito: limpiar valores monetarios ingresados desde formularios para guardarlos en formato numérico.
 * Funcionamiento: elimina puntos como separadores de miles, cambia comas por puntos y verifica que
 *   el resultado sea numérico antes de convertirlo a float.
 * Retorno: número flotante listo para persistir o null cuando el valor está vacío o no es válido.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 604).
 * Propósito: transformar fechas ingresadas en formato argentino (dd/mm/aaaa) al formato SQL (aaaa-mm-dd).
 * Funcionamiento: divide la cadena por '/', valida que tenga tres partes y que correspondan a una fecha
 *   válida con checkdate, y finalmente utiliza sprintf para armar la cadena ISO.
 * Retorno: fecha normalizada como string o null si la entrada está vacía o no supera las validaciones.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 436).
 * Propósito: obtener la lista de destinos disponibles para asignar a los viajes.
 * Funcionamiento: ejecuta una consulta ordenada alfabéticamente sobre la tabla destinos y construye
 *   un array con cada registro obtenido.
 * Retorno: arreglo con los destinos vigentes o un array vacío si la tabla no tiene datos.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 463).
 * Propósito: crear un nuevo registro de chofer junto con sus credenciales de acceso.
 * Funcionamiento: normaliza los datos recibidos, ejecuta un INSERT en la tabla usuarios con nivel 3 y
 *   aborta el script si ocurre un error porque el alta es crítica.
 * Retorno: array con el ID generado y las credenciales para informar al operador tras la inserción.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 495).
 * Propósito: registrar un nuevo camión o transporte en la flota de la empresa.
 * Funcionamiento: recoge los datos del formulario, arma la sentencia INSERT sobre la tabla transportes
 *   y detiene la ejecución con un mensaje si la consulta falla para evitar estados inconsistentes.
 * Retorno: identificador autogenerado del transporte para referenciarlo en otras operaciones.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 523).
 * Propósito: dar de alta un nuevo viaje asignando chofer, transporte, destino y costos.
 * Funcionamiento: toma los datos ya validados, construye la sentencia INSERT sobre viajes incluyendo
 *   quién creó el registro y asegura la carga abortando con die si la consulta falla.
 * Retorno: ID del viaje recién creado para poder redirigir o informar al usuario.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 553).
 * Propósito: construir el listado de viajes con toda la información relacionada.
 * Funcionamiento: arma una consulta con múltiples joins entre viajes, usuarios, transportes, marcas y
 *   destinos; opcionalmente filtra por chofer y calcula el monto correspondiente para cada viaje.
 * Retorno: array de viajes con datos listos para mostrar en tablas; vacío si no hay registros.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 604).
 * Propósito: validar rápidamente que un campo obligatorio tenga algún contenido.
 * Funcionamiento: aplica trim al valor recibido para quitar espacios y verifica que la cadena resultante
 *   no sea vacía.
 * Retorno: booleano true cuando hay datos ingresados y false cuando el campo está vacío.
 */
function CampoRequerido($Valor)
{
    return trim($Valor) != '';
}

/**
 * Archivo: funciones/funciones.php (línea 616).
 * Propósito: comprobar que el DNI ingresado tenga formato correcto.
 * Funcionamiento: elimina espacios, verifica que no esté vacío, que todos los caracteres sean dígitos
 *   y que la longitud se encuentre entre 7 y 8 posiciones.
 * Retorno: true si el DNI es válido según esas reglas; false en caso contrario.
 */
function ValidarDNI($Dni)
{
    $Dni = trim($Dni);
    return $Dni != '' && ctype_digit($Dni) && strlen($Dni) >= 7 && strlen($Dni) <= 8;
}

/**
 * Archivo: funciones/funciones.php (línea 629).
 * Propósito: validar que el porcentaje asignado a un chofer sea un número entre 0 y 100.
 * Funcionamiento: limpia espacios, comprueba que haya un valor, que sea numérico y finalmente evalúa
 *   que al convertirlo a entero quede dentro del rango permitido.
 * Retorno: true si el porcentaje es válido; false cuando incumple alguna de las condiciones.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 652).
 * Propósito: verificar si un nombre de usuario ya está registrado antes de crear uno nuevo.
 * Funcionamiento: consulta la tabla usuarios filtrando por el nombre recibido y analiza si la consulta
 *   devolvió algún registro válido.
 * Retorno: true cuando el usuario ya existe; false cuando está disponible o si la consulta falla.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 673).
 * Propósito: impedir que se duplique un DNI en la tabla de usuarios.
 * Funcionamiento: ejecuta una consulta buscando el documento recibido y valida si hay coincidencias.
 * Retorno: true cuando se encontró el DNI; false cuando no existe o si la consulta falla.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 695).
 * Propósito: evitar que se cargue dos veces un mismo vehículo por su patente.
 * Funcionamiento: consulta la tabla transportes con la patente proporcionada y evalúa si se obtuvo un
 *   registro.
 * Retorno: true cuando la patente ya está registrada; false si no existe o la consulta falla.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 717).
 * Propósito: mostrar fechas almacenadas en la base en formato día/mes/año.
 * Funcionamiento: convierte la cadena recibida a timestamp con strtotime y la formatea con date,
 *   devolviendo una cadena vacía si el valor no es interpretable.
 * Retorno: string con la fecha en formato español o cadena vacía cuando no se puede convertir.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 739).
 * Propósito: asignar clases CSS a filas del listado según la fecha del viaje.
 * Funcionamiento: normaliza la fecha recibida, la compara con la fecha actual y con el día siguiente
 *   para decidir qué clase aplicar (realizado, hoy, mañana) o ninguna si es posterior.
 * Retorno: cadena con el nombre de la clase CSS para estilizar la fila correspondiente.
 */
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

/**
 * Archivo: funciones/funciones.php (línea 773).
 * Propósito: determinar cuánto debe cobrar el chofer según el porcentaje asignado.
 * Funcionamiento: multiplica el costo total del viaje por el porcentaje correspondiente y lo divide por
 *   100, redondeando el resultado a dos decimales para representar moneda.
 * Retorno: monto numérico redondeado que se mostrará en listados y reportes.
 */
function CalcularMontoChofer($Costo, $Porcentaje)
{
    return round($Costo * $Porcentaje / 100, 2);
}
