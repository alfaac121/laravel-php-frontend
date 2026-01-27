<?php
require_once 'config.php';


// Redirigir a login si no está autenticado
if (!isLoggedIn()) {
    header('Location: welcome.php');
    exit;
}

// Usuario ya está autenticado via sesión PHP

$conn = getDBConnection();
$user = getCurrentUser();

// Filtros (se pasan a JavaScript para la API)
$categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$busqueda = isset($_GET['busqueda']) ? sanitize($_GET['busqueda']) : '';

// Obtener categorías para el filtro
$categorias_query = "SELECT * FROM categorias ORDER BY nombre";
$categorias_result = $conn->query($categorias_query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Mercado SENA - Marketplace</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">
                    <a href="index.php">
                        <img src="logo_new.png" class="logo-img">
                        Tu Mercado SENA
                    </a>
                </h1>
                
                <nav class="nav nav-desktop">
                    <a href="mis_productos.php">Mis Productos</a>
                    <a href="favoritos.php">Favoritos</a>
                    <a href="publicar.php">Publicar Producto</a>
                    <div class="notification-badge">
                        <i class="ri-chat-3-line notification-icon" id="notificationIcon" title="Chats y notificaciones"></i>
                        <span class="notification-count hidden" id="notificationCount">0</span>
                        <div class="chats-list" id="chatsList"></div>
                    </div>
                    <a href="perfil.php" class="perfil-link">
                        <div class="user-avatar-container">
                            <img src="<?= getAvatarUrl($user['imagen']); ?>" 
                                 class="avatar-header" id="headerAvatar" alt="Mi Avatar">
                            <span class="user-name-footer"><?php echo htmlspecialchars($user['nickname']); ?></span>
                        </div>
                    </a>
                </nav>
            </div>
        </div>
    </header>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="filters-section">
                <div class="filters-form" id="filtersForm">
                    <div class="filter-group">
                        <input type="text" id="searchInput" placeholder="Buscar productos..." 
                               value="<?php echo htmlspecialchars($busqueda); ?>" class="search-input">
                    </div>
                    <div class="filter-group">
                        <select id="categoryFilter" class="select-input">
                            <option value="0">Categorías</option>
                            <?php while ($cat = $categorias_result->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $categoria_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select id="sortFilter" class="select-input">
                            <option value="newest">Más recientes</option>
                            <option value="oldest">Más antiguos</option>
                            <option value="price_low">Menor precio</option>
                            <option value="price_high">Mayor precio</option>
                            <option value="available">Más disponibles</option>
                        </select>
                    </div>
                    <button type="button" id="clearFiltersBtn" class="btn-link" style="display: none;">Limpiar filtros</button>
                </div>
            </div>

            <!-- Contenedor de productos con Infinite Scroll -->
            <div class="products-grid" id="productsGrid">
                <!-- Los productos se cargarán dinámicamente via JavaScript -->
            </div>
            
            <!-- Skeleton Loaders (se muestran mientras carga) -->
            <div class="products-grid skeleton-grid" id="skeletonGrid">
                <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="product-card skeleton-card">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton-info">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-price"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text-short"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Indicador de carga para infinite scroll -->
            <div class="loading-more" id="loadingMore" style="display: none;">
                <div class="loading-spinner"></div>
                <span>Cargando más productos...</span>
            </div>
            
            <!-- Mensaje cuando no hay más productos -->
            <div class="no-more-products" id="noMoreProducts" style="display: none;">
                <p>✨ Has visto todos los productos disponibles</p>
            </div>
            
            <!-- Mensaje cuando no hay productos -->
            <div class="no-products" id="noProducts" style="display: none;">
                <p>No se encontraron productos. ¡Sé el primero en publicar!</p>
                <?php if ($user): ?>
                    <a href="publicar.php" class="btn-primary">Publicar Producto</a>
                <?php endif; ?>
            </div>
            
            <!-- Pasar filtros actuales a JavaScript -->
            <script>
                window.productFilters = {
                    categoria: <?php echo json_encode($categoria_id); ?>,
                    busqueda: <?php echo json_encode($busqueda); ?>,
                    orden: 'newest'
                };
            </script>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script src="script.js?v=<?= time(); ?>"></script>
</body>
</html>
<?php
$conn->close();
?>

