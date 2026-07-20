<?php
$db_path = __DIR__ . '/../database.db';
$pdo = new PDO("sqlite:$db_path");

header('Content-Type: application/json');

$sort = $_GET['sort'] ?? 'links';
$orderBy = $sort === 'clicks'
    ? 'clicks DESC'
    : 'links DESC';

$stmt = $pdo->query("
SELECT LOWER( REPLACE( REPLACE(REPLACE(url,'https://',''),'http://',''),'www.','')) AS domain, COUNT(*) AS links,SUM(clicks) AS clicks FROM shortlinks WHERE url IS NOT NULL AND url <> '' GROUP BY domain ORDER BY $orderBy LIMIT 10");

$data = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $domain = explode("/", $row['domain'])[0];
    $data[] = [
        "domain"=>$domain,
        "links"=>(int)$row['links'],
        "clicks"=>(int)$row['clicks']
    ];
}

echo json_encode($data);