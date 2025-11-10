<?php
// Archivo de conexión siguiendo la estructura presentada en clase.
function ConexionBD($Host = 'localhost', $User = 'root', $Password = '', $BaseDeDatos = 'tdcsa') {
    $linkConexion = mysqli_connect($Host, $User, $Password, $BaseDeDatos);

    if ($linkConexion === false) {
        die('No se pudo establecer la conexión.');
    }

    return $linkConexion;
}
