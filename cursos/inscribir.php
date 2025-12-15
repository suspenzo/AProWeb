<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requiereSesion();

$idUsuario = (int)$_SESSION["id_usuario"];
$idCurso = isset($_POST["id_curso"]) ? (int)$_POST["id_curso"] : 0;

if ($idCurso <= 0) {
    header("Location: ../index.php");
    exit;
}

/* =========================================
   1) Traer plan del usuario (max_cursos)
========================================= */
$sqlPlan = "
  SELECT p.max_cursos
  FROM usuario u
  INNER JOIN plan p ON p.id_plan = u.id_plan
  WHERE u.id_usuario = $idUsuario
  LIMIT 1
";
$resPlan = mysqli_query($conexion, $sqlPlan);
$plan = $resPlan ? mysqli_fetch_assoc($resPlan) : null;

$maxCursos = $plan ? (int)$plan["max_cursos"] : 0;

// Si no tiene plan (SIN_PLAN = 0 cursos permitidos)
if ($maxCursos <= 0) {
    header("Location: ../cuenta/mi_plan.php?error=sin_plan");
    exit;
}

/* =========================================
   2) Ver si ya está inscrito a este curso
   - Si ya está ACTIVO → no hacer nada
   - Si está CANCELADO → lo reactivamos
========================================= */
$sqlExiste = "
  SELECT estado, estado_pago
  FROM usuario_curso
  WHERE id_usuario=$idUsuario AND id_curso=$idCurso
  LIMIT 1
";
$resExiste = mysqli_query($conexion, $sqlExiste);
$existe = $resExiste ? mysqli_fetch_assoc($resExiste) : null;

if ($existe && $existe["estado"] === "ACTIVO") {
    header("Location: mis_cursos.php?ok=ya_inscrito");
    exit;
}

/* =========================================
   3) Contar cursos activos actuales
========================================= */
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM usuario_curso
  WHERE id_usuario=$idUsuario AND estado='ACTIVO'
";
$resCount = mysqli_query($conexion, $sqlCount);
$c = $resCount ? mysqli_fetch_assoc($resCount) : ["total" => 0];
$totalActivos = (int)($c["total"] ?? 0);

if ($totalActivos >= $maxCursos) {
    header("Location: mis_cursos.php?error=max_cursos");
    exit;
}

/* =========================================
   4) Precio del curso → estado_pago
========================================= */
$resCurso = mysqli_query($conexion, "SELECT precio FROM curso WHERE id_curso=$idCurso LIMIT 1");
$curso = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

$precio = $curso ? (float)$curso["precio"] : 0.0;
$estadoPago = ($precio > 0) ? "PENDIENTE" : "GRATIS";

/* =========================================
   5) Inscribir / reactivar
========================================= */
if ($existe && $existe["estado"] === "CANCELADO") {
    // Reactivar inscripción cancelada
    mysqli_query($conexion, "
      UPDATE usuario_curso
      SET estado='ACTIVO', estado_pago='$estadoPago'
      WHERE id_usuario=$idUsuario AND id_curso=$idCurso
    ");
} else {
    // Insertar nueva inscripción
    mysqli_query($conexion, "
      INSERT INTO usuario_curso (id_usuario, id_curso, estado, estado_pago)
      VALUES ($idUsuario, $idCurso, 'ACTIVO', '$estadoPago')
    ");
}

header("Location: mis_cursos.php?ok=inscrito");
exit;
