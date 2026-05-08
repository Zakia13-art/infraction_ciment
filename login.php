<?php
session_start();

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Déjà connecté
if (isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

// Login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    // Identifiants à modifier ici
    if ($user === 'admin' && $pass === 'admin') {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infraction CIMAT - Connexion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 380px; }
        .login-box h1 { text-align: center; color: #333; margin-bottom: 30px; font-size: 24px; }
        .login-box p.sub { text-align: center; color: #888; margin-bottom: 25px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; color: #555; font-weight: 600; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-group input:focus { border-color: #667eea; }
        .btn-login { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .btn-login:hover { opacity: 0.9; }
        .error { color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🚛 Infraction CIMAT</h1>
        <p class="sub">Accès réservé</p>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Utilisateur</label>
                <input type="text" name="username" placeholder="Entrez votre identifiant" required autofocus>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Entrez votre mot de passe" required>
            </div>
            <button type="submit" class="btn-login">Se connecter</button>
        </form>
    </div>
</body>
</html>
