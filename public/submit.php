<?php

$formData = $_POST;
$fileData = $_FILES;

$boundary = uniqid();
$delimiter = '-------------' . $boundary;

$postData = buildDataFiles($boundary, $formData, $fileData);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'generate_pdf.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: multipart/form-data; boundary=" . $delimiter,
    ],
    CURLOPT_POSTFIELDS => $postData,
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;


function buildDataFiles($boundary, $fields, $files) {
    $data = '';

    // Text fields
    foreach ($fields as $name => $value) {
        $data .= "--" . $boundary . "\r\n"
            . 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n"
            . $value . "\r\n";
    }

    // File uploads
    foreach ($files as $name => $file) {
        if (empty($file['tmp_name'])) continue;

        $fileContent = file_get_contents($file['tmp_name']);
        $data .= "--" . $boundary . "\r\n"
            . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['name'] . "\"\r\n"
            . "Content-Type: " . $file['type'] . "\r\n\r\n"
            . $fileContent . "\r\n";
    }

    $data .= "--" . $boundary . "--\r\n";
    return $data;
}
?>
