<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

$timestamp = date("Ymd_His");
$uploadDir = 'D:/yu/Web_Based_PDF_Form/backup/uploads_' . $timestamp . '/';
mkdir($uploadDir, 0777, true);

// Save uploaded image files
foreach ($_FILES as $key => $file) {
    if ($file['error'] == 0) {
        move_uploaded_file($file['tmp_name'], $uploadDir . basename($file['name']));
    }
}

// Save form data (excluding signatures) to JSON
$formData = $_POST;
unset($formData['signature1'], $formData['signature2'], $formData['signature3']);
file_put_contents($uploadDir . 'form_data.json', json_encode($formData, JSON_PRETTY_PRINT));

// Pass everything including base64 signatures to PDF generator
$_POST['folder'] = $uploadDir;
include('generate_pdf.php');
?>
