<?php
require_once "../autenticacion.php";
require_once "../conexion.php";
requierePermiso("PANEL_ADMIN");

$idCurso = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($idCurso <= 0) { header("Location: cursos.php"); exit; }

$mensajeOk = "";
$mensajeError = "";

// Traer curso
$resC = mysqli_query($conexion, "SELECT * FROM curso WHERE id_curso=$idCurso LIMIT 1");
$curso = $resC ? mysqli_fetch_assoc($resC) : null;
if (!$curso) { header("Location: cursos.php"); exit; }

// Guardar
if ($_POST) {
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
          WHERE id_curso=$idCurso
        ");
        if ($ok) {
            $mensajeOk = "Curso actualizado.";
            $resC = mysqli_query($conexion, "SELECT * FROM curso WHERE id_curso=$idCurso LIMIT 1");
            $curso = $resC ? mysqli_fetch_assoc($resC) : $curso;
        } else {
            $mensajeError = "No se pudo guardar.";
        }
    }
}

require "../encabezado.php";
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Editar curso</h1>
    <p class="p">Modifica título, descripción, precio y publicación.</p>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
      <a class="btn" href="cursos.php">← Volver a cursos</a>
      <a class="btn" href="pagos.php">Ver pagos</a>
    </div>

    <?php if ($mensajeOk): ?>
      <div class="alert alert-ok" style="margin-top:12px;">✅ <?php echo htmlspecialchars($mensajeOk); ?></div>
    <?php endif; ?>
    <?php if ($mensajeError): ?>
      <div class="alert alert-err" style="margin-top:12px;">❌ <?php echo htmlspecialchars($mensajeError); ?></div>
    <?php endif; ?>

    <hr class="sep">

    <form class="form" method="POST">
      <input class="input" name="titulo" value="<?php echo htmlspecialchars($curso["titulo"]); ?>" required>
      <textarea class="input" name="descripcion" rows="7"><?php echo htmlspecialchars($curso["descripcion"] ?? ""); ?></textarea>
      <input class="input" name="precio" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($curso["precio"]); ?>">

      <label class="alert" style="display:flex; gap:10px; align-items:center; cursor:pointer;">
        <input type="checkbox" name="publicado" value="1" <?php echo ((int)$curso["publicado"]===1) ? "checked" : ""; ?>>
        <div>Publicado (visible en el catálogo)</div>
      </label>

      <button class="btn btn-primary" type="submit">Guardar cambios</button>
    </form>
  </div>
</section>

<?php require "../pie.php"; ?>
