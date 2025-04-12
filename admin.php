<?php
session_start();

// Habilitar modo de depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el archivo keys.json existe y es accesible
if (!file_exists('keys.json')) {
    file_put_contents('keys.json', '[]');
}

// Inicializar variables de sesión
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = false;
}

$admin_username = "deyz";
$admin_password = "deyz@";

// Verificar login de admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);

    if ($input_username === $admin_username && $input_password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $input_username;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Credenciales de administrador incorrectas";
    }
}

// Función para generar claves con formato FINDER-XXXXX-XXXXX
function generateSecureKey() {
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $keyPart1 = '';
    $keyPart2 = '';
    for ($i = 0; $i < 5; $i++) {
        $keyPart1 .= $characters[rand(0, strlen($characters) - 1)];
        $keyPart2 .= $characters[rand(0, strlen($characters) - 1)];
    }
    return "FINDER-" . $keyPart1 . "-" . $keyPart2;
}

// Manejar la lógica del panel si está logueado como admin
if ($_SESSION['admin_logged_in']) {
    $keys = json_decode(file_get_contents('keys.json'), true) ?: [];
    $success_message = '';
    $new_key = '';

    // Crear una nueva clave
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_key'])) {
        $new_username = trim($_POST['username']);
        $duration = $_POST['duration'];

        $new_key = generateSecureKey();
        $expiry = ($duration === 'lifetime') ? '9999-12-31 23:59:59' : date('Y-m-d H:i:s', strtotime("+$duration days"));
        $keys[] = ['username' => $new_username, 'key' => $new_key, 'expiry' => $expiry, 'expired' => false];
        file_put_contents('keys.json', json_encode($keys));
        $success_message = "Clave creada correctamente";
        $new_key_display = $new_key;
    }

    // Eliminar una clave
    if (isset($_POST['delete_key'])) {
        $key_index = $_POST['key_index'];
        if (isset($keys[$key_index])) {
            unset($keys[$key_index]);
            $keys = array_values($keys);
            file_put_contents('keys.json', json_encode($keys));
        }
    }

    // Determinar la pestaña activa
    $active_tab = $_GET['tab'] ?? 'home';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Area</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            margin: 0;
            color: #fff;
            display: flex;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }
        /* Sidebar Styles */
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #ff4b5e, #cc0000);
            padding: 30px 0;
            text-align: left;
            border-radius: 0 15px 15px 0;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.3);
            animation: slideInLeft 0.7s ease-in-out;
            z-index: 10;
        }
        .sidebar h2 {
            color: #fff;
            margin-left: 25px;
            font-size: 1.6em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .sidebar a {
            display: block;
            color: #fff;
            padding: 15px 25px;
            text-decoration: none;
            font-size: 1.1em;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #00eaff;
        }
        /* Login Container Styles (Centered) */
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            width: 450px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            z-index: 20;
        }
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.7);
        }
        .login-container h1 {
            color: #00eaff;
            margin-bottom: 25px;
            font-size: 2.2em;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: glow 2s infinite alternate;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 85%;
            padding: 12px;
            margin: 12px 0;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1em;
            outline: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .login-container input[type="text"]::placeholder,
        .login-container input[type="password"]::placeholder {
            color: #aaa;
        }
        .login-container input[type="text"]:focus,
        .login-container input[type="password"]:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
        }
        .login-container button {
            padding: 12px 30px;
            background: #00eaff;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            text-transform: uppercase;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }
        .login-container button:hover {
            background: #00c4d4;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 234, 255, 0.4);
        }
        .back-link {
            margin-top: 20px;
            color: #00ffcc;
            text-decoration: none;
            font-size: 0.9em;
            padding: 10px 20px;
            background: #1e3c72;
            border-radius: 5px;
            transition: color 0.3s ease, background 0.3s ease;
            display: inline-block;
        }
        .back-link:hover {
            color: #00d4ff;
            background: #2a5298;
        }
        .error {
            color: #ff4444;
            margin-top: 15px;
            font-size: 0.9em;
            background: rgba(255, 68, 68, 0.1);
            padding: 8px;
            border-radius: 5px;
            animation: fadeIn 0.5s ease-in-out;
        }
        /* Content Styles */
        .content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            animation: fadeIn 0.7s ease-in-out;
        }
        .content h1 {
            text-align: center;
            color: #00eaff;
            margin-bottom: 25px;
            font-size: 2em;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: glow 2s infinite alternate;
        }
        .table-header, .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            margin-bottom: 8px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .table-row {
            background: rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .table-row:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        input, select {
            padding: 12px;
            margin: 8px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1em;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        input:focus, select:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
        }
        button {
            padding: 12px 30px;
            background: #00eaff;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            text-transform: uppercase;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }
        button:hover {
            background: #00c4d4;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 234, 255, 0.4);
        }
        .delete-btn {
            background: #ff4444;
        }
        .delete-btn:hover {
            background: #cc0000;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.4);
        }
        .logout-btn {
            background: #ff4444;
        }
        .logout-btn:hover {
            background: #cc0000;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.4);
        }
        .success-message {
            color: #00ffcc;
            margin-top: 15px;
            font-size: 1.1em;
            background: rgba(0, 255, 204, 0.1);
            padding: 10px;
            border-radius: 8px;
            animation: fadeIn 0.5s ease-in-out;
        }
        .key-display {
            color: #00eaff;
            font-size: 1.2em;
            margin-top: 15px;
            word-break: break-all;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease-in-out;
        }
        .key-item {
            margin: 10px 0;
        }
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        @keyframes glow {
            from { text-shadow: 0 0 5px #00eaff, 0 0 10px #00eaff; }
            to { text-shadow: 0 0 10px #00eaff, 0 0 20px #00eaff, 0 0 30px #00eaff; }
        }
    </style>
</head>
<body>
    <?php if (!$_SESSION['admin_logged_in']): ?>
        <div class="login-container">
            <h1>Admin Login</h1>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Nombre de usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit" name="admin_login">Iniciar sesión</button>
            </form>
            <a href="index.php" class="back-link">Volver al login principal</a>
        </div>
    <?php else: ?>
        <div class="sidebar">
            <h2>Admin Area</h2>
            <a href="admin.php?tab=home" class="<?php echo $active_tab === 'home' ? 'active' : ''; ?>">Home</a>
            <a href="admin.php?tab=users" class="<?php echo $active_tab === 'users' ? 'active' : ''; ?>">Users</a>
            <a href="admin.php?tab=settings" class="<?php echo $active_tab === 'settings' ? 'active' : ''; ?>">Settings</a>
            <a href="admin.php?tab=logout" class="<?php echo $active_tab === 'logout' ? 'active' : ''; ?>">Logout</a>
        </div>
        <div class="content">
            <?php if ($active_tab === 'home'): ?>
                <h1>Admin Dashboard</h1>
                <div class="form-group">
                    <h2>Create Key</h2>
                    <form method="POST">
                        <input type="text" name="username" placeholder="Username" required>
                        <select name="duration">
                            <option value="7">7 Days</option>
                            <option value="30">30 Days</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                        <button type="submit" name="create_key">Create</button>
                    </form>
                    <?php if ($success_message): ?>
                        <div class="success-message"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($new_key_display)): ?>
                        <div class="key-display">New Key: <?php echo htmlspecialchars($new_key_display); ?> <button onclick="navigator.clipboard.writeText('<?php echo $new_key_display; ?>')">Copy</button></div>
                    <?php endif; ?>
                </div>
            <?php elseif ($active_tab === 'users'): ?>
                <h1>Users</h1>
                <div class="table-header">
                    <span>Username</span>
                    <span>Key</span>
                    <span>Expires In</span>
                </div>
                <?php foreach ($keys as $index => $key): ?>
                    <div class="table-row key-item">
                        <span><?php echo htmlspecialchars($key['username']); ?></span>
                        <span><?php echo htmlspecialchars($key['key']); ?></span>
                        <span><?php echo $key['expired'] ? 'Expired (' . $key['expiry'] . ')' : $key['expiry']; ?></span>
                        <form method="POST" style="display:inline;" class="delete-form">
                            <input type="hidden" name="key_index" value="<?php echo $index; ?>">
                            <button type="submit" name="delete_key" class="delete-btn">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($active_tab === 'settings'): ?>
                <h1>Settings</h1>
                <p>Settings page coming soon!</p>
            <?php elseif ($active_tab === 'logout'): ?>
                <h1>Logout</h1>
                <form method="POST">
                    <button type="submit" name="logout" class="logout-btn">Cerrar sesión</button>
                </form>
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
                    session_destroy();
                    header("Location: admin.php");
                    exit;
                }
                ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
