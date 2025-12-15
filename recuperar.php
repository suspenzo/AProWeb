<?php
require "encabezado.php";
require "conexion.php";
require "correo.php";

$mensajeError = "";
$mensajeOk = "";

if ($_POST) {
    $email = mysqli_real_escape_string($conexion, trim($_POST["email"]));

    if ($email === "") {
        $mensajeError = "Escribe tu correo.";
    } else {
       // Buscar usuario activo
$sql = "SELECT id_usuario, nick
        FROM usuario
        WHERE email='$email' AND estado='ACTIVO'
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$u = $res ? mysqli_fetch_assoc($res) : null;

if (!$u) {
    $mensajeError = "Ese correo no está registrado o la cuenta no está activa.";
} else {
            // ✅ aquí recién creas token y envías correo
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash("sha256", $token);

            mysqli_query($conexion, "
                INSERT INTO recuperacion_pass (id_usuario, token_hash, expira_en)
                VALUES ({$u['id_usuario']}, '$tokenHash', DATE_ADD(NOW(), INTERVAL 1 HOUR))
            ");

            $urlBase = "http://localhost/pagina";
            $link = $urlBase . "/restablecer.php?token=" . $token;

            $html = "
                <h2>Restablecer contraseña</h2>
                <p>Hola <b>{$u['nick']}</b>,</p>
                <p>Para cambiar tu contraseña haz clic aquí:</p>
                <p><a href='$link'>🔑 Restablecer contraseña</a></p>
                <p>Este enlace vence en 1 hora.</p>
            ";

            try {
                enviarCorreo($email, "Restablecer contraseña", $html);
                $mensajeOk = "✅ Te enviamos un enlace a tu correo para restablecer tu contraseña.";
            } catch (Exception $e) {
                $mensajeError = "No se pudo enviar el correo. Revisa configuración.";
            }
        }

    }
}
?>

<section class="hero">
  <div class="hero-grid">
    <div class="card">
      <h1 class="h1">Recuperar contraseña</h1>
      <p class="p">Te enviaremos un enlace a tu correo para restablecer tu contraseña.</p>

      <?php if ($mensajeError): ?>
        <div class="alert alert-err" style="margin-top:12px;">❌ <?php echo htmlspecialchars($mensajeError); ?></div>
      <?php endif; ?>

      <?php if ($mensajeOk): ?>
        <div class="alert alert-ok" style="margin-top:12px;"><?php echo htmlspecialchars($mensajeOk); ?></div>
      <?php endif; ?>

      <form class="form" method="POST" style="margin-top:12px;">
        <input class="input" name="email" type="email" placeholder="Correo electrónico" required>
        <button class="btn btn-primary" type="submit">Enviar enlace</button>
      </form>

      <hr class="sep">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="iniciar_sesion.php">Volver a iniciar sesión</a>
        <a class="btn" href="registrarse.php">Crear cuenta</a>
      </div>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Nota</h2>
      <div class="alert">
        Revisa también tu carpeta de <b>Spam</b>.
      </div>
    </div>
  </div>
</section>

<?php require "pie.php"; ?>
