<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requierePermiso("PANEL_ADMIN");

$mensajeOk = "";
$mensajeError = "";

/* =========================
   ACCIONES (GET)
========================= */
if (isset($_GET["accion"]) && isset($_GET["id"])) {
    $id = (int)$_GET["id"];
    $accion = $_GET["accion"];

    if ($accion === "publicar") {
        mysqli_query($conexion, "UPDATE curso SET publicado=1 WHERE id_curso=$id");
        $mensajeOk = "Curso publicado.";
    }
    if ($accion === "ocultar") {
        mysqli_query($conexion, "UPDATE curso SET publicado=0 WHERE id_curso=$id");
        $mensajeOk = "Curso ocultado.";
    }
}

/* =========================
   CREAR CURSO (POST)
========================= */
if (isset($_POST["crear_curso"])) {
    $titulo = mysqli_real_escape_string($conexion, trim($_POST["titulo"]));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST["descripcion"]));
    $precio = (float)($_POST["precio"] ?? 0);
    $publicado = isset($_POST["publicado"]) ? 1 : 0;

    if ($titulo === "") {
        $mensajeError = "El título es obligatorio.";
    } else {
        $ok = mysqli_query($conexion, "
            INSERT INTO curso (titulo, descripcion, precio, publicado)
            VALUES ('$titulo', '$descripcion', $precio, $publicado)
        ");
        if ($ok) $mensajeOk = "Curso creado.";
        else $mensajeError = "No se pudo crear el curso.";
    }
}

/* =========================
   EDITAR CURSO (POST)
========================= */
if (isset($_POST["guardar_curso"])) {
    $id = (int)$_POST["id_curso"];
    $titulo = mysqli_real_escape_string($conexion, trim($_POST["titulo"]));
    $descripcion = mysqli_real_escape_string($conexion, trim($_POST["descripcion"]));
    $precio = (float)($_POST["precio"] ?? 0);
    $publicado = isset($_POST["publicado"]) ? 1 : 0;

    if ($titulo === "") {
        $mensajeError = "El título es obligatorio.";
    } else {
        $ok = mysqli_query($conexion, "
            UPDATE curso
            SET titulo='$titulo', descripcion='$descripcion', precio=$precio, publicado=$publicado
            WHERE id_curso=$id
        ");
        if ($ok) $mensajeOk = "Curso actualizado.";
        else $mensajeError = "No se pudo actualizar.";
    }
}

/* =========================
   LISTA CURSOS (con inscritos)
========================= */
$sql = "
  SELECT
    c.id_curso, c.titulo, c.precio, c.publicado, c.creado_en,
    (SELECT COUNT(*) FROM usuario_curso uc WHERE uc.id_curso=c.id_curso AND uc.estado='ACTIVO') AS inscritos_activos,
    (SELECT COUNT(*) FROM usuario_curso uc WHERE uc.id_curso=c.id_curso AND uc.estado_pago='PENDIENTE') AS pagos_pendientes
  FROM curso c
  ORDER BY c.id_curso DESC
";
$res = mysqli_query($conexion, $sql);

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Administración · Cursos</h1>
    <p class="p">Crea, edita, publica y gestiona cursos (incluye precio y estado).</p>

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
      <a class="btn btn-primary" href="cursos.php">Cursos</a>
      <a class="btn" href="pagos.php">Pagos</a>
    </div>
  </div>

  <div style="height:14px;"></div>

  <div class="hero-grid">
    <!-- Crear -->
    <div class="card">
      <h2 style="margin:0 0 8px;">Crear curso</h2>
      <form class="form" method="POST">
        <input class="input" name="titulo" placeholder="Título del curso" required>
        <textarea class="input" name="descripcion" placeholder="Descripción (puede ser corta)" rows="5"></textarea>
        <input class="input" name="precio" type="number" step="0.01" min="0" value="0.00">
        <label class="alert" style="display:flex; gap:10px; align-items:center; cursor:pointer;">
          <input type="checkbox" name="publicado" value="1" checked>
          <div>Publicado (visible en el catálogo)</div>
        </label>
        <button class="btn btn-primary" name="crear_curso" value="1" type="submit">Crear</button>
      </form>
    </div>

    <!-- Lista -->
    <div class="card">
      <h2 style="margin:0 0 8px;">Cursos existentes</h2>

      <div style="overflow:auto; margin-top:10px;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">ID</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Título</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Precio</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Inscritos</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Pendientes</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Estado</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid rgba(255,255,255,.08);">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($c = mysqli_fetch_assoc($res)): ?>
              <tr>
                <td style="padding:10px;"><?php echo (int)$c["id_curso"]; ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars($c["titulo"]); ?></td>
                <td style="padding:10px;"><?php echo number_format((float)$c["precio"], 2); ?></td>
                <td style="padding:10px;"><?php echo (int)$c["inscritos_activos"]; ?></td>
                <td style="padding:10px;"><?php echo (int)$c["pagos_pendientes"]; ?></td>
                <td style="padding:10px;">
                  <?php echo ((int)$c["publicado"] === 1) ? "PUBLICADO" : "OCULTO"; ?>
                </td>
                <td style="padding:10px; display:flex; gap:8px; flex-wrap:wrap;">
                  <a class="btn" href="curso_editar.php?id=<?php echo (int)$c["id_curso"]; ?>">Editar</a>

                  <?php if ((int)$c["publicado"] === 1): ?>
                    <a class="btn btn-danger" href="cursos.php?accion=ocultar&id=<?php echo (int)$c["id_curso"]; ?>">Ocultar</a>
                  <?php else: ?>
                    <a class="btn btn-primary" href="cursos.php?accion=publicar&id=<?php echo (int)$c["id_curso"]; ?>">Publicar</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="small" style="margin-top:10px;">
        Nota: “Pendientes” cuenta inscripciones con estado_pago=PENDIENTE (para que admin confirme).
      </div>
    </div>
  </div>
</section>

<?php require "../pie.php"; ?>
