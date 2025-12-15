<?php
require "encabezado.php";       // incluye autenticacion.php y sesión
require "conexion.php";         // $conexion

$mensajeError = "";
$mensajeOk = "";

if ($_POST) {
    $email = mysqli_real_escape_string($conexion, $_POST["email"]);
    $pass  = md5($_POST["password"]);

    // Buscamos usuario por email
    $sql = "SELECT id_usuario, id_rol, pass_hash, estado
            FROM usuario
            WHERE email='$email'
            LIMIT 1";

    $res = mysqli_query($conexion, $sql);
    $usuario = $res ? mysqli_fetch_assoc($res) : null;

    if (!$usuario) {
        $mensajeError = "Correo o contraseña incorrectos.";
    } else {
        // Validar contraseña
        if ($usuario["pass_hash"] !== $pass) {
            $mensajeError = "Correo o contraseña incorrectos.";
        } else {
            // Validar estado
            if ($usuario["estado"] === "PENDIENTE") {
                $mensajeError = "Tu cuenta aún no está verificada. Revisa tu correo y verifica tu cuenta.";
            } elseif ($usuario["estado"] === "DESACTIVADO") {
                $mensajeError = "Tu cuenta está desactivada. Contacta al administrador.";
            } else {
                // OK: crear sesión
                $_SESSION["id_usuario"] = (int)$usuario["id_usuario"];
                $_SESSION["id_rol"]     = (int)$usuario["id_rol"];

                // Cargar permisos del rol
                cargarPermisos($_SESSION["id_rol"]);

                header("Location: index.php");
                exit;
            }
        }
    }
}
?>

<section class="hero">
  <div class="hero-grid">
    <div class="card">
      <h1 class="h1">Iniciar sesión</h1>
      <p class="p">Accede a tu cuenta para inscribirte a cursos.</p>

      <?php if ($mensajeError): ?>
        <div class="alert alert-err" style="margin-top:12px;">❌ <?php echo htmlspecialchars($mensajeError); ?></div>
      <?php endif; ?>

      <form class="form" method="POST" style="margin-top:12px;">
        <input class="input" name="email" type="email" placeholder="Correo electrónico" required>
        <input class="input" name="password" type="password" placeholder="Contraseña" required>
        <button class="btn btn-primary" type="submit">Entrar</button>
      </form>

      <hr class="sep">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="recuperar.php">Olvidé mi contraseña</a>
        <a class="btn" href="registrarse.php">Crear cuenta</a>
      </div>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Notas</h2>
      <div class="alert">
        Si tu cuenta está en <b>PENDIENTE</b>, primero debes <b>verificar tu correo</b>.
      </div>
      <div class="alert" style="margin-top:10px;">
        Si no recibes el correo, revisa <b>Spam</b> o solicita el reenvío (lo haremos en el siguiente paso).
      </div>
    </div>
  </div>
</section>

<?php require "pie.php"; ?>
