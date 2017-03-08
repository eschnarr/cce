<?php

function gen_auth($email)
{
    $salt = "Anything to make the auth token harder to crack.";
    return sha1("{$salt}{$email}");
}

{
    global $email, $auth;
    $email = $auth = "";

    $_email = $_auth = "";
    $_req = array_merge($_COOKIE, $_POST, $_GET);
    if(is_array($_req)) {
        if(isset($_req['email'])) {
            $_email = filter_var($_req['email'], FILTER_SANITIZE_EMAIL);
        }
        if(isset($_req['auth'])) {
            $_auth = trim($_req['auth']);
        }
    }

    if($_email && gen_auth($_email) == $_auth) {
        $email = $_email;
        $auth = $_auth;
    }

    global $countdown;
    $now = new DateTime();
    $end = new DateTime("2017-05-01");
    $countdown = $end->getTimestamp() - $now->getTimestamp();

}

?>
