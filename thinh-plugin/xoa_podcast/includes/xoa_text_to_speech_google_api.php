<?php
function xoa_text_to_speech_google_api($text) {
    $KEY_API = get_option('xoa_api_key_google', '');
    $url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . $KEY_API;

    $chunks = str_split($text, 5000); 

    $audioContents = [];

    foreach ($chunks as $chunk) {
        $data = array(
            'input' => array('text' => $chunk),
            'voice' => array('languageCode' => 'en-US', 'ssmlGender' => 'FEMALE'),
            'audioConfig' => array('audioEncoding' => 'MP3')
        );

        $json_data = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            error_log('Error accessing Text-to-Speech API: ' . $error);
            curl_close($ch);
            return false;
        }

        $response = json_decode($result, true);
        curl_close($ch);

        if (isset($response['audioContent'])) {
            $audioContents[] = base64_decode($response['audioContent']);
        } else {
            error_log('Error in API response: ' . print_r($response, true));
            return false;
        }
    }

    $finalAudio = implode('', $audioContents);

    return $finalAudio;
}