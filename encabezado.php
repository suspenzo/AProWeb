<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/autenticacion.php";

$estaLogueado = isset($_SESSION["id_usuario"]);

// ✅ ALERT GLOBAL (para avisos como "se cerró la sesión más antigua")
if (!empty($_SESSION["alerta_login"])) {
    $msg = addslashes($_SESSION["alerta_login"]);
    unset($_SESSION["alerta_login"]);
    echo "<script>alert('{$msg}');</script>";
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Plataforma de Cursos</title>
  <link rel="stylesheet" href="/pagina/estilos.css">
</head>
<body>

<header class="navbar">
  <div class="container nav-inner">
    <a class="brand" href="/pagina/index.php">
      <span class="brand-badge"></span>
      <span>Plataforma Cursos</span>
    </a>

    <nav class="nav-links">
      <a class="pill" href="/pagina/index.php">Inicio</a>
      <!-- <a class="pill" href="pagina/cursos/cursos.php">Cursos</a> -->

      <?php if ($estaLogueado): ?>
        <a class="pill" href="/pagina/cursos/mis_cursos.php">Mis cursos</a>
        <a class="pill" href="/pagina/cuenta/mi_plan.php">Mi suscripción</a>
        <a class="pill" href="/pagina/cuenta/mis_sesiones.php">Mis sesiones</a>


      <?php if (tienePermiso("PANEL_ADMIN")): ?>
        <a class="pill" href="/pagina/admin/usuarios.php">Administración</a>
        <!-- <div class="dropdown">
          <a class="pill dropdown-btn" href="/pagina/admin/usuarios.php">Administración ▾</a>
          <div class="dropdown-menu">
            <a href="pagina/admin/usuarios.php">Usuarios</a>
            <a href="pagina/admin/roles.php">Roles</a>
            <a href="pagina/admin/permisos.php">Permisos</a>
            <a href="pagina/admin/rol_permiso.php">Permisos por rol</a>
            <a href="pagina/admin/cursos.php">Cursos</a>
            <a href="pagina/admin/pagos.php">Pagos</a>
          </div>
        </div> -->
      <?php endif; ?>

        <a class="btn btn-danger" href="/pagina/cerrar_sesion.php">Salir</a>
      <?php else: ?>
        <a class="btn" href="/pagina/iniciar_sesion.php">Iniciar sesión</a>
        <a class="btn btn-primary" href="registrarse.php">Registrarse</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">
