<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requierePermiso("PANEL_ADMIN");

$mensajeOk = "";
$mensajeError = "";

/* =========================
   ACCIONES (POST)
========================= */
if (isset($_POST["accion"], $_POST["id_solicitud"])) {
    $idSolicitud = (int)$_POST["id_solicitud"];
    $accion = $_POST["accion"];
    $comentario = mysqli_real_escape_string($conexion, trim($_POST["comentario"] ?? ""));

    // Buscar solicitud pendiente con su usuario y plan solicitado
    $resSol = mysqli_query($conexion, "
      SELECT sp.id_solicitud, sp.id_usuario, sp.id_plan_solicitado
      FROM solicitud_plan sp
      WHERE sp.id_solicitud=$idSolicitud AND sp.estado='PENDIENTE'
      LIMIT 1
    ");
    $sol = $resSol ? mysqli_fetch_assoc($resSol) : null;

    if (!$sol) {
        $mensajeError = "La solicitud no existe o ya fue revisada.";
    } else {
        $idUsuario = (int)$sol["id_usuario"];
        $idPlanSolicitado = (int)$sol["id_plan_solicitado"];

        if ($accion === "aprobar") {
            // 1) Cambiar plan del usuario (automático)
            $ok1 = mysqli_query($conexion, "
              UPDATE usuario
              SET id_plan=$idPlanSolicitado
              WHERE id_usuario=$idUsuario
              LIMIT 1
            ");

            // 2) Marcar solicitud como aprobada
            $ok2 = mysqli_query($conexion, "
              UPDATE solicitud_plan
              SET estado='APROBADO', revisado_en=NOW(), comentario_admin='$comentario'
              WHERE id_solicitud=$idSolicitud
              LIMIT 1
            ");

            if ($ok1 && $ok2) $mensajeOk = "Solicitud aprobada. El plan del usuario fue actualizado.";
            else $mensajeError = "No se pudo aprobar la solicitud.";
        }

        if ($accion === "rechazar") {
            $ok = mysqli_query($conexion, "
              UPDATE solicitud_plan
              SET estado='RECHAZADO', revisado_en=NOW(), comentario_admin='$comentario'
              WHERE id_solicitud=$idSolicitud
              LIMIT 1
            ");

            if ($ok) $mensajeOk = "Solicitud rechazada.";
            else $mensajeError = "No se pudo rechazar la solicitud.";
        }
    }
}

/* =========================
   LISTADO DE SOLICITUDES
========================= */
$sql = "
  SELECT
    sp.id_solicitud, sp.estado, sp.creado_en, sp.revisado_en, sp.comentario_admin,
    u.id_usuario, u.nick, u.email,
    p.codigo AS plan_codigo, p.nombre AS plan_nombre, p.max_sesiones, p.max_cursos
  FROM solicitud_plan sp
  INNER JOIN usuario u ON u.id_usuario=sp.id_usuario
  INNER JOIN plan p ON p.id_plan=sp.id_plan_solicitado
  ORDER BY
    (sp.estado='PENDIENTE') DESC,
    sp.id_solicitud DESC
";
$res = mysqli_query($conexion, $sql);

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Administración · Solicitudes de plan</h1>
    <p class="p">Aprueba o rechaza solicitudes. Al aprobar, el plan del usuario se actualiza automáticamente.</p>

    <?php if ($mensajeOk): ?>
      <div class="alert alert-ok" style="margin-top:12px;">✅ <?php echo htmlspecialchars($mensajeOk); ?></div>
    <?php endif; ?>
    <?php if ($mensajeError): ?>
      <div class="alert alert-err" style="margin-top:12px;">❌ <?php echo htmlspecialchars($mensajeError); ?></div>
    <?php endif; ?>

    <hr class="sep">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn" href="usuarios.php">Usuarios</a>
      <a class="btn" href="roles.php">Roles</a>
      <a class="btn" href="permisos.php">Permisos</a>
      <a class="btn" href="cursos.php">Cursos</a>
      <a class="btn btn-primary" href="solicitudes_plan.php">Solicitudes de plan</a>
      <a class="btn" href="pagos.php">Pagos</a>
    </div>
  </div>

  <div style="height:14px;"></div>

  <div class="card">
    <h2 style="margin:0 0 8px;">Listado</h2>

    <?php if (!$res || mysqli_num_rows($res) == 0): ?>
      <div class="alert">No hay solicitudes.</div>
    <?php else: ?>
      <div style="overflow:auto; margin-top:12px;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Usuario</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Plan solicitado</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Estado</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Fecha</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($f = mysqli_fetch_assoc($res)): ?>
              <tr>
                <td style="padding:10px;">
                  <b><?php echo htmlspecialchars($f["nick"]); ?></b>
                  <div class="small"><?php echo htmlspecialchars($f["email"]); ?></div>
                </td>

                <td style="padding:10px;">
                  <b><?php echo htmlspecialchars($f["plan_codigo"]); ?></b> · <?php echo htmlspecialchars($f["plan_nombre"]); ?>
                  <div class="small">Sesiones: <?php echo (int)$f["max_sesiones"]; ?> · Cursos: <?php echo (int)$f["max_cursos"]; ?></div>
                </td>

                <td style="padding:10px;">
                  <span class="pill" style="border:1px solid rgba(255,255,255,.10);">
                    <?php echo htmlspecialchars($f["estado"]); ?>
                  </span>
                  <?php if (!empty($f["comentario_admin"])): ?>
                    <div class="small">Nota: <?php echo htmlspecialchars($f["comentario_admin"]); ?></div>
                  <?php endif; ?>
                </td>

                <td style="padding:10px;">
                  <div class="small">Creado: <?php echo htmlspecialchars($f["creado_en"]); ?></div>
                  <div class="small">Revisado: <?php echo htmlspecialchars($f["revisado_en"] ?? ""); ?></div>
                </td>

                <td style="padding:10px;">
                  <?php if ($f["estado"] === "PENDIENTE"): ?>
                    <form method="POST" style="display:grid; gap:8px; margin:0; min-width:260px;">
                      <input type="hidden" name="id_solicitud" value="<?php echo (int)$f["id_solicitud"]; ?>">
                      <input class="input" name="comentario" placeholder="Comentario (opcional)">

                      <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn btn-primary" name="accion" value="aprobar" type="submit">Aprobar</button>
                        <button class="btn btn-danger" name="accion" value="rechazar" type="submit">Rechazar</button>
                      </div>
                    </form>
                  <?php else: ?>
                    <span class="small">Ya revisado</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require "../pie.php"; ?>
