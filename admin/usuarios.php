<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requierePermiso("PANEL_ADMIN");

$mensajeOk = "";
$mensajeError = "";

/* =========================
   ACCIONES RÁPIDAS (GET)
========================= */
if (isset($_GET["accion"], $_GET["id"])) {
    $id = (int)$_GET["id"];
    $accion = $_GET["accion"];

    if ($accion === "activar") {
        mysqli_query($conexion, "UPDATE usuario SET estado='ACTIVO' WHERE id_usuario=$id");
        $mensajeOk = "Usuario activado.";
    } elseif ($accion === "desactivar") {
        mysqli_query($conexion, "UPDATE usuario SET estado='DESACTIVADO' WHERE id_usuario=$id");
        $mensajeOk = "Usuario desactivado.";
    } elseif ($accion === "cerrar_sesiones") {
        mysqli_query($conexion, "UPDATE sesion_usuario SET revocado_en=NOW() WHERE id_usuario=$id AND revocado_en IS NULL");
        $mensajeOk = "Se cerraron todas las sesiones activas de este usuario.";
    }
}

/* =========================
   GUARDAR EDICIÓN (POST)
========================= */
if (isset($_POST["guardar_usuario"])) {
    $id = (int)$_POST["id_usuario"];
    $nick = mysqli_real_escape_string($conexion, trim($_POST["nick"]));
    $email = mysqli_real_escape_string($conexion, trim($_POST["email"]));
    $idRol = (int)$_POST["id_rol"];
    $idPlan = (int)$_POST["id_plan"];
    $estado = mysqli_real_escape_string($conexion, $_POST["estado"]);

    if ($nick === "" || $email === "") {
        $mensajeError = "Nick y email son obligatorios.";
    } else {
        $ok = mysqli_query($conexion, "
            UPDATE usuario
            SET nick='$nick', email='$email', id_rol=$idRol, id_plan=$idPlan, estado='$estado'
            WHERE id_usuario=$id
        ");

        if ($ok) $mensajeOk = "Usuario actualizado.";
        else $mensajeError = "No se pudo actualizar (¿email duplicado?).";
    }
}

/* =========================
   DATOS PARA SELECTORES
========================= */
$roles = [];
$resRoles = mysqli_query($conexion, "SELECT id_rol, nombre FROM rol ORDER BY nombre");
while ($r = mysqli_fetch_assoc($resRoles)) $roles[] = $r;

$planes = [];
$resPlanes = mysqli_query($conexion, "SELECT id_plan, codigo, nombre, max_sesiones, max_cursos FROM plan ORDER BY id_plan");
while ($p = mysqli_fetch_assoc($resPlanes)) $planes[] = $p;

/* =========================
   LISTADO USUARIOS + CONSUMO
========================= */
$sql = "
  SELECT
    u.id_usuario, u.nick, u.email, u.estado, u.creado_en,
    r.nombre AS rol,
    p.codigo AS plan_codigo, p.nombre AS plan_nombre, p.max_sesiones, p.max_cursos,

    (SELECT COUNT(*)
     FROM sesion_usuario s
     WHERE s.id_usuario=u.id_usuario AND s.revocado_en IS NULL) AS sesiones_activas,

    (SELECT COUNT(*)
     FROM usuario_curso uc
     WHERE uc.id_usuario=u.id_usuario AND uc.estado='ACTIVO') AS cursos_activos

  FROM usuario u
  INNER JOIN rol r ON r.id_rol = u.id_rol
  INNER JOIN plan p ON p.id_plan = u.id_plan
  ORDER BY u.id_usuario DESC
";
$res = mysqli_query($conexion, $sql);

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Administración · Usuarios</h1>
    <p class="p">Gestiona usuarios: activar/desactivar, roles, planes, sesiones (dispositivos) y cursos activos.</p>

    <?php if ($mensajeOk): ?>
      <div class="alert alert-ok" style="margin-top:12px;">✅ <?php echo htmlspecialchars($mensajeOk); ?></div>
    <?php endif; ?>
    <?php if ($mensajeError): ?>
      <div class="alert alert-err" style="margin-top:12px;">❌ <?php echo htmlspecialchars($mensajeError); ?></div>
    <?php endif; ?>

    <hr class="sep">

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn btn-primary" href="usuarios.php">Usuarios</a>
      <a class="btn" href="roles.php">Roles</a>
      <a class="btn" href="permisos.php">Permisos</a>
      <a class="btn" href="cursos.php">Cursos</a>
      <a class="btn" href="pagos.php">Pagos</a>
    </div>
  </div>

  <div style="height:14px;"></div>

  <div class="card">
    <h2 style="margin:0 0 8px;">Listado</h2>
    <div class="alert">
      <b>Verificación por email:</b> se refleja en <b>estado</b> (PENDIENTE/ACTIVO/DESACTIVADO).<br>
      <b>Sesiones:</b> número de dispositivos con sesión activa según plan. <b>Cursos:</b> cursos activos simultáneos.
    </div>

    <div style="overflow:auto; margin-top:12px;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">ID</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Usuario</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Email</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Rol</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Plan</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Sesiones</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Cursos</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Estado</th>
            <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Acciones</th>
          </tr>
        </thead>

        <tbody>
          <?php while ($u = mysqli_fetch_assoc($res)): ?>
            <?php
              $sesAct = (int)$u["sesiones_activas"];
              $sesMax = (int)$u["max_sesiones"];
              $curAct = (int)$u["cursos_activos"];
              $curMax = (int)($u["max_cursos"] ?? 0);
            ?>
            <tr>
              <td style="padding:10px;"><?php echo (int)$u["id_usuario"]; ?></td>

              <td style="padding:10px;">
                <form method="POST" style="display:grid; gap:8px; margin:0;">
                  <input type="hidden" name="id_usuario" value="<?php echo (int)$u["id_usuario"]; ?>">
                  <input class="input" name="nick" value="<?php echo htmlspecialchars($u["nick"]); ?>" required>

                  <!-- Mini info desplegable (no satura)
                  <details style="margin-top:6px;">
                    <summary style="cursor:pointer;">Ver</summary>
                    <div class="alert" style="margin-top:8px;">
                      <div class="small">Creado: <?php echo htmlspecialchars($u["creado_en"]); ?></div>
                      <div class="small">Plan: <b><?php echo htmlspecialchars($u["plan_codigo"]); ?></b></div>
                      <div class="small">Sesiones: <b><?php echo $sesAct; ?></b> / <?php echo $sesMax; ?></div>
                      <div class="small">Cursos: <b><?php echo $curAct; ?></b> / <?php echo $curMax; ?></div>
                    </div>
                  </details> -->
              </td>

              <td style="padding:10px;">
                  <input class="input" name="email" type="email" value="<?php echo htmlspecialchars($u["email"]); ?>" required>
              </td>

              <td style="padding:10px;">
                  <select class="input" name="id_rol" style="max-width:220px;">
                    <?php foreach ($roles as $r): ?>
                      <option value="<?php echo (int)$r["id_rol"]; ?>" <?php echo ($r["nombre"] === $u["rol"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($r["nombre"]); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
              </td>

              <td style="padding:10px;">
                  <select class="input" name="id_plan" style="max-width:280px;">
                    <?php foreach ($planes as $p): ?>
                      <?php
                        $textoPlan = $p["codigo"] . " · " . $p["nombre"] .
                          " (sesiones: " . $p["max_sesiones"] . ", cursos: " . ($p["max_cursos"] ?? 0) . ")";
                      ?>
                      <option value="<?php echo (int)$p["id_plan"]; ?>" <?php echo ($p["plan_codigo"] === $u["plan_codigo"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($textoPlan); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
              </td>

              <td style="padding:10px;">
                <b><?php echo $sesAct; ?></b> / <?php echo $sesMax; ?>
              </td>

              <td style="padding:10px;">
                <b><?php echo $curAct; ?></b> / <?php echo $curMax; ?>
              </td>

              <td style="padding:10px;">
                  <select class="input" name="estado" style="max-width:180px;">
                    <option value="PENDIENTE" <?php echo ($u["estado"]==="PENDIENTE")?"selected":""; ?>>PENDIENTE</option>
                    <option value="ACTIVO" <?php echo ($u["estado"]==="ACTIVO")?"selected":""; ?>>ACTIVO</option>
                    <option value="DESACTIVADO" <?php echo ($u["estado"]==="DESACTIVADO")?"selected":""; ?>>DESACTIVADO</option>
                  </select>
              </td>

              <td style="padding:10px; display:flex; gap:8px; flex-wrap:wrap;">
                  <button class="btn" name="guardar_usuario" value="1" type="submit">Guardar</button>
                </form>

                <?php if ($u["estado"] === "ACTIVO"): ?>
                  <a class="btn btn-danger" href="usuarios.php?accion=desactivar&id=<?php echo (int)$u["id_usuario"]; ?>">Desactivar</a>
                <?php else: ?>
                  <a class="btn btn-primary" href="usuarios.php?accion=activar&id=<?php echo (int)$u["id_usuario"]; ?>">Activar</a>
                <?php endif; ?>

                <?php if ($sesAct > 0): ?>
                  <a class="btn" href="usuarios.php?accion=cerrar_sesiones&id=<?php echo (int)$u["id_usuario"]; ?>">Cerrar sesiones</a>
                <?php endif; ?>
                <a class="btn" href="solicitudes_plan.php">Solicitudes de plan</a>

              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require "../pie.php"; ?>
