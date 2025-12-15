<?php
require "encabezado.php";
require "conexion.php";
require "correo.php";

$mensajeError = "";
$mensajeOk = "";

function urlBaseActual() {
    $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");
    $proto = $https ? "https://" : "http://";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";

    // Ajusta si tu proyecto vive en /pagina
    $basePath = "/pagina";

    return $proto . $host . $basePath;
}

if ($_POST) {
    $nick  = mysqli_real_escape_string($conexion, trim($_POST["nick"] ?? ""));
    $email = mysqli_real_escape_string($conexion, trim($_POST["email"] ?? ""));
    $passwordPlano = $_POST["password"] ?? "";
    $pass  = md5($passwordPlano);

    // Validaciones simples
    if ($nick === "" || $email === "" || $passwordPlano === "") {
        $mensajeError = "Completa todos los campos.";
    } else {
        // Verificar email repetido
        $existe = mysqli_query($conexion, "SELECT id_usuario FROM usuario WHERE email='$email' LIMIT 1");
        if ($existe && mysqli_fetch_assoc($existe)) {
            $mensajeError = "Ese correo ya está registrado. Intenta iniciar sesión.";
        } else {
            // Rol USUARIO
            $resRol = mysqli_query($conexion, "SELECT id_rol FROM rol WHERE nombre='USUARIO' LIMIT 1");
            $rol = $resRol ? mysqli_fetch_assoc($resRol) : null;

            // ✅ Plan SIN_PLAN por defecto
            $resPlan = mysqli_query($conexion, "SELECT id_plan FROM plan WHERE codigo='SIN_PLAN' LIMIT 1");
            $plan = $resPlan ? mysqli_fetch_assoc($resPlan) : null;

            if (!$rol || !$plan) {
                $mensajeError = "Faltan datos base (rol/plan). Revisa la BD (rol USUARIO y plan SIN_PLAN).";
            } else {
                // Crear usuario en PENDIENTE
                $sql = "INSERT INTO usuario (id_rol, id_plan, nick, email, pass_hash, estado)
                        VALUES ({$rol['id_rol']}, {$plan['id_plan']}, '$nick', '$email', '$pass', 'PENDIENTE')";
                $ok = mysqli_query($conexion, $sql);

                if (!$ok) {
                    $mensajeError = "No se pudo registrar. Intenta nuevamente.";
                } else {
                    $idUsuario = mysqli_insert_id($conexion);

                    // Crear token de verificación
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash("sha256", $token);

                    mysqli_query($conexion, "
                        INSERT INTO verificacion_email (id_usuario, token_hash, expira_en)
                        VALUES ($idUsuario, '$tokenHash', DATE_ADD(NOW(), INTERVAL 24 HOUR))
                    ");

                    // ✅ URL base automática (local/hosting)
                    $urlBase = urlBaseActual();
                    $link = $urlBase . "/verificar.php?token=" . $token;

                    $html = "
                        <h2>Verificación de cuenta</h2>
                        <p>Hola <b>$nick</b>, gracias por registrarte.</p>
                        <p>Para activar tu cuenta haz clic aquí:</p>
                        <p><a href='$link'>✅ Verificar mi cuenta</a></p>
                        <p>Este enlace vence en 24 horas.</p>
                    ";

                    // Enviar correo
                    try {
                        enviarCorreo($email, "Verifica tu cuenta", $html);
                        $mensajeOk = "✅ Registro exitoso. Te enviamos un correo para verificar tu cuenta.";
                    } catch (Exception $e) {
                        $mensajeOk = "✅ Registro exitoso, pero no se pudo enviar el correo. Revisa configuración.";
                    }
                }
            }
        }
    }
}
?>

<section class="hero">
  <div class="hero-grid">
    <div class="card">
      <h1 class="h1">Crear cuenta</h1>
      <p class="p">Regístrate y verifica tu correo para activar tu cuenta.</p>

      <?php if ($mensajeError): ?>
        <div class="alert alert-err" style="margin-top:12px;">❌ <?php echo htmlspecialchars($mensajeError); ?></div>
      <?php endif; ?>

      <?php if ($mensajeOk): ?>
        <div class="alert alert-ok" style="margin-top:12px;"><?php echo htmlspecialchars($mensajeOk); ?></div>
      <?php endif; ?>

      <form class="form" method="POST" style="margin-top:12px;">
        <input class="input" name="nick" placeholder="Nombre de usuario" required>
        <input class="input" name="email" type="email" placeholder="Correo electrónico" required>
        <input class="input" name="password" type="password" placeholder="Contraseña" required>
        <button class="btn btn-primary" type="submit">Registrarme</button>
      </form>

      <hr class="sep">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="iniciar_sesion.php">Ya tengo cuenta</a>
        <a class="btn" href="recuperar.php">Recuperar contraseña</a>
      </div>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Tu plan inicial</h2>
      <div class="alert">
        Al registrarte empiezas en <b>SIN_PLAN</b>. Para inscribirte a cursos de pago, necesitas que el admin active un plan.
      </div>
      <div class="alert" style="margin-top:10px;">
        Si no te llega el correo, revisa <b>Spam</b>.
      </div>
    </div>
  </div>
</section>

<?php require "pie.php"; ?>
