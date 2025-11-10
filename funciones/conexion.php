<?php
// Archivo de conexión siguiendo la estructura presentada en clase.
/**
 * Archivo: funciones/conexion.php (línea 13).
 * Propósito: crear y devolver un vínculo activo con la base de datos MySQL de la aplicación.
 * Funcionamiento: utiliza mysqli_connect con los parámetros recibidos, valida que la conexión no falle
 *   y, si se establece correctamente, configura el juego de caracteres en UTF-8 para evitar problemas
 *   con acentos y caracteres especiales.
 * Retorno: devuelve el recurso de conexión generado por mysqli_connect para reutilizarlo en el resto
 *   de los módulos. En caso de error aborta la ejecución con un mensaje porque no se puede continuar
 *   sin la base de datos.
 */
function ConexionBD($Host = 'localhost', $User = 'root', $Password = '', $BaseDeDatos = 'tdcsa') {
    $linkConexion = mysqli_connect($Host, $User, $Password, $BaseDeDatos);

    if ($linkConexion === false) {
        die('No se pudo establecer la conexión.');
    }

    mysqli_set_charset($linkConexion, 'utf8');

    return $linkConexion;
}
