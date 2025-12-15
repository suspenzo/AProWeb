<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requierePermiso("PANEL_ADMIN");

$mensajeOk = "";
$mensajeError = "";

/* =========================
   CREAR ROL
========================= */
if (isset($_POST["crear_rol"])) {
    $nombre = mysqli_real_escape_string($conexion, trim($_POST["nombre"]));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST["descripcion"]));

    if ($nombre === "") {
        $mensajeError = "El nombre del rol es obligatorio.";
    } else {
        $ok = mysqli_query($conexion, "INSERT INTO rol (nombre, descripcion) VALUES ('$nombre', '$descripcion')");
        if ($ok) $mensajeOk = "Rol creado.";
        else $mensajeError = "No se pudo crear (¿nombre duplicado?).";
    }
}

/* =========================
   EDITAR ROL
========================= */
if (isset($_POST["guardar_rol"])) {
    $idRol = (int)$_POST["id_rol"];
    $nombre = mysqli_real_escape_string($conexion, trim($_POST["nombre"]));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST["descripcion"]));

    if ($nombre === "") {
        $mensajeError = "El nombre del rol es obligatorio.";
    } else {
        $ok = mysqli_query($conexion, "UPDATE rol SET nombre='$nombre', descripcion='$descripcion' WHERE id_rol=$idRol");
        if ($ok) $mensajeOk = "Rol actualizado.";
        else $mensajeError = "No se pudo actualizar (¿nombre duplicado?).";
    }
}

/* =========================
   ROL SELECCIONADO
========================= */
$idRolSel = isset($_GET["rol"]) ? (int)$_GET["rol"] : 0;

// Si no viene rol, tomamos el primero
if ($idRolSel <= 0) {
    $tmp = mysqli_query($conexion, "SELECT id_rol FROM rol ORDER BY nombre LIMIT 1");
    $row = $tmp ? mysqli_fetch_assoc($tmp) : null;
    $idRolSel = $row ? (int)$row["id_rol"] : 0;
}

/* =========================
   GUARDAR PERMISOS DEL ROL
========================= */
if (isset($_POST["guardar_permisos"])) {
    $idRol = (int)$_POST["id_rol"];
    $seleccionados = isset($_POST["permisos"]) ? $_POST["permisos"] : [];

    // 1) borrar asignaciones actuales
    mysqli_query($conexion, "DELETE FROM rol_permiso WHERE id_rol=$idRol");

    // 2) insertar seleccionados
    foreach ($seleccionados as $idPermiso) {
        $idPermiso = (int)$idPermiso;
        mysqli_query($conexion, "INSERT INTO rol_permiso (id_rol, id_permiso) VALUES ($idRol, $idPermiso)");
    }

    $mensajeOk = "Permisos del rol actualizados.";
    header("Location: roles.php?rol=$idRol");
    exit;
}

/* =========================
   LISTA ROLES (para selector y tabla)
========================= */
$roles = [];
$resRoles = mysqli_query($conexion, "SELECT id_rol, nombre, descripcion FROM rol ORDER BY nombre");
while ($r = mysqli_fetch_assoc($resRoles)) $roles[] = $r;

/* =========================
   LISTA PERMISOS
========================= */
$permisos = [];
$resPerm = mysqli_query($conexion, "SELECT id_permiso, codigo, descripcion FROM permiso ORDER BY codigo");
while ($p = mysqli_fetch_assoc($resPerm)) $permisos[] = $p;

/* =========================
   PERMISOS ASIGNADOS AL ROL SELECCIONADO
========================= */
$asignados = [];
if ($idRolSel > 0) {
    $resAs = mysqli_query($conexion, "SELECT id_permiso FROM rol_permiso WHERE id_rol=$idRolSel");
    while ($a = mysqli_fetch_assoc($resAs)) $asignados[(int)$a["id_permiso"]] = true;
}

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Administración · Roles</h1>
    <p class="p">Crea/edita roles y asigna permisos con checkbox (se guarda en <b>rol_permiso</b> internamente).</p>

    <?php if ($mensajeOk): ?><div class="alert alert-ok" style="margin-top:12px;">✅ <?php echo htmlspecialchars($mensajeOk); ?></div><?php endif; ?>
    <?php if ($mensajeError): ?><div class="alert alert-err" style="margin-top:12px;">❌ <?php echo htmlspecialchars($mensajeError); ?></div><?php endif; ?>

    <hr class="sep">

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn" href="usuarios.php">Usuarios</a>
      <a class="btn btn-primary" href="roles.php">Roles</a>
      <a class="btn" href="permisos.php">Permisos</a>
      <a class="btn" href="cursos.php">Cursos</a>
      <a class="btn" href="pagos.php">Pagos</a>
    </div>
  </div>

  <div style="height:14px;"></div>

  <div class="hero-grid">

    <!-- Crear rol -->
    <div class="card">
      <h2 style="margin:0 0 8px;">Crear rol</h2>
      <form class="form" method="POST">
        <input class="input" name="nombre" placeholder="Nombre (Ej: DOCENTE)" required>
        <input class="input" name="descripcion" placeholder="Descripción (opcional)">
        <button class="btn btn-primary" name="crear_rol" value="1" type="submit">Crear</button>
      </form>

      <hr class="sep">

      <h2 style="margin:0 0 8px;">Editar roles</h2>
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">ID</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Nombre</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Descripción</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Guardar</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($roles as $r): ?>
              <tr>
                <td style="padding:10px;"><?php echo (int)$r["id_rol"]; ?></td>
                <td style="padding:10px;">
                  <form method="POST" style="display:grid; gap:8px; margin:0;">
                    <input type="hidden" name="id_rol" value="<?php echo (int)$r["id_rol"]; ?>">
                    <input class="input" name="nombre" value="<?php echo htmlspecialchars($r["nombre"]); ?>" required>
                </td>
                <td style="padding:10px;">
                    <input class="input" name="descripcion" value="<?php echo htmlspecialchars($r["descripcion"] ?? ""); ?>">
                </td>
                <td style="padding:10px;">
                    <button class="btn" name="guardar_rol" value="1" type="submit">Guardar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Permisos del rol -->
    <div class="card">
      <h2 style="margin:0 0 8px;">Permisos del rol</h2>

      <?php if ($idRolSel <= 0): ?>
        <div class="alert alert-err">No hay roles creados todavía.</div>
      <?php else: ?>

        <!-- Selector -->
        <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
          <select class="input" name="rol" style="max-width:320px;">
            <?php foreach ($roles as $r): ?>
              <option value="<?php echo (int)$r["id_rol"]; ?>" <?php echo ((int)$r["id_rol"] === $idRolSel) ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($r["nombre"]); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn" type="submit">Cargar</button>
        </form>

        <div class="alert" style="margin-top:10px;">
          Marca permisos y guarda. Esto controla qué páginas/acciones puede usar ese rol.
        </div>

        <form method="POST" style="margin-top:12px;">
          <input type="hidden" name="id_rol" value="<?php echo $idRolSel; ?>">

          <div style="display:grid; gap:10px;">
            <?php foreach ($permisos as $p): ?>
              <?php $idPerm = (int)$p["id_permiso"]; ?>
              <label class="alert" style="display:flex; gap:10px; align-items:flex-start; cursor:pointer;">
                <input type="checkbox" name="permisos[]" value="<?php echo $idPerm; ?>"
                  <?php echo isset($asignados[$idPerm]) ? "checked" : ""; ?>
                >
                <div>
                  <div><b><?php echo htmlspecialchars($p["codigo"]); ?></b></div>
                  <div class="small"><?php echo htmlspecialchars($p["descripcion"] ?? ""); ?></div>
                </div>
              </label>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn btn-primary" name="guardar_permisos" value="1" type="submit">Guardar permisos</button>
          </div>
        </form>

      <?php endif; ?>
    </div>

  </div>
</section>

<?php require "../pie.php"; ?>
