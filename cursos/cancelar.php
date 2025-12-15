<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requiereSesion();

$idUsuario = (int)$_SESSION["id_usuario"];
$idCurso = isset($_POST["id_curso"]) ? (int)$_POST["id_curso"] : 0;

if ($idCurso <= 0) {
    header("Location: mis_cursos.php");
    exit;
}

// Cancelar SOLO si está ACTIVO
mysqli_query($conexion, "
  UPDATE usuario_curso
  SET estado='CANCELADO'
  WHERE id_usuario=$idUsuario AND id_curso=$idCurso AND estado='ACTIVO'
");

header("Location: mis_cursos.php?ok=cancelado");
exit;
