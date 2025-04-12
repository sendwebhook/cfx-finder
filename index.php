<?php
session_start();

// Habilitar modo de depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicializar variables de sesión
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = false;
}
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = '';
}
if (!isset($_SESSION['key'])) {
    $_SESSION['key'] = '';
}

// Clave de la API para detectar VPN
const VPN_API_KEY = "36696d159ba04b3886a15b2cca84e12b";
$webhook_url = "https://discord.com/api/webhooks/1350825756653256836/MHvdnzHFd9BM0khiVyR_FZ4sV8SBxbXg9q5F3qBobAThJOt1bhUYbp3_amrAl_hGESdk";

// Verificar VPN/Tor
$ip = $_SERVER['REMOTE_ADDR'];
$geo_url = "http://ip-api.com/json/{$ip}?fields=status,message,country,city,isp";
$geo_data = json_decode(file_get_contents($geo_url), true);
$location = $geo_data['status'] === 'success' ? $geo_data['country'] . ", " . $geo_data['city'] : "No disponible";
$company = $geo_data['status'] === 'success' ? $geo_data['isp'] : "No disponible";
$vpn_url = "https://ipqualityscore.com/api/json/ip/" . VPN_API_KEY . "?ip={$ip}";
$vpn_data = json_decode(file_get_contents($vpn_url), true);
$vpn_detected = $vpn_data['vpn'] ?? false;
$tor_detected = $vpn_data['tor'] ?? false;

$data = ["ip" => $ip, "compañia" => $company, "localizacion" => $location, "vpn" => $vpn_detected ? "Sí" : "No", "tor" => $tor_detected ? "Sí" : "No"];
$payload = json_encode(["content" => null, "embeds" => [["title" => "Nuevo visitante", "fields" => array_map(fn($k, $v) => ["name" => $k, "value" => $v, "inline" => true], array_keys($data), $data), "color" => 5814783]]]);
$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);

if ($vpn_detected || $tor_detected) {
    http_response_code(403);
    die("Acceso denegado: Se detectó el uso de VPN o Tor.");
}

// Verificar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $input_username = trim($_POST['username']);
    $input_key = trim($_POST['key']);
    $keys = json_decode(file_get_contents('keys.json'), true) ?: [];

    $valid_key = false;
    foreach ($keys as $k) {
        if ($k['username'] === $input_username && $k['key'] === $input_key && !$k['expired']) {
            $expiry = strtotime($k['expiry']);
            if ($expiry < time()) {
                $k['expired'] = true;
                file_put_contents('keys.json', json_encode($keys));
            } else {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $input_username;
                $_SESSION['key'] = $input_key;
                header("Location: index.php");
                exit;
            }
            break;
        }
    }
    if (!$_SESSION['logged_in']) {
        $error = "Usuario o clave incorrectos o expirados";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FiveM Server Finder</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #fff;
            overflow: hidden;
            position: relative;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 400px;
            text-align: center;
            transition: transform 0.3s ease-in-out;
            animation: fadeIn 1s ease-in-out;
        }
        .container:hover {
            transform: scale(1.02);
        }
        h1 {
            color: #00d4ff;
            margin-bottom: 20px;
            font-size: 2em;
            animation: glow 2s infinite alternate;
        }
        input[type="text"], input[type="password"] {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            outline: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.02);
        }
        button {
            padding: 10px 20px;
            background: #00d4ff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        button:hover {
            background: #00aaff;
            transform: translateY(-2px);
        }
        #resultado {
            margin-top: 20px;
            color: #00ffcc;
            font-size: 1.1em;
            animation: fadeIn 0.5s ease-in-out;
        }
        .login-section {
            display: <?php echo $_SESSION['logged_in'] ? 'none' : 'block'; ?>;
        }
        .search-section {
            display: <?php echo $_SESSION['logged_in'] ? 'block' : 'none'; ?>;
        }
        .admin-link {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #00ffcc;
            text-decoration: none;
            font-size: 0.8em;
            padding: 5px 10px;
            transition: color 0.3s ease;
        }
        .admin-link:hover {
            color: #00d4ff;
        }
        .logout-btn {
            background: #ff4444;
        }
        .logout-btn:hover {
            background: #cc0000;
        }
        .error {
            color: #ff4444;
            margin-top: 10px;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes glow {
            from { text-shadow: 0 0 5px #00d4ff; }
            to { text-shadow: 0 0 10px #00d4ff, 0 0 20px #00d4ff; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-section">
            <h1>Login</h1>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Nombre de usuario" required>
                <input type="password" name="key" placeholder="Clave" required>
                <button type="submit" name="login">Iniciar sesión</button>
            </form>
            <a href="admin.php" class="admin-link">admin</a>
        </div>
        <div class="search-section">
            <h1>Buscar IP FiveM</h1>
            <input type="text" id="cfxLink" placeholder="Ingresa enlace cfx.re/join/xxxxxx">
            <button onclick="buscarServer()">Buscar IP y Puerto</button>
            <div id="resultado"></div>
            <form method="POST" style="margin-top: 15px;">
                <button type="submit" name="logout" class="logout-btn">Cerrar sesión</button>
            </form>
        </div>
    </div>

    <script>
        function buscarServer() {
            let cfxLink = document.getElementById('cfxLink').value.trim();
            if (!cfxLink.startsWith('http://') && !cfxLink.startsWith('https://')) {
                cfxLink = 'https://' + cfxLink;
            }
            const cfxRegex = /^https?:\/\/cfx\.re\/join\/[a-z0-9]{6}$/i;
            if (!cfxRegex.test(cfxLink)) {
                document.getElementById('resultado').innerText = 'Por favor, ingresa un enlace válido (ej. https://cfx.re/join/xxxxxx)';
                return;
            }

            fetch(`buscar_server.php?cfx=${encodeURIComponent(cfxLink)}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('resultado').innerText = data;
                })
                .catch(error => {
                    document.getElementById('resultado').innerText = 'Error al buscar el servidor';
                    console.error('Error:', error);
                });
        }
    </script>
</body>
</html>
