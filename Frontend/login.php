<?php

require_once 'config.php';
forceLightTheme();

$error = '';

// Si ya tiene sesión, redirigir
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        
        $conn = getDBConnection();
        
        // Buscar cuenta por email
        $stmt = $conn->prepare("
            SELECT c.id AS cuenta_id, c.password, c.email,
                   u.id AS usuario_id, u.nickname, u.imagen, u.rol_id, u.estado_id
            FROM cuentas c
            INNER JOIN usuarios u ON u.cuenta_id = c.id
            WHERE c.email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && password_verify($password, $user['password'])) {
            // Verificar estado del usuario
            if ($user['estado_id'] == 4) { // bloqueado
                $error = 'Tu cuenta ha sido bloqueada. Contacta al administrador.';
            } elseif ($user['estado_id'] == 3) { // eliminado
                $error = 'Esta cuenta ya no existe.';
            } else {
                // Login exitoso - Guardar datos en sesión
                $_SESSION['usuario_id'] = $user['usuario_id'];
                $_SESSION['usuario_nombre'] = $user['nickname'];
                $_SESSION['usuario_rol'] = $user['rol_id'];
                $_SESSION['usuario_imagen'] = $user['imagen'];
                $_SESSION['cuenta_id'] = $user['cuenta_id'];
                
                // Actualizar fecha_reciente
                $stmtUpdate = $conn->prepare("UPDATE usuarios SET fecha_reciente = NOW() WHERE id = ?");
                $stmtUpdate->bind_param("i", $user['usuario_id']);
                $stmtUpdate->execute();
                $stmtUpdate->close();
                
                $conn->close();
                
                header("Location: index.php");
                exit();
            }
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
        
        $conn->close();
        
    } else {
        $error = 'Por favor completa todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<script>
    const savedTheme = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", savedTheme);
</script>

<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">
        Iniciar Sesión
            </h1>
            <?php if (isset($_GET['session_expired'])): ?>
                <div class="error-message">Tu sesión ha expirado. Por favor inicia sesión nuevamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="success-message">¡Registro completado! Ahora puedes iniciar sesión.</div>
            <?php endif; ?>
            <?php if (isset($_GET['password_changed'])): ?>
                <div class="success-message">Contraseña cambiada correctamente. Ya puedes iniciar sesión.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
                <p class="auth-link"><a href="forgot_password.php">¿Olvidaste tu contraseña?</a></p>
            </form>
            <p class="auth-link">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
            <p class="auth-link"><small>Debes tener un correo @soy.sena.edu.co para registrarte</small></p>
        </div>
    </div>
</body>
</html>
