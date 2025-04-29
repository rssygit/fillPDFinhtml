<?php
session_start();
header('Content-Type: application/json');

$formData = $_SESSION['form_data'] ?? null;

if (!$formData) {
    echo json_encode(["success" => false, "message" => "No form data found!"]);
    exit;
}

$apiKey = 'rtsb.sy@gmail.com_2GaZSXqo9pRx1qm7vA8Ywc8lPHzjZV3cS8i61nk5WbIJVqe0WKtZwsWmLyvb01se';
$templateId = 'filetoken://b8ac853a49321875e93bec0c28c7e20ed107af93db34161fad';

$imagePositions = [
    ["fieldName" => "image1", "x" => 50, "y" => 210, "width" => 150, "height" => 150],
    ["fieldName" => "image2", "x" => 230, "y" => 210, "width" => 150, "height" => 150],
    ["fieldName" => "image3", "x" => 410, "y" => 210, "width" => 150, "height" => 150],
    ["fieldName" => "image4", "x" => 50, "y" => 430, "width" => 150, "height" => 150],
    ["fieldName" => "image5", "x" => 230, "y" => 430, "width" => 150, "height" => 150],
    ["fieldName" => "image6", "x" => 410, "y" => 430, "width" => 150, "height" => 150],
    ["fieldName" => "TechnicianSign", "x" => 400, "y" => 640, "width" => 80, "height" => 50],
    ["fieldName" => "ClientSign", "x" => 490, "y" => 640, "width" => 80, "height" => 50]
];

$fields = [];

$technician = trim(
    ($formData['fields']['tech1'] ?? '') . ',' .
    ($formData['fields']['tech2'] ?? '') . ',' .
    ($formData['fields']['tech3'] ?? '') . ',' .
    ($formData['fields']['tech4'] ?? '')
);

// Add regular fields, skipping individual tech fields
foreach ($formData['fields'] as $key => $value) {
    if (empty($value) || in_array($key, ['tech1', 'tech2', 'tech3', 'tech4'])) {
        continue; // Skip empty fields and individual tech fields
    }
    $fields[] = [
        "fieldName" => $key,
        "pages" => "0",
        "text" => $value,
        "fontName" => "Times New Roman",
        "fontSize" => 8
    ];
}

// Add the combined Technician field if it's not empty
if (!empty($technician)) {
    $fields[] = [
        "fieldName" => "Technician",
        "pages" => "0",
        "text" => $technician,
        "fontName" => "Times New Roman",
        "fontSize" => 8
    ];
}

$imagesArray = [];
foreach ($imagePositions as $img) {
    if (isset($formData['images'][$img['fieldName']])) {
        $imagesArray[] = [
            "url" => $formData['images'][$img['fieldName']],
            "pages" => "0",
            "x" => $img['x'],
            "y" => $img['y'],
            "width" => $img['width'],
            "height" => $img['height']
        ];
    }
}

$payload = [
    "name" => "filled_sdo.pdf",
    "url" => $templateId,
    "fields" => $fields,
    "images" => $imagesArray
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.pdf.co/v1/pdf/edit/add',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-api-key: $apiKey"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(["success" => false, "message" => "cURL Error: $err"]);
    exit;
}

$result = json_decode($response, true);

if (!empty($result['url'])) {
    echo json_encode(["success" => true, "url" => $result['url']]);
} else {
    echo json_encode(["success" => false, "message" => "PDF generation failed", "error" => $result]);
}
exit;
?>
