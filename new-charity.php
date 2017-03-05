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

    $url = !isset($_POST['url']) ? "" :
        filter_var($_POST['url'], FILTER_SANITIZE_URL);
    if($url && !strpos($url,"://")) { $url = "http://{$url}"; }
    $domain = get_domain($url);

    $name = !isset($_POST['name']) ? "" : trim($_POST['name']);
    $donate = !isset($_POST['donate']) ? "" :
        filter_var($_POST['donate'], FILTER_SANITIZE_URL);
    $value = !isset($_POST['value']) ? 0.0 :
        (float)filter_var($_POST['value'], FILTER_SANITIZE_NUMBER_FLOAT);
    if($value < 0.0) { $value = 0.0; }

    if($domain) {
        $do_update = $url && $name;

        $lock = fopen(LOCK_FILE, 'rw');
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

            if($value > 0.0) {
                $donations = load_donations($email);
                $donations[$domain] += $value;
                save_donations($email, $donations);
            }

            save_charities($charities);
            flock($lock, LOCK_UN);
            fclose($lock);

            header("Location: index.php");
            exit;
        }

        if($url && !$name) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            $s = curl_exec($ch);
            curl_close($ch);

            $matches = array();
            $pattern = "#<\s*title( [^>]*)?>\s*([^<]*)\s*<\s*/\s*title\s*>#i";
            if(preg_match($pattern, $s, $matches)) {
                $name = $matches[2];
            }
        }

        flock($lock, LOCK_UN);
        fclose($lock);
    }

    if($c) {
        if(!$url) { $url = $c->url; }
        if(!$name) { $name = $c->name; }
        if(!$donate && $c->donate != $c->url) { $donate = $c->donate; }
    } else {
        $c = new Charity();
    }
?>

<html><body>

<form action="new-charity.php" method="post">
<table><tr>
  <td align="right">Charity Name:</td>
  <td><input type="text" name="name" value="<?php echo "$name"; ?>" size="80" required></td>
</tr><tr>
  <td align="right">Charity URL:</td>
  <td><input type="text" name="url" value="<?php echo "$url"; ?>" size="80" required></td>
</tr><tr>
  <td align="right">URL for Donations:</td>
  <td><input type="url" name="donate" value="<?php echo "$donate"; ?>" size="80"></td>
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
