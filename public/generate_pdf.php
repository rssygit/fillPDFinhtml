<?php
session_start();

header('Content-Type: application/json');

// Load form data from session
$formData = $_SESSION['form_data'] ?? null;

// Always output in json format
if (!$formData) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "No form data found!"]);
    exit;
}

$apiKey = 'rtsb.sy@gmail.com_2GaZSXqo9pRx1qm7vA8Ywc8lPHzjZV3cS8i61nk5WbIJVqe0WKtZwsWmLyvb01se';
$templateId = 'filetoken://b8ac853a49321875e93bec0c28c7e20ed107af93db34161fad';
$fields = [];
$images = [
    ["fieldName" => "image1", "x" => 50, "y" => 210, "width" => 150, "height" => 150],
    ["fieldName" => "image2", "x" => 230, "y" => 210, "width" => 150, "height" => 150],
    ["fieldName" => "image3", "x" => 410, "y" => 210, "width" => 150, "height" => 150],
    ["fieldName" => "image4", "x" => 50, "y" => 430, "width" => 150, "height" => 150],
    ["fieldName" => "image5", "x" => 230, "y" => 430, "width" => 150, "height" => 150],
    ["fieldName" => "image6", "x" => 410, "y" => 430, "width" => 150, "height" => 150],
    ["fieldName" => "TechnicianSign", "x" => 400, "y" => 640, "width" => 80, "height" => 50],
    ["fieldName" => "ClientSign", "x" => 490, "y" => 640, "width" => 80, "height" => 50]
];

// Handle Technician merging
$technician = trim(
    ($formData['fields']['tech1'] ?? '') . ' ' .
    ($formData['fields']['tech2'] ?? '') . ' ' .
    ($formData['fields']['tech3'] ?? '') . ' ' .
    ($formData['fields']['tech4'] ?? '')
);

// Handle normal text fields
foreach ($formData['fields'] as $key => $value) {
    if (!is_string($key) || empty($value)) continue;

    // Special handle Technician field (combined)
    if ($key === 'Technician') {
        $fields[] = [
            "fieldName" => "Technician",
            "pages" => "0",
            "text" => $technician,
            "fontName" => "Times New Roman",
            "fontSize" => 8
        ];
        continue;
    }

    // Handle normal fields
    $fields[] = [
        "fieldName" => $key,
        "pages" => "0",
        "text" => $value,
        "fontName" => "Times New Roman",
        "fontSize" => 8
    ];
}

// Handle image fields
$imagesArray = [];
foreach ($images as $img) {
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

// Final payload
$payload = [
    "name" => "filled_sdo.pdf",
    "url" => $templateId,
    "fields" => $fields,
    "images" => $imagesArray
];

// Send API request to fill PDF
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

// Execute request
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($err) {
    die("cURL Error: " . htmlspecialchars($err));
}

// Check result
if (!empty($result['url'])) {
    echo "✅ PDF generated successfully: <a href='" . $result['url'] . "' target='_blank'>Download Here</a>";
    echo json_encode([
        "success" => true,
        "url" => $result['url']
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to generate PDF",
        "error" => $result
    ]);
}
exit;

?>
