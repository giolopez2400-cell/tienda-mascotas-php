<?php
$host = "localhost";
$user = "root"; // Usuario por defecto en phpMyAdmin
$pass = "";     // Contraseña por defecto (vacía en XAMPP)
$db   = "tienda_mascotas";

$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>