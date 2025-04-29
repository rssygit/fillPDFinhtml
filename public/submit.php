<?php
session_start(); // or you can save to JSON file if you prefer

// PDF.co API Key
$apiKey = 'rtsb.sy@gmail.com_2GaZSXqo9pRx1qm7vA8Ywc8lPHzjZV3cS8i61nk5WbIJVqe0WKtZwsWmLyvb01se';

// Function to upload a file to PDF.co and get the URL
function uploadFileToPDFco($filePath, $apiKey) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.pdf.co/v1/file/upload',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($filePath)
        ],
        CURLOPT_HTTPHEADER => [
            "x-api-key: $apiKey"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return false;
    } else {
        $result = json_decode($response, true);
        if (!empty($result['url'])) {
            return $result['url']; // return uploaded file URL
        }
    }

    return false;
}

// List of expected image fields from HTML form
$imageFields = ['image1', 'image2', 'image3', 'image4', 'image5', 'image6', 'TechnicianSign', 'ClientSign2'];

// Storage for uploaded images URLs
$uploadedImages = [];

// Loop through expected image fields
foreach ($imageFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
        $tempFilePath = $_FILES[$field]['tmp_name'];
        $uploadedUrl = uploadFileToPDFco($tempFilePath, $apiKey);

        if ($uploadedUrl !== false) {
            $uploadedImages[$field] = $uploadedUrl;
        } else {
            echo "Failed to upload $field to PDF.co!<br>";
        }
    }
}

// Now also save normal text fields and checkbox fields
$normalFields = [];

// Example: Loop through all posted data
foreach ($_POST as $key => $value) {
    if (!in_array($key, $imageFields)) { // skip image fields
        if (is_array($value)) {
            // For checkbox arrays (if multiple checkbox selected)
            $normalFields[$key] = implode(", ", $value);
        } else {
            // Normal text fields
            $normalFields[$key] = $value;
        }
    }
}

// Save everything into Session
$_SESSION['form_data'] = [
    'images' => $uploadedImages,
    'fields' => $normalFields
];

// Redirect to generate PDF
header("Location: generate_pdf.php");
exit;
?>
