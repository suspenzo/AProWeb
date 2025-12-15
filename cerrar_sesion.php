<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "conexion.php";

// 1) Revocar sesión de dispositivo (si existe cookie)
if (!empty($_SESSION["id_usuario"]) && !empty($_COOKIE["sesion_token"])) {
    $idUsuario = (int)$_SESSION["id_usuario"];

    $token = $_COOKIE["sesion_token"];
    $tokenHash = hash("sha256", $token);

    // Revocar SOLO esta sesión (este dispositivo)
    mysqli_query($conexion, "
      UPDATE sesion_usuario
      SET revocado_en=NOW()
      WHERE id_usuario=$idUsuario
        AND token_hash='$tokenHash'
        AND revocado_en IS NULL
      LIMIT 1
    ");
}

// 2) Borrar cookie del dispositivo
setcookie("sesion_token", "", time() - 3600, "/");

// 3) Cerrar sesión PHP
session_unset();
session_destroy();

header("Location: index.php");
exit;
