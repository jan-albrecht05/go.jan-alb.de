<?php
header('Content-Type: application/json');
$db_path = __DIR__ . '/../database.db';
$pdo = new PDO("sqlite:$db_path");


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

    $where[] = "
        active = 1
        AND (
            expires_at IS NULL
            OR expires_at > datetime('now')
        )
    ";

}

if ($status === 'expired') {

    $where[] = "
        expires_at IS NOT NULL
        AND expires_at <= datetime('now')
    ";

}

if ($status === 'disabled') {

    $where[] = "
        active = 0
    ";

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