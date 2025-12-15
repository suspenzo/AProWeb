<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requiereSesion();

$idUsuario = (int)$_SESSION["id_usuario"];

$sql = "
  SELECT sp.id_solicitud, sp.estado, sp.creado_en, sp.revisado_en, sp.comentario_admin,
         p.codigo, p.nombre
  FROM solicitud_plan sp
  INNER JOIN plan p ON p.id_plan = sp.id_plan_solicitado
  WHERE sp.id_usuario=$idUsuario
  ORDER BY sp.id_solicitud DESC
";
$res = mysqli_query($conexion, $sql);

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Mis solicitudes de plan</h1>
    <p class="p">Aquí verás el estado de tus solicitudes.</p>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
      <a class="btn btn-primary" href="mi_plan.php">Volver a Mi suscripción</a>
      <a class="btn" href="../index.php">Inicio</a>
    </div>
  </div>

  <div style="height:14px;"></div>

  <div class="card">
    <?php if (!$res || mysqli_num_rows($res) == 0): ?>
      <div class="alert">No tienes solicitudes todavía.</div>
    <?php else: ?>
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Plan</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Estado</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Creado</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Revisión</th>
            </tr>
          </thead>
          <tbody>
            <?php while($f=mysqli_fetch_assoc($res)): ?>
              <tr>
                <td style="padding:10px;">
                  <b><?php echo htmlspecialchars($f["codigo"]); ?></b> · <?php echo htmlspecialchars($f["nombre"]); ?>
                  <?php if (!empty($f["comentario_admin"])): ?>
                    <div class="small">Nota: <?php echo htmlspecialchars($f["comentario_admin"]); ?></div>
                  <?php endif; ?>
                </td>
                <td style="padding:10px;">
                  <span class="pill" style="border:1px solid rgba(255,255,255,.10);">
                    <?php echo htmlspecialchars($f["estado"]); ?>
                  </span>
                </td>
                <td style="padding:10px;"><?php echo htmlspecialchars($f["creado_en"]); ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars($f["revisado_en"] ?? ""); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require "../pie.php"; ?>
