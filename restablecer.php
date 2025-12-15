<?php
require "encabezado.php";
require "conexion.php";

$mensajeError = "";
$mensajeOk = "";

if (!isset($_GET["token"])) {
    $mensajeError = "Token no válido.";
} else {
    $token = $_GET["token"];
    $tokenHash = hash("sha256", $token);

    // Buscar token válido
    $sql = "
      SELECT id_recuperacion, id_usuario
      FROM recuperacion_pass
      WHERE token_hash='$tokenHash'
        AND usado_en IS NULL
        AND expira_en >= NOW()
      LIMIT 1
    ";
    $res = mysqli_query($conexion, $sql);
    $rec = $res ? mysqli_fetch_assoc($res) : null;

    if (!$rec) {
        $mensajeError = "❌ Enlace inválido o expirado.";
    } else {
        // Si envían el formulario
        if ($_POST) {
            $pass1 = $_POST["password1"] ?? "";
            $pass2 = $_POST["password2"] ?? "";

            if ($pass1 === "" || $pass2 === "") {
                $mensajeError = "Completa ambos campos.";
            } elseif ($pass1 !== $pass2) {
                $mensajeError = "Las contraseñas no coinciden.";
            } elseif (strlen($pass1) < 6) {
                $mensajeError = "La contraseña debe tener al menos 6 caracteres.";
            } else {
                // Guardar MD5
                $nuevoHash = md5($pass1);

                mysqli_query($conexion, "
                    UPDATE usuario
                    SET pass_hash='$nuevoHash'
                    WHERE id_usuario={$rec['id_usuario']}
                ");

                // Marcar token usado
                mysqli_query($conexion, "
                    UPDATE recuperacion_pass
                    SET usado_en=NOW()
                    WHERE id_recuperacion={$rec['id_recuperacion']}
                ");

                $mensajeOk = "✅ Contraseña actualizada. Ya puedes iniciar sesión.";
            }
        }
    }
}
?>

<section class="hero">
  <div class="hero-grid">
    <div class="card">
      <h1 class="h1">Restablecer contraseña</h1>
      <p class="p">Crea una nueva contraseña para tu cuenta.</p>

      <?php if ($mensajeError): ?>
        <div class="alert alert-err" style="margin-top:12px;"><?php echo htmlspecialchars($mensajeError); ?></div>
      <?php endif; ?>

      <?php if ($mensajeOk): ?>
        <div class="alert alert-ok" style="margin-top:12px;"><?php echo htmlspecialchars($mensajeOk); ?></div>
        <div style="margin-top:12px;">
          <a class="btn btn-primary" href="iniciar_sesion.php">Ir a iniciar sesión</a>
        </div>
      <?php endif; ?>

      <?php if (!$mensajeOk && !$mensajeError || ($mensajeError && isset($_GET["token"]) && strpos($mensajeError, "inválido") === false)): ?>
        <form class="form" method="POST" style="margin-top:12px;">
          <input class="input" type="password" name="password1" placeholder="Nueva contraseña" required>
          <input class="input" type="password" name="password2" placeholder="Repetir contraseña" required>
          <button class="btn btn-primary" type="submit">Guardar contraseña</button>
        </form>
      <?php endif; ?>

      <hr class="sep">
      <a class="btn" href="index.php">Volver al inicio</a>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Consejo</h2>
      <div class="alert">
        Usa una contraseña que recuerdes. (En un sistema real se recomienda <b>bcrypt</b>, pero aquí usamos MD5 por la consigna).
      </div>
    </div>
  </div>
</section>

<?php require "pie.php"; ?>
