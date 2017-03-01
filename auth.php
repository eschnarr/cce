<?php

function gen_auth($email)
{
    $salt = "Anything to make the auth token harder to crack.";
    return sha1("{$salt}{$email}");
}

{
    $_req = array_merge($_COOKIE, $_POST, $_GET);
    $_email = $_req['email'];
    $_auth = $_req['auth'];
    if(gen_auth($_email) == $_auth) {
        global $email, $auth;
        $email = $_email;
        $auth = $_auth;
    }

    global $countdown;
    $now = new DateTime();
    $end = new DateTime("2017-04-15");
    $countdown = $end->getTimestamp() - $now->getTimestamp();

}

?>
