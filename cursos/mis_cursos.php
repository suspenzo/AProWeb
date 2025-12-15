<?php
require_once "../autenticacion.php";
require_once "../conexion.php";

requiereSesion();

$idUsuario = (int)$_SESSION["id_usuario"];

$sql = "
  SELECT
    c.id_curso,
    c.titulo,
    c.descripcion,
    c.precio,
    uc.estado,
    uc.estado_pago,
    uc.inscrito_en
  FROM usuario_curso uc
  INNER JOIN curso c ON c.id_curso = uc.id_curso
  WHERE uc.id_usuario = $idUsuario AND uc.estado = 'ACTIVO'
  ORDER BY uc.inscrito_en DESC
";

$res = mysqli_query($conexion, $sql);

require "../encabezado.php";
?>

<section class="hero">

  <div class="hero-grid">
    <div class="card">
      <h1 class="h1">Mis cursos</h1>
      <p class="p">Gestiona tus cursos activos. Si llegas al límite de tu plan, cancela uno para liberar cupo.</p>

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
        <a class="btn btn-primary" href="../index.php">Ver catálogo</a>
      </div>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Acciones</h2>
      <div class="alert">
        <b>Cancelar</b> libera un cupo si tu plan no permite más cursos simultáneos.
      </div>
      <div class="alert" style="margin-top:10px;">
        Si un curso tiene <b>PENDIENTE</b>, requiere pago (por ahora lo confirma el admin).
      </div>
    </div>
  </div>

  <div style="height:14px;"></div>

  <?php if (isset($_GET["error"]) && $_GET["error"] === "max_cursos"): ?>
    <div class="card">
      <div class="alert alert-err">
        ❌ Llegaste al límite de cursos simultáneos de tu plan. Cancela un curso para inscribirte a otro.
      </div>
    </div>
    <div style="height:14px;"></div>
  <?php endif; ?>

  <?php if (isset($_GET["ok"]) && $_GET["ok"] === "inscrito"): ?>
  <div class="card">
    <div class="alert alert-ok">✅ Te inscribiste al curso correctamente.</div>
  </div>
  <div style="height:14px;"></div>
<?php endif; ?>

<?php if (isset($_GET["ok"]) && $_GET["ok"] === "ya_inscrito"): ?>
  <div class="card">
    <div class="alert">ℹ️ Ya estabas inscrito en ese curso.</div>
  </div>
  <div style="height:14px;"></div>
<?php endif; ?>

<?php if (isset($_GET["ok"]) && $_GET["ok"] === "cancelado"): ?>
  <div class="card">
    <div class="alert alert-ok">✅ Curso cancelado. Se liberó un cupo de tu plan.</div>
  </div>
  <div style="height:14px;"></div>
<?php endif; ?>


  <?php if (!$res || mysqli_num_rows($res) == 0): ?>
    <div class="card">
      <div class="alert">No estás inscrito en ningún curso todavía.</div>
      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-primary" href="../index.php">Explorar cursos</a>
      </div>
    </div>
  <?php else: ?>

    <div class="grid2">
      <?php while ($fila = mysqli_fetch_assoc($res)): ?>
        <?php
          $precio = (float)$fila["precio"];
          $estadoPago = $fila["estado_pago"];
          $badgePago = ($precio <= 0) ? "GRATIS" : $estadoPago;
        ?>

        <div class="card">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
            <div>
              <h2 style="margin:0 0 6px;"><?php echo htmlspecialchars($fila["titulo"]); ?></h2>
              <div class="small">
                Inscrito: <?php echo htmlspecialchars($fila["inscrito_en"]); ?>
              </div>
            </div>

            <div class="pill" style="border:1px solid rgba(255,255,255,.10);">
              <?php echo htmlspecialchars($badgePago); ?>
            </div>
          </div>

          <p class="p" style="margin-top:10px;">
            <?php echo nl2br(htmlspecialchars(substr($fila["descripcion"] ?? "", 0, 170))); ?>...
          </p>

          <hr class="sep">

          <div class="grid2" style="gap:10px;">
            <div class="alert">
              <div class="small">Precio</div>
              <div style="font-weight:800; font-size:18px;">
                <?php echo number_format($precio, 2); ?>
              </div>
            </div>

            <div class="alert">
              <div class="small">Estado de pago</div>
              <div style="font-weight:800; font-size:18px;">
                <?php echo ($precio <= 0) ? "GRATIS" : htmlspecialchars($estadoPago); ?>
              </div>
            </div>
          </div>

          <?php if ($precio > 0 && $estadoPago === "PENDIENTE"): ?>
            <div class="alert" style="margin-top:10px;">
              ⚠️ Este curso requiere pago. Por ahora está <b>PENDIENTE</b> hasta confirmación del administrador.
            </div>
          <?php endif; ?>

          <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
            <form method="POST" action="cancelar.php" style="margin:0;">
              <input type="hidden" name="id_curso" value="<?php echo (int)$fila["id_curso"]; ?>">
              <button class="btn btn-danger" type="submit">Cancelar curso</button>
            </form>

            <a class="btn" href="../index.php">Ver más cursos</a>
          </div>
        </div>

      <?php endwhile; ?>
    </div>

  <?php endif; ?>

</section>

<?php require "../pie.php"; ?>
