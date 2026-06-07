<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("location: login.php");
    exit();
}
include('conexion.php');

$mensaje_compra = "";

// --- LÓGICA DEL CARRITO DE COMPRAS ---
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = array();
}

// Vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = array();
    header("location: index.php");
    exit();
}

// Añadir producto al carrito
if (isset($_POST['agregar_id'])) {
    $id_producto = intval($_POST['agregar_id']);
    if (isset($_SESSION['carrito'][$id_producto])) {
        $_SESSION['carrito'][$id_producto]++;
    } else {
        $_SESSION['carrito'][$id_producto] = 1;
    }
    header("location: index.php");
    exit();
}

// --- LÓGICA: GUARDAR COMPRA EN LA BASE DE DATOS ---
if (isset($_POST['confirmar_compra']) && !empty($_SESSION['carrito'])) {
    $usuario_logueado = $_SESSION['usuario'];
    
    // 1. Obtener el ID numérico del usuario actual
    $user_query = "SELECT id FROM usuarios WHERE usuario = '$usuario_logueado'";
    $user_res = mysqli_query($conexion, $user_query);
    $user_data = mysqli_fetch_assoc($user_res);
    $usuario_id = $user_data['id'];

    // 2. Calcular el total de la compra antes de insertar
    $total_compra = 0;
    $items_a_guardar = array();
    
    foreach ($_SESSION['carrito'] as $id => $cantidad) {
        $prod_query = "SELECT precio FROM productos WHERE id = $id";
        $prod_res = mysqli_query($conexion, $prod_query);
        if ($prod = mysqli_fetch_assoc($prod_res)) {
            $subtotal = $prod['precio'] * $cantidad;
            $total_compra += $subtotal;
            $items_a_guardar[] = array(
                'producto_id' => $id,
                'cantidad' => $cantidad,
                'precio' => $prod['precio']
            );
        }
    }

    // 3. Insertar el Pedido Principal (Cabecera)
    $insert_pedido = "INSERT INTO pedidos (usuario_id, total) VALUES ($usuario_id, $total_compra)";
    
    if (mysqli_query($conexion, $insert_pedido)) {
        $pedido_id = mysqli_insert_id($conexion);

        // 4. Insertar cada producto en la tabla detalle_pedidos
        foreach ($items_a_guardar as $item) {
            $insert_detalle = "INSERT INTO detalle_pedidos (pedido_id, producto_id, candy_id, cantidad, precio_unitario) 
                               VALUES ($pedido_id, {$item['producto_id']}, {$item['cantidad']}, {$item['precio']})";
            // Nota: Corregido nombre de campos estándar según la tabla original
            $insert_detalle = "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) 
                               VALUES ($pedido_id, {$item['producto_id']}, {$item['cantidad']}, {$item['precio']})";
            mysqli_query($conexion, $insert_detalle);
        }

        $_SESSION['carrito'] = array();
        $mensaje_compra = "<p class='alerta-exito'>✅ ¡Compra guardada con éxito en la Base de Datos! (Pedido #$pedido_id)</p>";
    } else {
        $mensaje_compra = "<p class='alerta-error'>❌ Error al procesar la compra en la base de datos.</p>";
    }
}

// Traer productos para la tienda
$query = "SELECT * FROM productos";
$productos = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Tienda de Mascotas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; }
        header { background-color: #ff9f43; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        header .botones { display: flex; gap: 10px; }
        header a { color: white; text-decoration: none; background: #ee5253; padding: 8px 15px; border-radius: 4px; font-weight: bold; }
        header a.btn-vaciar { background: #5758bb; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: flex; gap: 20px; flex-wrap: wrap; }
        .seccion-productos { flex: 3; min-width: 300px; }
        .grid-productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        
        /* --- ESTILO DE LAS TARJETAS CORREGIDO --- */
        .producto-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        
        /* ESTILO NUEVO PARA LAS IMÁGENES */
        .producto-card img {
            width: 100%;
            height: 150px;
            object-fit: contain; /* Encaja la imagen sin recortarla ni deformarla */
            margin-bottom: 15px;
            background-color: #fcfcfc;
            border-radius: 4px;
        }
        
        .producto-card h3 { color: #333; margin: 10px 0; font-size: 18px; }
        .producto-card p { color: #666; font-size: 14px; height: 40px; margin-bottom: 15px; }
        .precio { color: #ff9f43; font-weight: bold; font-size: 20px; margin: 10px 0; }
        .btn-comprar { background-color: #10ac84; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 15px; font-weight: bold; }
        .btn-comprar:hover { background-color: #1dd1a1; }
        
        .seccion-carrito { flex: 1; min-width: 280px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); height: fit-content; }
        .seccion-carrito h3 { margin-top: 0; border-bottom: 2px solid #ff9f43; padding-bottom: 10px; color: #333; }
        .item-carrito { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px dashed #eee; }
        .total-carrito { font-size: 18px; font-weight: bold; text-align: right; margin-top: 15px; color: #10ac84; margin-bottom: 15px; }
        
        .btn-pagar { background-color: #ff9f43; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .btn-pagar:hover { background-color: #ffb167; }
        
        .alerta-exito { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; font-weight: bold; text-align: center; width: 100%; box-sizing: border-box;}
    </style>
</head>
<body>

<header>
    <h1>🐾 PetShop - ¡Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h1>
    <div class="botones">
        <a href="index.php?vaciar=true" class="btn-vaciar">Vaciar Carrito</a>
        <a href="logout.php">Cerrar Sesión</a>
    </div>
</header>

<div class="container">
    
    <?php echo $mensaje_compra; ?>

    <div class="seccion-productos">
        <h2>Nuestros Productos</h2>
        <div class="grid-productos">
            <?php while($row = mysqli_fetch_assoc($productos)): ?>
                <div class="producto-card">
                    
                    <img src="imagenes/<?php echo $row['imagen']; ?>" alt="<?php echo htmlspecialchars($row['nombre']); ?>">
                    
                    <h3><?php echo htmlspecialchars($row['nombre']); ?></h3>
                    <p><?php echo htmlspecialchars($row['descripcion']); ?></p>
                    <div class="precio">$<?php echo number_format($row['precio'], 2); ?></div>
                    <form action="" method="POST">
                        <input type="hidden" name="agregar_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn-comprar">Añadir al carrito</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="seccion-carrito">
        <h3>🛒 Tu Carrito</h3>
        <?php
        $total = 0;
        if (!empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $id => $cantidad) {
                $query_prod = "SELECT nombre, precio FROM productos WHERE id = $id";
                $res_prod = mysqli_query($conexion, $query_prod);
                if ($prod = mysqli_fetch_assoc($res_prod)) {
                    $subtotal = $prod['precio'] * $cantidad;
                    $total += $subtotal;
                    echo "<div class='item-carrito'>";
                    echo "<span><strong>x{$cantidad}</strong> {$prod['nombre']}</span>";
                    echo "<span>$" . number_format($subtotal, 2) . "</span>";
                    echo "</div>";
                }
            }
            echo "<div class='total-carrito'>Total: $" . number_format($total, 2) . "</div>";
            
            echo '<form action="" method="POST">';
            echo '  <input type="hidden" name="confirmar_compra" value="1">';
            echo '  <button type="submit" class="btn-pagar">💳 Confirmar Compra</button>';
            echo '</form>';
            
        } else {
            echo "<p style='color: #999; text-align: center;'>El carrito está vacío.</p>";
        }
        ?>
    </div>

</div>

</body>
</html>