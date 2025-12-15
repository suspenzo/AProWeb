<?php
// conexion.php

$host = "localhost:3307";
$usuario = "root";
$clave = "";
$bd = "cursos"; // <-- el nombre exacto de tu BD

$conexion = mysqli_connect($host, $usuario, $clave, $bd);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Para que acepte tildes y ñ
mysqli_set_charset($conexion, "utf8mb4");
