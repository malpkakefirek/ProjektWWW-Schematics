<?php

if (!defined('IN_INDEX')) { exit("This file can only be included in index.php."); }

if (isset($_GET['redirect'])) {
    $redirect = urldecode($_GET['redirect']);
} else {
    $redirect = '/';
}

if (isset($_POST['register_nickname']) && isset($_POST['register_email']) && isset($_POST['register_password'])) {

    $username = $_POST['register_nickname'];
    $email = $_POST['register_email'];
    $password = $_POST['register_password'];

    if ($username && preg_match('/^[a-zA-Z0-9\-\_\.]+\@[a-zA-Z0-9\-\_\.]+\.[a-zA-Z]{2,5}$/D', $email) && $password) {

        $recaptcha = new \ReCaptcha\ReCaptcha(CONFIG['recaptcha_private']);
        $resp = $recaptcha->setExpectedHostname('s413180.labagh.pl')
            ->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

        if ($resp->isSuccess()) {

            try {

                $stmt = DB::getInstance()->prepare('
                    INSERT INTO users (
                        username, email, password, created
                    ) VALUES (
                        :username, :email, :password, NOW()
                    )
                ');
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                TwigHelper::addMsg('Account has been created.', 'success');

                // Automatically login after register
                $stmt = DB::getInstance()->prepare("SELECT * FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    TwigHelper::getInstance()->addGlobal('_session', $_SESSION);
                    TwigHelper::addMsg('User has been logged in.', 'success');

                    header('Location: ' . $redirect);
                    exit();
                }

            } catch (PDOException $e) {
                TwigHelper::addMsg('The given email address is already taken.', 'error');
            }

        } else {
            TwigHelper::addMsg('Captcha was not solved correctly.', 'error');
        }
    } else {
        TwigHelper::addMsg('Not all data was provided or the data provided is incorrect.', 'error');
    }
}

print TwigHelper::getInstance()->render('register.html', ['redirect' => $redirect]);
