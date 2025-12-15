<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requierePermiso("PANEL_ADMIN");

$mensajeOk = "";
$mensajeError = "";

// Marcar PAGADO / RECHAZADO
if (isset($_POST["accion_pago"])) {
    $idUsuario = (int)$_POST["id_usuario"];
    $idCurso = (int)$_POST["id_curso"];
    $accion = $_POST["accion_pago"]; // PAGADO o RECHAZADO
    $ref = mysqli_real_escape_string($conexion, trim($_POST["referencia_pago"] ?? ""));

    if ($accion !== "PAGADO" && $accion !== "RECHAZADO") {
        $mensajeError = "Acción inválida.";
    } else {
        $ok = mysqli_query($conexion, "
          UPDATE usuario_curso
          SET estado_pago='$accion', referencia_pago='$ref'
          WHERE id_usuario=$idUsuario AND id_curso=$idCurso AND estado_pago='PENDIENTE'
        ");

        if ($ok) $mensajeOk = "Pago actualizado a $accion.";
        else $mensajeError = "No se pudo actualizar el pago.";
    }
}

// Pendientes
$sqlPend = "
  SELECT
    u.id_usuario, u.nick, u.email,
    c.id_curso, c.titulo, c.precio,
    uc.estado_pago, uc.referencia_pago, uc.inscrito_en
  FROM usuario_curso uc
  INNER JOIN usuario u ON u.id_usuario = uc.id_usuario
  INNER JOIN curso c ON c.id_curso = uc.id_curso
  WHERE uc.estado='ACTIVO' AND uc.estado_pago='PENDIENTE'
  ORDER BY uc.inscrito_en DESC
";
$resPend = mysqli_query($conexion, $sqlPend);

// Historial (últimos 30)
$sqlHist = "
  SELECT
    u.nick, u.email,
    c.titulo, c.precio,
    uc.estado_pago, uc.referencia_pago, uc.inscrito_en
  FROM usuario_curso uc
  INNER JOIN usuario u ON u.id_usuario = uc.id_usuario
  INNER JOIN curso c ON c.id_curso = uc.id_curso
  WHERE uc.estado='ACTIVO' AND uc.estado_pago IN ('PAGADO','RECHAZADO')
  ORDER BY uc.inscrito_en DESC
  LIMIT 30
";
$resHist = mysqli_query($conexion, $sqlHist);

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Administración · Pagos</h1>
    <p class="p">Confirma pagos pendientes (manual). Luego se puede conectar pasarela.</p>

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
      <a class="btn btn-primary" href="pagos.php">Pagos</a>
    </div>
  </div>

  <div style="height:14px;"></div>

  <div class="hero-grid">

    <!-- Pendientes -->
    <div class="card">
      <h2 style="margin:0 0 8px;">Pendientes</h2>

      <?php if (!$resPend || mysqli_num_rows($resPend) == 0): ?>
        <div class="alert">No hay pagos pendientes.</div>
      <?php else: ?>
        <div style="overflow:auto; margin-top:12px;">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Usuario</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Curso</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Precio</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Referencia</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($x = mysqli_fetch_assoc($resPend)): ?>
                <tr>
                  <td style="padding:10px;">
                    <b><?php echo htmlspecialchars($x["nick"]); ?></b><br>
                    <span class="small"><?php echo htmlspecialchars($x["email"]); ?></span>
                  </td>
                  <td style="padding:10px;"><?php echo htmlspecialchars($x["titulo"]); ?></td>
                  <td style="padding:10px;"><?php echo number_format((float)$x["precio"], 2); ?></td>

                  <td style="padding:10px;">
                    <form method="POST" style="display:flex; gap:8px; flex-wrap:wrap; margin:0;">
                      <input type="hidden" name="id_usuario" value="<?php echo (int)$x["id_usuario"]; ?>">
                      <input type="hidden" name="id_curso" value="<?php echo (int)$x["id_curso"]; ?>">
                      <input class="input" name="referencia_pago"
                             placeholder="Ej: QR-123 / Depósito / TxID"
                             value="<?php echo htmlspecialchars($x["referencia_pago"] ?? ""); ?>"
                             style="min-width:220px;">
                  </td>

                  <td style="padding:10px; display:flex; gap:8px; flex-wrap:wrap;">
                      <button class="btn btn-primary" name="accion_pago" value="PAGADO" type="submit">Marcar PAGADO</button>
                      <button class="btn btn-danger" name="accion_pago" value="RECHAZADO" type="submit">Rechazar</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Historial -->
    <div class="card">
      <h2 style="margin:0 0 8px;">Últimos pagos (historial)</h2>
      <div class="alert">Muestra los últimos 30 marcados como PAGADO o RECHAZADO.</div>

      <?php if (!$resHist || mysqli_num_rows($resHist) == 0): ?>
        <div class="alert" style="margin-top:10px;">Aún no hay historial.</div>
      <?php else: ?>
        <div style="overflow:auto; margin-top:12px;">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Usuario</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Curso</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Precio</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Estado</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Referencia</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($h = mysqli_fetch_assoc($resHist)): ?>
                <tr>
                  <td style="padding:10px;">
                    <b><?php echo htmlspecialchars($h["nick"]); ?></b><br>
                    <span class="small"><?php echo htmlspecialchars($h["email"]); ?></span>
                  </td>
                  <td style="padding:10px;"><?php echo htmlspecialchars($h["titulo"]); ?></td>
                  <td style="padding:10px;"><?php echo number_format((float)$h["precio"], 2); ?></td>
                  <td style="padding:10px;"><b><?php echo htmlspecialchars($h["estado_pago"]); ?></b></td>
                  <td style="padding:10px;"><?php echo htmlspecialchars($h["referencia_pago"] ?? ""); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php require "../pie.php"; ?>
