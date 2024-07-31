<?php
function xoa_text_to_speech($text)
{
    $curl = curl_init();

    $KEY_API = get_option('xoa_api_key', '');
    $Voice_ID = get_option('xoa_voice_id', '');

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.elevenlabs.io/v1/text-to-speech/{$Voice_ID}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            'text' => $text,
            'model_id' => 'eleven_monolingual_v1'
        ]),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "xi-api-key: $KEY_API"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        error_log("cURL Error: " . $err);
        return false;
    } else {
        return $response;
    }
}