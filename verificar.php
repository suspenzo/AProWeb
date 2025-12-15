<?php
require "conexion.php";

if (!isset($_GET["token"])) {
    die("Token no válido.");
}

$token = $_GET["token"];
$tokenHash = hash("sha256", $token);

// Buscar token válido
$sql = "
  SELECT id_verificacion, id_usuario
  FROM verificacion_email
  WHERE token_hash='$tokenHash'
    AND usado_en IS NULL
    AND expira_en >= NOW()
  LIMIT 1
";
$res = mysqli_query($conexion, $sql);
$fila = $res ? mysqli_fetch_assoc($res) : null;

if (!$fila) {
    die("❌ Enlace inválido o expirado.");
}

// Activar usuario
mysqli_query($conexion, "
  UPDATE usuario
  SET estado='ACTIVO'
  WHERE id_usuario={$fila['id_usuario']}
");

// Marcar token usado
mysqli_query($conexion, "
  UPDATE verificacion_email
  SET usado_en=NOW()
  WHERE id_verificacion={$fila['id_verificacion']}
");

header("Location: iniciar_sesion.php");
exit;
