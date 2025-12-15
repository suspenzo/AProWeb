<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requiereSesion();

$idUsuario = (int)$_SESSION["id_usuario"];
$idPlanSolicitado = isset($_POST["id_plan_solicitado"]) ? (int)$_POST["id_plan_solicitado"] : 0;

if ($idPlanSolicitado <= 0) {
    header("Location: mi_plan.php");
    exit;
}

// No permitir pedir SIN_PLAN
$res = mysqli_query($conexion, "SELECT codigo FROM plan WHERE id_plan=$idPlanSolicitado LIMIT 1");
$p = $res ? mysqli_fetch_assoc($res) : null;

if (!$p || $p["codigo"] === "SIN_PLAN") {
    header("Location: mi_plan.php");
    exit;
}

// Evitar duplicar solicitud pendiente
$ya = mysqli_query($conexion, "
  SELECT id_solicitud
  FROM solicitud_plan
  WHERE id_usuario=$idUsuario AND estado='PENDIENTE'
  LIMIT 1
");
if ($ya && mysqli_fetch_assoc($ya)) {
    header("Location: mi_plan.php?error=pendiente");
    exit;
}

mysqli_query($conexion, "
  INSERT INTO solicitud_plan (id_usuario, id_plan_solicitado)
  VALUES ($idUsuario, $idPlanSolicitado)
");

header("Location: mi_plan.php?ok=solicitud_enviada");
exit;
