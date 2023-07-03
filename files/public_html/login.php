<?php

if (!defined('IN_INDEX')) { exit("This file can only be included in index.php."); }

if (isset($_GET['redirect'])) {
    $redirect = urldecode($_GET['redirect']);
} else {
    $redirect = '/';
}

if (isset($_POST['login_email']) && isset($_POST['login_password'])) {

    $stmt = DB::getInstance()->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $_POST['login_email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($_POST['login_password'], $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            TwigHelper::getInstance()->addGlobal('_session', $_SESSION);
            TwigHelper::addMsg('User has been logged in.', 'success');

            // Przekierowanie do strony z której się kliknęło przycisk login
            header('Location: ' . $redirect);
            exit();
        } else {
            TwigHelper::addMsg('Incorrect login credentials.', 'error');
        }
    } else {
        TwigHelper::addMsg('Incorrect login credentials.', 'error');
    }

}

print TwigHelper::getInstance()->render('login.html', ['redirect' => $redirect]);