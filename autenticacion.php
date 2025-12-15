<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "conexion.php";

/**
 * Carga los permisos del rol del usuario a la sesión.
 */
function cargarPermisos($idRol) {
    global $conexion;

    $_SESSION["permisos"] = [];

    $idRol = (int)$idRol;

    $sql = "
        SELECT p.codigo
        FROM rol_permiso rp
        INNER JOIN permiso p ON rp.id_permiso = p.id_permiso
        WHERE rp.id_rol = $idRol
    ";

    $res = mysqli_query($conexion, $sql);
    while ($fila = mysqli_fetch_assoc($res)) {
        $_SESSION["permisos"][] = $fila["codigo"];
    }
}

/**
 * Devuelve true si el usuario tiene un permiso.
 */
function tienePermiso($codigoPermiso) {
    if (!isset($_SESSION["permisos"])) return false;
    return in_array($codigoPermiso, $_SESSION["permisos"]);
}

/**
 * Protege páginas que requieren sesión.
 */
function requiereSesion() {
    if (!isset($_SESSION["id_usuario"])) {
        header("Location: /pagina/iniciar_sesion.php");
        exit;
    }
}

/**
 * Protege páginas que requieren un permiso específico.
 */
function requierePermiso($codigoPermiso) {
    requiereSesion();
    if (!tienePermiso($codigoPermiso)) {
        header("Location: /pagina/index.php");
        exit;
    }
}
