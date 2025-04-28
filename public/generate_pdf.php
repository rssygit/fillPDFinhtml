<?php
// generate_pdf.php - UPDATED for consistency if submit.php uploads are handled already

// Your API Key
$apiKey = "YOUR_PDFCO_API_KEY_HERE"; // <<< Replace this with your real API Key

// Assume this file is called by submit.php AFTER successful upload if needed
// In your new flow, submit.php already handles uploading files
// generate_pdf.php here can still support filling PDF form if needed separately

// Example payload (you can receive POST from submit.php if needed)
$payload = [
    "name" => "final_generated_pdf.pdf",
    "url" => $_POST['template_pdf_url'], // Passed from submit.php or fixed template URL
    "fields" => json_decode($_POST['fields'], true) // Passed from submit.php
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
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 30,
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['url'])) {
    echo "<h2>PDF Generated Successfully!</h2>";
    echo "<a href='" . $result['url'] . "' target='_blank'>Click here to download your PDF</a>";
} else {
    echo "<h2>PDF Generation Failed</h2>";
    echo "<pre>";
    var_dump($result);
    echo "</pre>";
}
?>
