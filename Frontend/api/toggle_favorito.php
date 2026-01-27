<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$vendedor_id = isset($_POST['vendedor_id']) ? (int)$_POST['vendedor_id'] : 0;

if ($vendedor_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de vendedor inválido']);
    exit;
}

// No permitir agregarse a uno mismo
if ($vendedor_id === $user['id']) {
    echo json_encode(['success' => false, 'error' => 'No puedes agregarte a ti mismo como favorito']);
    exit;
}

$conn = getDBConnection();

// Revisar si ya existe
$stmt = $conn->prepare("SELECT id FROM favoritos WHERE votante_id = ? AND votado_id = ?");
$stmt->bind_param("ii", $user['id'], $vendedor_id);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existe) {
    // Si ya existe → eliminar
    $stmt = $conn->prepare("DELETE FROM favoritos WHERE votante_id = ? AND votado_id = ?");
    $stmt->bind_param("ii", $user['id'], $vendedor_id);
    $stmt->execute();
    $stmt->close();
    
    $isFavorite = false;
    $message = 'Vendedor quitado de favoritos';
} else {
    // Si no existe → agregar
    $stmt = $conn->prepare("INSERT INTO favoritos (votante_id, votado_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user['id'], $vendedor_id);
    $stmt->execute();
    $stmt->close();
    
    $isFavorite = true;
    $message = 'Vendedor agregado a favoritos';
}

$conn->close();

echo json_encode([
    'success' => true,
    'is_favorite' => $isFavorite,
    'message' => $message
]);
