<?php
require_once 'config.php';

// Limpiar todas las variables de sesión
session_unset();
session_destroy();

// Redirigir al login
header("Location: login.php");
exit();
?>
