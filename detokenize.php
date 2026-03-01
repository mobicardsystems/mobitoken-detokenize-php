<?php

// Mandatory claims
$mobicard_version = "2.0";
$mobicard_mode = "LIVE"; // production
$mobicard_merchant_id = "4";
$mobicard_api_key = "YmJkOGY0OTZhMTU2ZjVjYTIyYzFhZGQyOWRiMmZjMmE2ZWU3NGIxZWM3ZTBiZSJ9";
$mobicard_secret_key = "NjIwYzEyMDRjNjNjMTdkZTZkMjZhOWNiYjIxNzI2NDQwYzVmNWNiMzRhMzBjYSJ9";

$mobicard_token_id = abs(rand(1000000,1000000000));
$mobicard_token_id = "$mobicard_token_id";

$mobicard_txn_reference = abs(rand(1000000,1000000000));
$mobicard_txn_reference = "$mobicard_txn_reference";

$mobicard_service_id = "20000";
$mobicard_service_type = "DETOKENIZATION";

// Token to detokenize (retrieved from your database)
$mobicard_card_token = "bbaefff665082af8f3a41fa51853062b1628345cec085498bba97e3ae3b1e77e4f7ac5ee0ac9bbf10ff8c151d006d80212a3dac731c48188a9e00f9084b163bf";

// Create JWT Header
$mobicard_jwt_header = [
    "typ" => "JWT",
    "alg" => "HS256"
];
$mobicard_jwt_header = rtrim(strtr(base64_encode(json_encode($mobicard_jwt_header)), '+/', '-_'), '=');

// Create JWT Payload
$mobicard_jwt_payload = array(
    "mobicard_version" => "$mobicard_version",
    "mobicard_mode" => "$mobicard_mode",
    "mobicard_merchant_id" => "$mobicard_merchant_id",
    "mobicard_api_key" => "$mobicard_api_key",
    "mobicard_service_id" => "$mobicard_service_id",
    "mobicard_service_type" => "$mobicard_service_type",
    "mobicard_token_id" => "$mobicard_token_id",
    "mobicard_txn_reference" => "$mobicard_txn_reference",
    "mobicard_card_token" => "$mobicard_card_token"
);

$mobicard_jwt_payload = rtrim(strtr(base64_encode(json_encode($mobicard_jwt_payload)), '+/', '-_'), '=');

// Generate Signature
$header_payload = $mobicard_jwt_header . '.' . $mobicard_jwt_payload;
$mobicard_jwt_signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header_payload, $mobicard_secret_key, true)), '+/', '-_'), '=');

// Create Final JWT
$mobicard_auth_jwt = "$mobicard_jwt_header.$mobicard_jwt_payload.$mobicard_jwt_signature";

// Make API Call
$mobicard_request_access_token_url = "https://mobicardsystems.com/api/v1/card_tokenization";

$mobicard_curl_post_data = array('mobicard_auth_jwt' => $mobicard_auth_jwt);

$curl_mobicard = curl_init();
curl_setopt($curl_mobicard, CURLOPT_URL, $mobicard_request_access_token_url);
curl_setopt($curl_mobicard, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_mobicard, CURLOPT_POST, true);
curl_setopt($curl_mobicard, CURLOPT_POSTFIELDS, json_encode($mobicard_curl_post_data));
curl_setopt($curl_mobicard, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl_mobicard, CURLOPT_SSL_VERIFYPEER, false);
$mobicard_curl_response = curl_exec($curl_mobicard);
curl_close($curl_mobicard);

// Parse Response
$response_data = json_decode($mobicard_curl_response, true);

if(isset($response_data) && is_array($response_data)) {
    if($response_data['status'] === 'SUCCESS') {
        // Extract card details for processing
        $card_number = $response_data['card_information']['card_number'];
        $card_expiry_date = $response_data['card_information']['card_expiry_date'];
        $card_token = $response_data['card_information']['card_token'];
        
        echo "Detokenization Successful!
";
        echo "Card Number: " . $response_data['card_information']['card_number_masked'] . "
";
        echo "Expiry Date: " . $card_expiry_date . "
";
        
        // If single-use token flag was set, new token is returned
        if($response_data['mobicard_single_use_token_flag'] === '1') {
            echo "New Token Generated: " . $card_token . "
";
            echo "Update your database with the new token.";
        }
        
        // Use $card_number for payment processing
        // Process payment here...
        
    } else {
        echo "Error: " . $response_data['status_message'] . " (Code: " . $response_data['status_code'] . ")";
    }
} else {
    echo "Error: Invalid API response";
}
