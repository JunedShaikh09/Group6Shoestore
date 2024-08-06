<?php
include_once 'Database.php';

if (isset($_POST['province'])) {
    $province = $_POST['province'];
    $placeOfSupply = new PlaceOfSupply();
    $cities = $placeOfSupply->getCitiesByProvince($province);

    header('Content-Type: application/json');
    echo json_encode($cities);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Province not provided']);
}
