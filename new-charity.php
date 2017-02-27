<?php
    require_once "auth.php";
    require_once "db.php";

    if($auth) {

        $name = $_POST['name'];
        $url = $_POST['url'];
        $donate = $_POST['donate'];
        $value = $_POST['value'];

        $charities = load_charities();

        foreach($charities as &$c) {
            if($c->url == $url) { $c->value += $value; goto redirect; }
        }

        $charities[] = new Charity($name, $url, $donate, $value);

      redirect:
        save_charities($charities);
        header("Location: index.php");
        exit;
    }

    echo "Unauthorized";
?>
