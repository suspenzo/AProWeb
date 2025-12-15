
<?php
require 'conexion.php';

$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuario");
$fila = mysqli_fetch_assoc($res);

echo "Conexión OK. Usuarios en BD: " . $fila['total'];