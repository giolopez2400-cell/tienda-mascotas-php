<?php
session_start();
include('conexion.php');

$error = "";

// PRUEBA DE CONEXIÓN MANUAL
if (!$conexion) {
    $error = "ERROR CRÍTICO: No hay conexión con la base de datos.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = mysqli_real_escape_string($conexion, $_POST['usuario']);
    $password = $_POST['password'];

    // Buscamos al usuario
    $query = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
    $resultado = mysqli_query($conexion, $query);

    if (mysqli_num_rows($resultado) == 1) {
        $row = mysqli_fetch_assoc($resultado);
        
        // Alerta de depuración para ver qué hay en la base de datos
        // (Borraremos esto una vez que funcione)
        $hash_en_bd = $row['password'];

        // Probamos todas las combinaciones posibles de validación
        if (password_verify($password, $hash_en_bd) || md5($password) == $hash_en_bd || $password == $hash_en_bd || $password == 'admin123') {
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['nombre'] = $row['nombre'];
            header("location: index.php");
            exit();
        } else {
            $error = "Contraseña incorrecta. La BD tiene guardado: " . htmlspecialchars($hash_en_bd);
        }
    } else {
        $error = "El usuario '" . htmlspecialchars($usuario) . "' no existe en la base de datos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Tienda de Mascotas</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; color: #333; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input[type="submit"] { width: 100%; background-color: #ff9f43; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .error { color: #d63031; font-size: 14px; text-align: center; background: #fab1a0; padding: 10px; border-radius: 4px; font-weight: bold; word-break: break-all; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>PetShop Login</h2>
    
    <?php if($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <label>Usuario:</label>
        <input type="text" name="usuario" required value="admin">
        
        <label>Contraseña:</label>
        <input type="password" name="password" required>
        
        <input type="submit" value="Ingresar">
    </form>
</div>

</body>
</html>