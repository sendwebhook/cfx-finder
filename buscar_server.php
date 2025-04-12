<?php
if (isset($_GET['cfx'])) {
    $cfx_link = $_GET['cfx'];
    $code = preg_replace('/^https?:\/\/cfx\.re\/join\/([a-z0-9]{6}).*$/i', '$1', $cfx_link);

    if (strlen($code) !== 6) {
        echo "Código inválido extraído de {$cfx_link}";
        exit;
    }

    $api_url = "https://servers-frontend.fivem.net/api/servers/single/{$code}";
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0 (compatible; FiveM IP Resolver)']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Error en la solicitud a la API: " . curl_error($ch);
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200) {
            echo "Error: Respuesta de la API (Código HTTP: {$http_code})";
        } else {
            $data = json_decode($response, true);
            if (isset($data['Data']['connectEndPoints']) && !empty($data['Data']['connectEndPoints'])) {
                $endpoint = $data['Data']['connectEndPoints'][0];
                list($ip, $port) = explode(':', $endpoint);
                echo "IP: {$ip}\nPuerto: {$port}";
            } else {
                echo "No se encontró información para el servidor con código {$code}";
            }
        }
    }
    curl_close($ch);
} else {
    echo "Por favor, proporciona un enlace cfx.re válido";
}
?>
