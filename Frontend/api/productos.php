<?php
/**
 * API de Productos con Paginación
 * Endpoint para cargar productos de forma paginada (infinite scroll)
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Parámetros de paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(24, max(6, (int)$_GET['limit'])) : 12; // Entre 6 y 24, default 12
$offset = ($page - 1) * $limit;

// Filtros
$categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$busqueda = isset($_GET['busqueda']) ? sanitize($_GET['busqueda']) : '';
$orden = isset($_GET['orden']) ? sanitize($_GET['orden']) : 'newest';

try {
    $conn = getDBConnection();
    
    // Query base para contar total
    $countQuery = "SELECT COUNT(DISTINCT p.id) as total
        FROM productos p
        INNER JOIN usuarios u ON p.vendedor_id = u.id
        INNER JOIN subcategorias sc ON p.subcategoria_id = sc.id
        INNER JOIN categorias c ON sc.categoria_id = c.id
        WHERE p.estado_id = 1 AND u.estado_id = 1";
    
    // Query de productos
    $query = "SELECT 
        p.id,
        p.nombre,
        p.descripcion,
        p.precio,
        p.disponibles,
        p.fecha_registro,
        u.id AS vendedor_id,
        u.nickname AS vendedor_nombre,
        u.imagen AS vendedor_avatar,
        sc.nombre AS subcategoria_nombre, 
        c.id AS categoria_id,
        c.nombre AS categoria_nombre, 
        i.nombre AS integridad_nombre,
        f.imagen AS producto_imagen
    FROM productos p
    INNER JOIN usuarios u ON p.vendedor_id = u.id
    INNER JOIN subcategorias sc ON p.subcategoria_id = sc.id
    INNER JOIN categorias c ON sc.categoria_id = c.id
    INNER JOIN integridad i ON p.integridad_id = i.id
    LEFT JOIN fotos f ON f.producto_id = p.id
    WHERE p.estado_id = 1 AND u.estado_id = 1";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if ($categoria_id > 0) {
        $query .= " AND c.id = ?";
        $countQuery .= " AND c.id = ?";
        $params[] = $categoria_id;
        $types .= 'i';
    }
    
    if (!empty($busqueda)) {
        $query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
        $countQuery .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
        $search_term = "%$busqueda%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }
    
    // Obtener total de productos
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $totalProductos = $totalResult['total'];
    $countStmt->close();
    
    // Determinar ordenamiento
    $orderBy = match($orden) {
        'oldest' => 'p.fecha_registro ASC',
        'price_low' => 'p.precio ASC',
        'price_high' => 'p.precio DESC',
        'available' => 'p.disponibles DESC',
        default => 'p.fecha_registro DESC' // newest
    };
    
    // Agregar ordenamiento y paginación
    $query .= " GROUP BY p.id ORDER BY $orderBy LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    // Ejecutar query de productos
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear datos del producto
        $productos[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'precio' => (float)$row['precio'],
            'precio_formateado' => formatPrice($row['precio']),
            'disponibles' => (int)$row['disponibles'],
            'fecha_registro' => $row['fecha_registro'],
            'vendedor_id' => (int)$row['vendedor_id'],
            'vendedor_nombre' => $row['vendedor_nombre'],
            'vendedor_avatar' => getAvatarUrl($row['vendedor_avatar']),
            'categoria_id' => (int)$row['categoria_id'],
            'categoria_nombre' => $row['categoria_nombre'],
            'subcategoria_nombre' => $row['subcategoria_nombre'],
            'integridad' => $row['integridad_nombre'],
            // Usar imagen del producto o placeholder dinámico de picsum
            'imagen' => !empty($row['producto_imagen']) 
                ? 'uploads/' . $row['producto_imagen'] 
                : 'https://picsum.photos/seed/' . $row['id'] . '/400/300'
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    // Calcular información de paginación
    $totalPages = ceil($totalProductos / $limit);
    $hasMore = $page < $totalPages;
    
    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalProductos,
            'total_pages' => $totalPages,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener productos: ' . $e->getMessage()
    ]);
}
?>
