<?php

header('Content-Type: application/json');

ini_set('display_errors',1);
error_reporting(E_ALL);

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode([
        "success"=>false,
        "error"=>"Ungültige Anfrage."
    ]);
    exit;
}

if(!isset($_FILES['file'])){
    echo json_encode([
        "success"=>false,
        "error"=>"Keine Datei empfangen."
    ]);
    exit;
}

$file = $_FILES['file'];

if($file['error'] !== UPLOAD_ERR_OK){
    echo json_encode([
        "success"=>false,
        "error"=>"Uploadfehler ".$file['error']
    ]);
    exit;
}

$uploadDir = "../uploads/";

if(!is_dir($uploadDir)){
    mkdir($uploadDir,0777,true);
}

$fileExtension = strtolower(
    pathinfo($file['name'],PATHINFO_EXTENSION)
);

$fileName = bin2hex(random_bytes(16));

if($fileExtension){
    $fileName .= ".".$fileExtension;
}

$physicalPath = $uploadDir.$fileName;
$publicPath = "uploads/".$fileName;

if(!move_uploaded_file($file['tmp_name'],$physicalPath)){

    echo json_encode([
        "success"=>false,
        "error"=>"Datei konnte nicht gespeichert werden."
    ]);

    exit;
}

echo json_encode([
    "success"=>true,
    "path"=>$publicPath,
    "original_filename"=>$file['name'],
    "file_size"=>$file['size']
]);