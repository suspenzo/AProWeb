<?php
require_once "../autenticacion.php";
require_once "../conexion.php";

requiereSesion();

$idUsuario = (int)$_SESSION["id_usuario"];

$sql = "
  SELECT
    p.codigo, p.nombre, p.max_sesiones, p.max_cursos, p.precio_mensual,
    (SELECT COUNT(*) FROM sesion_usuario s WHERE s.id_usuario=$idUsuario AND s.revocado_en IS NULL) AS sesiones_activas,
    (SELECT COUNT(*) FROM usuario_curso uc WHERE uc.id_usuario=$idUsuario AND uc.estado='ACTIVO') AS cursos_activos
  FROM usuario u
  INNER JOIN plan p ON p.id_plan=u.id_plan
  WHERE u.id_usuario=$idUsuario
  LIMIT 1
";
$res = mysqli_query($conexion, $sql);
$plan = $res ? mysqli_fetch_assoc($res) : null;

/* =========================
   Lista de planes disponibles (sin SIN_PLAN)
========================= */
$listaPlanes = [];
$resLista = mysqli_query($conexion, "
  SELECT id_plan, codigo, nombre, max_sesiones, max_cursos, precio_mensual
  FROM plan
  WHERE codigo<>'BASICO'
  ORDER BY id_plan
");
while ($x = $resLista ? mysqli_fetch_assoc($resLista) : null) {
  if (!$x) break;
  $listaPlanes[] = $x;
}

/* =========================
   Ver si ya hay solicitud pendiente
========================= */
$solPendiente = null;
$resPend = mysqli_query($conexion, "
  SELECT sp.id_solicitud, sp.creado_en, p.codigo, p.nombre
  FROM solicitud_plan sp
  INNER JOIN plan p ON p.id_plan = sp.id_plan_solicitado
  WHERE sp.id_usuario=$idUsuario AND sp.estado='PENDIENTE'
  LIMIT 1
");
$solPendiente = $resPend ? mysqli_fetch_assoc($resPend) : null;

require "../encabezado.php";
?>

<section class="hero">
  <div class="hero-grid">

    <div class="card">
      <h1 class="h1">Mi suscripción</h1>
      <p class="p">Aquí ves tu plan actual, tus límites y puedes solicitar un cambio.</p>

      <?php if (isset($_GET["error"]) && $_GET["error"] === "sin_plan"): ?>
        <div class="alert alert-err" style="margin-top:12px;">
          ❌ Para inscribirte necesitas activar un plan.
        </div>
      <?php endif; ?>

      <?php if (isset($_GET["ok"]) && $_GET["ok"] === "solicitud_enviada"): ?>
        <div class="alert alert-ok" style="margin-top:12px;">
          ✅ Solicitud enviada. El administrador la revisará.
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Resumen</h2>

      <?php if (!$plan): ?>
        <div class="alert alert-err">No se pudo cargar tu plan.</div>
      <?php else: ?>
        <div class="alert">
          Plan actual: <b><?php echo htmlspecialchars($plan["nombre"]); ?></b>
          <div class="small">Código: <?php echo htmlspecialchars($plan["codigo"]); ?></div>
          <div class="small">Precio mensual: <?php echo number_format((float)$plan["precio_mensual"], 2); ?></div>
        </div>

        <div class="grid2" style="gap:10px; margin-top:10px;">
          <div class="alert">
            <div class="small">Sesiones (dispositivos)</div>
            <div style="font-weight:800; font-size:18px;">
              <?php echo (int)$plan["sesiones_activas"]; ?> / <?php echo (int)$plan["max_sesiones"]; ?>
            </div>
          </div>
          <div class="alert">
            <div class="small">Cursos activos</div>
            <div style="font-weight:800; font-size:18px;">
              <?php echo (int)$plan["cursos_activos"]; ?> / <?php echo (int)$plan["max_cursos"]; ?>
            </div>
          </div>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
          <a class="btn btn-primary" href="../index.php">Ver cursos</a>
          <a class="btn" href="../cursos/mis_cursos.php">Mis cursos</a>
          <a class="btn" href="mis_sesiones.php">Mis sesiones</a>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <div style="height:14px;"></div>

  <div class="card">
    <h2 style="margin:0 0 8px;">Cambiar de plan</h2>

    <?php if ($solPendiente): ?>
      <div class="alert">
        Ya tienes una solicitud <b>PENDIENTE</b>:
        <b><?php echo htmlspecialchars($solPendiente["codigo"]); ?></b> · <?php echo htmlspecialchars($solPendiente["nombre"]); ?>
        <div class="small">Enviada: <?php echo htmlspecialchars($solPendiente["creado_en"]); ?></div>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
        <a class="btn btn-primary" href="mis_solicitudes.php">Ver mis solicitudes</a>
      </div>
    <?php else: ?>
      <div class="alert">
        Elige un plan y envía la solicitud. Al aprobarse, tu plan se actualiza automáticamente.
      </div>

      <?php if (count($listaPlanes) === 0): ?>
        <div class="alert alert-err" style="margin-top:10px;">No hay planes disponibles configurados.</div>
      <?php else: ?>
        <form class="form" method="POST" action="solicitar_plan.php" style="margin-top:12px;">
          <select class="input" name="id_plan_solicitado" required>
            <?php foreach ($listaPlanes as $pl): ?>
              <?php
                $texto = $pl["codigo"] . " · " . $pl["nombre"] .
                  " (sesiones: " . $pl["max_sesiones"] . ", cursos: " . $pl["max_cursos"] . ")" .
                  " · Bs " . number_format((float)$pl["precio_mensual"], 2);
              ?>
              <option value="<?php echo (int)$pl["id_plan"]; ?>">
                <?php echo htmlspecialchars($texto); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <button class="btn btn-primary" type="submit">Solicitar cambio</button>
        </form>

        <div style="margin-top:12px;">
          <a class="btn" href="mis_solicitudes.php">Ver mis solicitudes</a>
        </div>

        <div class="alert" style="margin-top:12px;">
          Nota: El pago real con tarjeta se puede integrar después (Stripe/MercadoPago). Por ahora el admin confirma manualmente.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</section>

<?php require "../pie.php"; ?>
