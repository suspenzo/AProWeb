<?php
require "encabezado.php";
require "conexion.php";

$estaLogueado = isset($_SESSION["id_usuario"]);
$idUsuario = $estaLogueado ? (int)$_SESSION["id_usuario"] : 0;

// Traer cursos publicados
$cursos = [];
$res = mysqli_query($conexion, "SELECT id_curso, titulo, descripcion
                               FROM curso
                               WHERE publicado = 1
                               ORDER BY id_curso DESC");
while ($fila = mysqli_fetch_assoc($res)) {
    $cursos[] = $fila;
}
?>

<section class="hero">
  <div class="card">
    <h1 class="h1">Catálogo de cursos</h1>
    <p class="p">Explora los cursos disponibles. Para inscribirte debes iniciar sesión.</p>
  </div>

  <div style="height:14px;"></div>

  <div class="grid2">
    <?php if (count($cursos) == 0): ?>
      <div class="card">
        <div class="alert">Aún no hay cursos publicados.</div>
      </div>
    <?php else: ?>
      <?php foreach ($cursos as $c): ?>
        <div class="card">
          <h2 style="margin:0 0 6px;"><?php echo htmlspecialchars($c["titulo"]); ?></h2>
          <p class="p"><?php echo nl2br(htmlspecialchars(substr($c["descripcion"] ?? "", 0, 160))); ?>...</p>

          <hr class="sep">

          <?php if (!$estaLogueado): ?>
            <div class="alert">Inicia sesión para inscribirte.</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
              <a class="btn btn-primary" href="iniciar_sesion.php">Iniciar sesión</a>
              <a class="btn" href="registrarse.php">Registrarse</a>
            </div>
          <?php else: ?>
            <form method="POST" action="cursos/inscribir.php" style="margin:0;">
              <input type="hidden" name="id_curso" value="<?php echo (int)$c["id_curso"]; ?>">
              <button class="btn btn-primary" type="submit">Inscribirme</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php require "pie.php"; ?>
