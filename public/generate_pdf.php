<?php

header('Content-Type: application/json');

$apiKey = 'rtsb.sy@gmail.com_2GaZSXqo9pRx1qm7vA8Ywc8lPHzjZV3cS8i61nk5WbIJVqe0WKtZwsWmLyvb01se';
$templateId = 'filetoken://944fe515aa9895ef08708501720ae6efbb12796c5048e6d0d8';

$fields = [];
$images_payload = []; // Initialize array for coordinate-based images

// Placeholder coordinates and dimensions (in points) - ADJUST THESE VALUES
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


// Collect technician names into an array, filtering out empty ones and the default space value
$tech_names = array_filter(
    [
        $_POST['tech1'] ?? '',
        $_POST['tech2'] ?? '',
        $_POST['tech3'] ?? '',
        $_POST['tech4'] ?? ''
    ],
    function($value) {
        // Keep the value if it's not null, not an empty string, and not just a space " " from the default option
        return $value !== null && $value !== '' && trim($value) !== '';
    }
);
// Join the non-empty names with a comma and space
$technician = implode(', ', $tech_names);


foreach ($_POST as $key => $value) {
    if (!is_string($key) || empty($value)) continue;

    // Skip base64 signature fields handled separately
    if ($key === 'signature1Data' || $key === 'signature2Data') {
        continue; // Skip signature data handled later
    }

    // Handle normal fields (including tech1, tech2, etc. individually for now)
    // We will add the combined Technician field *after* this loop.
    $fields[] = [
        "fieldName" => $key,
        "pages" => "0",
        "text" => $value,
        "fontName" => "Times New Roman",
        "fontSize" => 8
    ];
}

// 4. Handle uploaded image fields (using the 'name' attribute from HTML)
foreach ($_FILES as $key => $file) { // $key will be 'Image1', 'Image2', etc.
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) continue;

    $imageUrl = uploadFileToPDFco($file['tmp_name'], $apiKey);
    // Log image upload result
    error_log("PDF.co Upload Result for $key: " . ($imageUrl ?: 'Failed'));

    if ($imageUrl) {
        $fieldNameLower = strtolower($key); // e.g., 'image1'
        // Check if coordinates are defined for this image field
        if (isset($imageCoordinates[$fieldNameLower])) {
            $coords = $imageCoordinates[$fieldNameLower];
            $images_payload[] = [
                "url" => $imageUrl,
                "pages" => $coords['pages'],
                "x" => $coords['x'],
                "y" => $coords['y'],
                "width" => $coords['width'],
                "height" => $coords['height']
                // "async" => false // Optional: set to true for faster response if needed
            ];
        } else {
             error_log("Warning: Coordinates not defined for image field: " . $fieldNameLower);
             // Optionally, add as a field as a fallback? Or just skip? Skipping for now.
        }
    }
}

// 5. Handle Signatures (base64 images from hidden inputs)
// Technician Signature
if (!empty($_POST['signature1Data'])) {
    // Check if the data is a valid base64 string with prefix
    if (preg_match('/^data:image\/png;base64,/', $_POST['signature1Data'])) {
        $base64 = explode(',', $_POST['signature1Data'])[1];
        $signaturePath = sys_get_temp_dir() . '/temp_signature_' . uniqid() . '.png'; // Use temp dir
        if (file_put_contents($signaturePath, base64_decode($base64))) {
            $signatureUrl = uploadFileToPDFco($signaturePath, $apiKey);
            // Log signature upload result
            error_log("PDF.co Upload Result for TechnicianSign: " . ($signatureUrl ?: 'Failed'));
            if ($signatureUrl) {
                $fieldNameLower = 'techniciansign'; // Consistent key for coordinate lookup
                if (isset($imageCoordinates[$fieldNameLower])) {
                    $coords = $imageCoordinates[$fieldNameLower];
                    $images_payload[] = [
                        "url" => $signatureUrl,
                        "pages" => $coords['pages'],
                        "x" => $coords['x'],
                        "y" => $coords['y'],
                        "width" => $coords['width'],
                        "height" => $coords['height']
                    ];
                } else {
                    error_log("Warning: Coordinates not defined for image field: " . $fieldNameLower);
                }
            }
            unlink($signaturePath); // Clean up temp file
        }
    }
}

// Client Signature
if (!empty($_POST['signature2Data'])) {
     // Check if the data is a valid base64 string with prefix
    if (preg_match('/^data:image\/png;base64,/', $_POST['signature2Data'])) {
        $base64Client = explode(',', $_POST['signature2Data'])[1];
        $clientSignPath = sys_get_temp_dir() . '/temp_client_signature_' . uniqid() . '.png'; // Use temp dir
        if (file_put_contents($clientSignPath, base64_decode($base64Client))) {
            $clientSignatureUrl = uploadFileToPDFco($clientSignPath, $apiKey);
            // Log signature upload result
            error_log("PDF.co Upload Result for ClientSign: " . ($clientSignatureUrl ?: 'Failed'));
            if ($clientSignatureUrl) {
                 $fieldNameLower = 'clientsign'; // Consistent key for coordinate lookup
                 if (isset($imageCoordinates[$fieldNameLower])) {
                    $coords = $imageCoordinates[$fieldNameLower];
                    $images_payload[] = [
                        "url" => $clientSignatureUrl,
                        "pages" => $coords['pages'],
                        "x" => $coords['x'],
                        "y" => $coords['y'],
                        "width" => $coords['width'],
                        "height" => $coords['height']
                    ];
                 } else {
                    error_log("Warning: Coordinates not defined for image field: " . $fieldNameLower);
                 }
            }
            unlink($clientSignPath); // Clean up temp file
        }
    }
}

// Add the combined Technician field explicitly after processing other POST fields
// Assuming the PDF field name is 'Technician'
if (!empty($technician)) {
    $fields[] = [
        "fieldName" => "Technician", // PDF Field for combined Technician Name
        "pages" => "0",
        "text" => $technician,
        "fontName" => "Times New Roman",
        "fontSize" => 8 // Adjust font size if needed
    ];
}


$payload = [
    "name" => "filled_sdo.pdf",
    "url" => $templateId,
    "fields" => $fields // Keep existing text fields
    // Add images payload if it's not empty
];

if (!empty($images_payload)) {
    $payload["images"] = $images_payload;
}

// Log the final payload before sending
error_log("PDF.co Payload: " . json_encode($payload));

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
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 30,
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (!empty($result['url'])) {
    $pdfUrl = str_replace('\u0026', '&', $result['url']); // fix escape characters
    // Instead of redirecting, send the URL back as JSON
    echo json_encode([
        "success" => true,
        "url" => $pdfUrl
    ]);
    exit;
} else {
    // Log the full error response from PDF.co
    error_log("PDF.co Generation Failed. Response: " . json_encode($result));
    echo json_encode([
        "success" => false,
        "message" => "Failed to generate PDF",
        "error" => $result // Keep sending the error back to the browser too
    ]);
}

function uploadFileToPDFco($filePath, $apiKey) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.pdf.co/v1/file/upload',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["x-api-key: $apiKey"],
        CURLOPT_POSTFIELDS => ['file' => new CURLFile($filePath)],
        CURLOPT_TIMEOUT => 60, // Keep increased timeout
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
    $curlError = curl_error($ch); // Get cURL error message
    curl_close($ch);

    // Log cURL errors if any
    if ($curlError) {
        error_log("PDF.co Upload cURL Error for $filePath: " . $curlError);
    }
    // Log HTTP status code
     error_log("PDF.co Upload HTTP Status for $filePath: " . $httpCode);


    $result = json_decode($response, true);
    // Log the raw response from file upload
    error_log("PDF.co Upload Raw Response for $filePath: " . $response);
    return $result['url'] ?? null;
}
?>
