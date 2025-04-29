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
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'error' => "cURL Error: $err"];
    }

    $result = json_decode($response, true);

    if (!empty($result['url'])) {
        return ['success' => true, 'url' => $result['url']];
    } else {
        return ['success' => false, 'error' => $result['message'] ?? 'Unknown error'];
    }
}

$imageFields = ['image1', 'image2', 'image3', 'image4', 'image5', 'image6', 'TechnicianSign', 'ClientSign'];
$uploadedImages = [];

$tmpFile = $_FILES[$field]['tmp_name'];
if (!file_exists($tmpFile)) {
    echo json_encode([
        'success' => false,
        'message' => "Temp file for $field does not exist!",
        'tmp_name' => $tmpFile
    ]);
    exit;
} elseif (!is_readable($tmpFile)) {
    echo json_encode([
        'success' => false,
        'message' => "Temp file for $field is not readable!",
        'tmp_name' => $tmpFile
    ]);
    exit;
}

foreach ($imageFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
        $uploadResult = uploadFileToPDFco($_FILES[$field]['tmp_name'], $apiKey);
        if ($uploadResult['success']) {
            $uploadedImages[$field] = $uploadResult['url'];
        } else {
            // Stop and show detailed error
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Upload failed for $field",
                'error' => $uploadResult['error']
            ]);
            exit;
        }
    }
}

$normalFields = [];
foreach ($_POST as $key => $value) {
    if (!in_array($key, $imageFields)) {
        $normalFields[$key] = $value;
    }
}

if (empty($uploadedImages)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Image upload failed. No images were successfully uploaded.',
        'imageUrls' => []
    ]);
    exit;
}

$_SESSION['form_data'] = [
    'images' => $uploadedImages,
    'fields' => $normalFields
];

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Data saved successfully!',
    'imageUrls' => $uploadedImages
]);
exit;

?>
