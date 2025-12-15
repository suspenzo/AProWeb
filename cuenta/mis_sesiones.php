<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requiereSesion();

$idUsuario = (int)$_SESSION["id_usuario"];

// Token del dispositivo actual (para marcar "Esta sesión")
$tokenActual = $_COOKIE["sesion_token"] ?? "";
$tokenActualHash = $tokenActual ? hash("sha256", $tokenActual) : "";

/* =========================
   ACCIONES (POST)
========================= */
if (isset($_POST["accion"])) {
    $accion = $_POST["accion"];

    if ($accion === "cerrar_una") {
        $idSesion = (int)($_POST["id_sesion"] ?? 0);
        if ($idSesion > 0) {
            mysqli_query($conexion, "
              UPDATE sesion_usuario
              SET revocado_en=NOW()
              WHERE id_sesion=$idSesion AND id_usuario=$idUsuario AND revocado_en IS NULL
              LIMIT 1
            ");
        }
        header("Location: mis_sesiones.php?ok=cerrada");
        exit;
    }

    if ($accion === "cerrar_otras") {
        if ($tokenActualHash !== "") {
            mysqli_query($conexion, "
              UPDATE sesion_usuario
              SET revocado_en=NOW()
              WHERE id_usuario=$idUsuario
                AND revocado_en IS NULL
                AND token_hash <> '$tokenActualHash'
            ");
        } else {
            // Si no hay cookie, cerramos todas por seguridad
            mysqli_query($conexion, "
              UPDATE sesion_usuario
              SET revocado_en=NOW()
              WHERE id_usuario=$idUsuario AND revocado_en IS NULL
            ");
        }
        header("Location: mis_sesiones.php?ok=otras");
        exit;
    }
}

/* =========================
   INFO DEL PLAN + CONTEO
========================= */
$sqlPlan = "
  SELECT p.nombre, p.codigo, p.max_sesiones,
    (SELECT COUNT(*) FROM sesion_usuario s WHERE s.id_usuario=$idUsuario AND s.revocado_en IS NULL) AS activas
  FROM usuario u
  INNER JOIN plan p ON p.id_plan=u.id_plan
  WHERE u.id_usuario=$idUsuario
  LIMIT 1
";
$resPlan = mysqli_query($conexion, $sqlPlan);
$plan = $resPlan ? mysqli_fetch_assoc($resPlan) : null;

$maxSes = $plan ? (int)$plan["max_sesiones"] : 1;
$actSes = $plan ? (int)$plan["activas"] : 0;

/* =========================
   LISTA DE SESIONES ACTIVAS
========================= */
$sql = "
  SELECT id_sesion, token_hash, ip, user_agent, creado_en
  FROM sesion_usuario
  WHERE id_usuario=$idUsuario AND revocado_en IS NULL
  ORDER BY creado_en DESC
";
$res = mysqli_query($conexion, $sql);

require "../encabezado.php";
?>

<section class="hero">
  <div class="hero-grid">

    <div class="card">
      <h1 class="h1">Mis sesiones (dispositivos)</h1>
      <p class="p">Aquí puedes ver en qué dispositivos tienes la cuenta abierta y cerrar accesos.</p>

      <?php if (isset($_GET["ok"]) && $_GET["ok"] === "cerrada"): ?>
        <div class="alert alert-ok" style="margin-top:12px;">✅ Sesión cerrada.</div>
      <?php endif; ?>
      <?php if (isset($_GET["ok"]) && $_GET["ok"] === "otras"): ?>
        <div class="alert alert-ok" style="margin-top:12px;">✅ Se cerraron las otras sesiones.</div>
      <?php endif; ?>

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
        <a class="btn" href="mi_plan.php">Mi suscripción</a>
        <a class="btn btn-primary" href="../index.php">Inicio</a>
      </div>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Límite de tu plan</h2>
      <?php if (!$plan): ?>
        <div class="alert alert-err">No se pudo cargar tu plan.</div>
      <?php else: ?>
        <div class="alert">
          Plan: <b><?php echo htmlspecialchars($plan["nombre"]); ?></b>
          <div class="small">Sesiones activas: <b><?php echo $actSes; ?></b> / <?php echo $maxSes; ?></div>
        </div>

        <form method="POST" style="margin-top:12px;">
          <input type="hidden" name="accion" value="cerrar_otras">
          <button class="btn btn-danger" type="submit">Cerrar otras sesiones</button>
        </form>

        <div class="alert" style="margin-top:10px;">
          “Cerrar otras sesiones” deja esta sesión abierta y cierra las demás.
        </div>
      <?php endif; ?>
    </div>

  </div>

  <div style="height:14px;"></div>

  <div class="card">
    <h2 style="margin:0 0 8px;">Sesiones activas</h2>

    <?php if (!$res || mysqli_num_rows($res) == 0): ?>
      <div class="alert">No hay sesiones activas registradas.</div>
    <?php else: ?>
      <div style="overflow:auto; margin-top:12px;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Dispositivo</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">IP</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Inicio</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($s = mysqli_fetch_assoc($res)): ?>
              <?php
                $esActual = ($tokenActualHash !== "" && $s["token_hash"] === $tokenActualHash);
                $ua = $s["user_agent"] ?: "Navegador desconocido";
              ?>
              <tr>
                <td style="padding:10px;">
                  <b><?php echo htmlspecialchars(substr($ua, 0, 60)); ?></b>
                  <?php if ($esActual): ?>
                    <span class="pill" style="margin-left:8px; border:1px solid rgba(255,255,255,.10);">Esta sesión</span>
                  <?php endif; ?>
                </td>
                <td style="padding:10px;"><?php echo htmlspecialchars($s["ip"] ?? ""); ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars($s["creado_en"]); ?></td>
                <td style="padding:10px;">
                  <?php if ($esActual): ?>
                    <span class="small">No puedes cerrar la sesión actual desde aquí. Usa “Salir”.</span>
                  <?php else: ?>
                    <form method="POST" style="margin:0;">
                      <input type="hidden" name="accion" value="cerrar_una">
                      <input type="hidden" name="id_sesion" value="<?php echo (int)$s["id_sesion"]; ?>">
                      <button class="btn btn-danger" type="submit">Cerrar</button>
                    </form>
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
