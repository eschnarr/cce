<?php
    require_once "auth.php";
    require_once "db.php";

    if($countdown <= 0) {
        header("Location: index.php");
        exit;
    }

    if(!$auth) {
        header("Location: invite1.php");
        exit;
    }

    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
    $domain = get_domain($url);

    $name = trim($_POST['name']);
    $donate = filter_var($_POST['donate'], FILTER_SANITIZE_URL);
    $value = filter_var($_POST['value'], FILTER_SANITIZE_NUMBER_FLOAT);
    if($value < 0.0) { $value = 0.0; }

    if($domain) try {
        $do_update = $url && $name && $donate;

        $lock = fopen(LOCK_FILE, 'w');
        flock($lock, $do_update ? LOCK_EX : LOCK_SH);

        $charities = load_charities();

        $c = $charities[$domain];
        if(!$c) {
            $c = new Charity();
            $charities[$domain] = $c;
        }

        if($do_update) {
            $c->url = $url;
            $c->name = $name;
            $c->donate = $donate;
            if(!$c->donate) { $c->donate = $url; }
            $c->value += $value;

            save_charities($charities);
            header("Location: index.php");
            exit;
        }

    } finally {
        if($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    if($c) {
        if(!$url) { $url = $c->url; }
        if(!$name) { $name = $c->name; }
        if(!$donate) { $donate = $c->donate; }
        if(!$donate) { $donate = $url; }
    } else {
        $c = new Charity();
    }

    include "header.html";
?>

<html><body>

<form action="new-charity.php" method="post">
<table><tr>
  <td align="right">Charity Name:</td>
  <td><input type="text" name="name" value="<?php echo "$name"; ?>" required></td>
</tr><tr>
  <td align="right">Charity URL:</td>
  <td><input type="text" name="url" value="<?php echo "$url"; ?>" required></td>
</tr><tr>
  <td align="right">URL for Donations:</td>
  <td><input type="url" name="donate" value="<?php echo "$donate"; ?>" required></td>
</tr><tr>
  <td align="right"><?php
    if($c->value > 0.0) { echo "New"; } else { echo "Initial"; }
  ?> Donation:</td>
  <td>&dollar;<input type="number" name="value" step="0.01"
      value="<?php if($value > 0.0) { echo "$value"; } ?>"></td>
</tr><tr>
  <td colspan=2 align="center"><input type="submit" value="<?php
    if($c->url) { echo "Update"; } else { echo "Add"; }
  ?> Charity"></td>
</tr></table>
</form>

</body></html>

<?php
    include "footer.html";
?>