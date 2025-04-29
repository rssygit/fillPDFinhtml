<?php

header('Content-Type: application/json');

// 1. Setup PDF.co credentials
$apiKey = 'rtsb.sy@gmail.com_2GaZSXqo9pRx1qm7vA8Ywc8lPHzjZV3cS8i61nk5WbIJVqe0WKtZwsWmLyvb01se';
$templateId = 'filetoken://944fe515aa9895ef08708501720ae6efbb12796c5048e6d0d8';

$fields = [];

$imageCoordinates = [
    // Images 1-6 from file uploads
    'image1' => ['x' => 50,  'y' => 100, 'width' => 100, 'height' => 75, 'pages' => '0'],
    'image2' => ['x' => 200, 'y' => 100, 'width' => 100, 'height' => 75, 'pages' => '0'],
    'image3' => ['x' => 350, 'y' => 100, 'width' => 100, 'height' => 75, 'pages' => '0'],
    'image4' => ['x' => 50,  'y' => 200, 'width' => 100, 'height' => 75, 'pages' => '0'],
    'image5' => ['x' => 200, 'y' => 200, 'width' => 100, 'height' => 75, 'pages' => '0'],
    'image6' => ['x' => 350, 'y' => 200, 'width' => 100, 'height' => 75, 'pages' => '0'],
    // Signatures
    'techniciansign' => ['x' => 50, 'y' => 700, 'width' => 150, 'height' => 40, 'pages' => '0'], // Adjust coordinates
    'clientsign' => ['x' => 350, 'y' => 700, 'width' => 150, 'height' => 40, 'pages' => '0'], // Adjust coordinates
];

// 2. Handle Technician merging
$technician = trim(
    ($_POST['tech1'] ?? '') . ' ' .
    ($_POST['tech2'] ?? '') . ' ' .
    ($_POST['tech3'] ?? '') . ' ' .
    ($_POST['tech4'] ?? '')
);

// 3. Handle normal text fields
foreach ($_POST as $key => $value) {
    if (!is_string($key) || empty($value)) continue;

    // Skip base64 signature field
    if (strpos($key, 'signature') !== false) {
        continue;
    }

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

foreach ($_FILES as $key => $file) {
    $payload = [
        "name" => 'test.pdf',
        "url" => $templateId,
        "images" => [
            [
                "url" => $imageUrl,
                "pages" => "0",
                "x" => $coords['x'],
                "y" => $coords['y'],
                "width" => $coords['width'],
                "height" => $coords['height']
            ]
        ]
    ];
}

// 5. Handle Signature (base64 image)
if (!empty($_POST['signature'])) {
    $base64 = explode(',', $_POST['signature'])[1];
    $signaturePath = 'temp_signature.png';
    file_put_contents($signaturePath, base64_decode($base64));

    $signatureUrl = uploadFileToPDFco($signaturePath, $apiKey);

    if ($signatureUrl) {
        $fields[] = [
            "fieldName" => "TechnicianSign",
            "pages" => "0",
            "image" => $signatureUrl
        ];
    }
}
if (!empty($_POST['client_signature'])) {
    $base64Client = explode(',', $_POST['client_signature'])[1];
    $clientSignPath = 'temp_client_signature.png';
    file_put_contents($clientSignPath, base64_decode($base64Client));

    $clientSignatureUrl = uploadFileToPDFco($clientSignPath, $apiKey);

    if ($clientSignatureUrl) {
        $fields[] = [
            "fieldName" => "ClientSign",
            "pages" => "0",
            "image" => $clientSignatureUrl
        ];
    }
} 

// 6. Build final payload
$payload = [
    "name" => "filled_sdo.pdf",
    "url" => $templateId,
    "fields" => $fields
];

// 7. Send API request to fill PDF
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
curl_close($ch);

$result = json_decode($response, true);

// 8. Handle response
if (!empty($result['url'])) {
    $pdfUrl = str_replace('\u0026', '&', $result['url']); // fix escape characters
    header("Location: $pdfUrl"); // Auto redirect to filled PDF
    exit;
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to generate PDF",
        "error" => $result
    ]);
}

// === Helper function ===
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
?>
