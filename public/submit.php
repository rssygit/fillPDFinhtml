<?php
session_start();

$apiKey = 'rtsb.sy@gmail.com_2GaZSXqo9pRx1qm7vA8Ywc8lPHzjZV3cS8i61nk5WbIJVqe0WKtZwsWmLyvb01se';

function uploadFileToPDFco($filePath, $apiKey) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.pdf.co/v1/file/upload',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["x-api-key: $apiKey"],
        CURLOPT_POSTFIELDS => ['file' => new CURLFile($filePath)],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['url'] ?? null;
}

$imageFields = ['image1', 'image2', 'image3', 'image4', 'image5', 'image6', 'TechnicianSign', 'ClientSign'];
$uploadedImages = [];

foreach ($imageFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
        $uploadedUrl = uploadFileToPDFco($_FILES[$field]['tmp_name'], $apiKey);
        if ($uploadedUrl) {
            $uploadedImages[$field] = $uploadedUrl;
        }
    }
}

$normalFields = [];
foreach ($_POST as $key => $value) {
    if (!in_array($key, $imageFields)) {
        $normalFields[$key] = $value;
    }
}

$_SESSION['form_data'] = [
    'images' => $uploadedImages,
    'fields' => $normalFields
];

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Data saved successfully!']);
exit;
?>
