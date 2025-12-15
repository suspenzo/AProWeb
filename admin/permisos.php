<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requierePermiso("PANEL_ADMIN");

$mensajeOk = "";
$mensajeError = "";

// Crear permiso
if (isset($_POST["crear_permiso"])) {
    $codigo = mysqli_real_escape_string($conexion, trim($_POST["codigo"]));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST["descripcion"]));

    if ($codigo === "") {
        $mensajeError = "El código es obligatorio.";
    } else {
        $ok = mysqli_query($conexion, "INSERT INTO permiso (codigo, descripcion) VALUES ('$codigo', '$descripcion')");
        if ($ok) $mensajeOk = "Permiso creado.";
        else $mensajeError = "No se pudo crear (¿código duplicado?).";
    }
}

// Editar permiso
if (isset($_POST["guardar_permiso"])) {
    $idPermiso = (int)$_POST["id_permiso"];
    $codigo = mysqli_real_escape_string($conexion, trim($_POST["codigo"]));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST["descripcion"]));

    if ($codigo === "") {
        $mensajeError = "El código es obligatorio.";
    } else {
        $ok = mysqli_query($conexion, "
          UPDATE permiso
          SET codigo='$codigo', descripcion='$descripcion'
          WHERE id_permiso=$idPermiso
        ");
        if ($ok) $mensajeOk = "Permiso actualizado.";
        else $mensajeError = "No se pudo actualizar (¿código duplicado?).";
    }
}

$res = mysqli_query($conexion, "SELECT id_permiso, codigo, descripcion FROM permiso ORDER BY codigo");

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Administración · Permisos</h1>
    <p class="p">Crea y edita permisos (código único). Ej: CURSO_GESTIONAR.</p>

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
      <a class="btn btn-primary" href="permisos.php">Permisos</a>
      <a class="btn" href="cursos.php">Cursos</a>
      <a class="btn" href="pagos.php">Pagos</a>
    </div>
  </div>

  <div style="height:14px;"></div>

  <div class="hero-grid">
    <div class="card">
      <h2 style="margin:0 0 8px;">Crear permiso</h2>
      <form class="form" method="POST">
        <input class="input" name="codigo" placeholder="Código (Ej: PAGO_GESTIONAR)" required>
        <input class="input" name="descripcion" placeholder="Descripción (opcional)">
        <button class="btn btn-primary" name="crear_permiso" value="1" type="submit">Crear</button>
      </form>

      <div class="alert" style="margin-top:10px;">
        Recomendación: usa códigos en MAYÚSCULAS y con guion bajo. Ej: <b>USUARIO_GESTIONAR</b>
      </div>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Permisos existentes</h2>

      <div style="overflow:auto; margin-top:10px;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">ID</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Código</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Descripción</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Guardar</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($p = mysqli_fetch_assoc($res)): ?>
              <tr>
                <td style="padding:10px;"><?php echo (int)$p["id_permiso"]; ?></td>
                <td style="padding:10px;">
                  <form method="POST" style="display:grid; gap:8px; margin:0;">
                    <input type="hidden" name="id_permiso" value="<?php echo (int)$p["id_permiso"]; ?>">
                    <input class="input" name="codigo" value="<?php echo htmlspecialchars($p["codigo"]); ?>" required>
                </td>
                <td style="padding:10px;">
                    <input class="input" name="descripcion" value="<?php echo htmlspecialchars($p["descripcion"] ?? ""); ?>">
                </td>
                <td style="padding:10px;">
                    <button class="btn" name="guardar_permiso" value="1" type="submit">Guardar</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="alert" style="margin-top:10px;">
        Nota: los permisos se asignan a roles en <b>Roles</b> (checkbox).
      </div>
    </div>
  </div>
</section>

<?php require "../pie.php"; ?>
