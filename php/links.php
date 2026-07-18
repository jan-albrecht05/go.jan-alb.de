<?php
header('Content-Type: application/json');
$db_path = __DIR__ . '/../database.db';
$pdo = new PDO("sqlite:$db_path");

// -----------------------------
// Funktionen
// -----------------------------

// Link Sichtbarkeit ändern

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    $action = $input['action'] ?? '';

    switch ($action) {

        case 'changeVisibility':

            $id = (int)($input['id'] ?? 0);

            echo json_encode(changeVisibility($id));
            exit;

        default:

            echo json_encode([
                'success' => false,
                'message' => 'Unknown action'
            ]);
            exit;
    }

}

function changeVisibility($linkId) { 
    global $pdo; 
    // Prüfen, ob der Link existiert 
    $stmt = $pdo->prepare("SELECT * FROM shortlinks WHERE id = :id"); 
    $stmt->execute([':id' => $linkId]); 
    $link = $stmt->fetch(PDO::FETCH_ASSOC); 
    
    if (!$link) { 
        return ['success' => false, 'message' => 'Link not found']; 
    } 
    // Sichtbarkeit umschalten 
    $newStatus = $link['active'] ? 0 : 1; 
    $updateStmt = $pdo->prepare("UPDATE shortlinks SET active = :active WHERE id = :id"); 
    $updateStmt->execute([':active' => $newStatus, ':id' => $linkId]);
        return ['success' => true, 'newStatus' => $newStatus]; 
}


// -----------------------------
// Standardmäßig alle Links abrufen
// -----------------------------

// -----------------------------
// Filter
// -----------------------------

$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// -----------------------------
// Sortierung
// -----------------------------

$sort = $_GET['sort'] ?? 'created_at_desc';
switch ($sort) {
    case 'created_at_asc':
        $orderBy = 'created_at ASC';
        break;
    case 'created_at_desc':
        $orderBy = 'created_at DESC';
        break;
    case 'clicks_asc':
        $orderBy = 'clicks ASC';
        break;
    case 'clicks_desc':
        $orderBy = 'clicks DESC';
        break;
    default:
        $orderBy = 'created_at DESC';
        break;
}


// -----------------------------
// SQL bauen
// -----------------------------

$where = [];
$params = [];

// Status
if ($status === 'active') {

    $where[] = "active = 1";

}

if ($status === 'inactive') {

    $where[] = "active = 0";

}

// Typ
if ($type === 'url') {

    $where[] = "type = 'url'";

}

if ($type === 'file') {

    $where[] = "type = 'file'";

}


// Suche
if (!empty($search)) {

    $where[] = "
        (
            hash LIKE :search_hash
            OR target LIKE :search_target
        )
    ";

    $params[':search_hash'] = '%' . $search . '%';
    $params[':search_target'] = '%' . $search . '%';

}


// -----------------------------
// Query
// -----------------------------

$sql = "SELECT * FROM shortlinks";

if(count($where)>0){

    $sql .= " WHERE " . implode(" AND ", $where);

}

$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);
