<?php
if($_SERVER['SERVER_NAME'] != "localhost" &&
   (!$_SERVER['HTTPS'] || $_SERVER['HTTPS'] == "off"))
{
    $redirect = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}

require_once "auth.php";
require_once "db.php";

if($email) { setcookie('email', $email); }
if($auth) { setcookie('auth', $auth); }

$lock = fopen(LOCK_FILE, 'rw');
flock($lock, LOCK_SH);

$charities = load_charities();
$donations = $email ? load_donations($email) : array();

flock($lock, LOCK_UN);
fclose($lock);

$total_value = 0.0;
foreach($charities as $c) {
    $total_value += $c->value;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
    <link type="text/css" rel="stylesheet" href="style.css">
    <title>Charity Chain Experiment</title>
  </head>

  <body>
    <table class=header><tr>

      <td width=200></td>

      <td width=600><center>
        <h1 class=title>The Charity Chain</h1>
        <h2 class=subtitle>$<?php echo $total_value; ?> donated so far!</h2>
      </center></td>

      <td width=200><div class=cce-status><?php
$clock_stopped = "The event has ended!";
if($countdown <= 0) { echo $clock_stopped; }
else echo <<<"END"
<p><a href="invite.php?n=1">Send an invitation</a></p>
Time until event ends:<div class=clock>00 days 00h 00m 00s
<script type="text/javascript">
  function startTimer() {
    var timer = {$countdown}, days, hours, minutes, seconds;
    setInterval(function() {
        days = parseInt(timer / (60 * 60 * 24), 10);
        hours = parseInt(timer / (60 * 60) % 24, 10);
        minutes = parseInt(timer / 60 % 60, 10);
        seconds = parseInt(timer % 60, 10);

        days = days < 10 ? "0" + days : days;
        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        if(--timer >= 0) {
          document.querySelector('.clock').textContent =
              days + " days " + hours + "h " + minutes + "m " + seconds + "s";
        } else {
          document.querySelector('.cce-status').textContent =
              '{$clock_stopped}';
        }
    }, 1000);
  }

  window.onload = function() {
    startTimer();
  };

  function load_it() {
      document.getElementById("testload").innerHTML='<object type="text/html" data="new-charity.php?url=\"http://localhost\""></object>';
  }
</script></div>
END;
?>
      </div></td>

    </tr><tr>

      <td colspan=2><div class=welcome><?php
if($email) { echo "Welcome {$email}"; }
else echo <<<"END"
<a href="invite.php?n=1">Send yourself an invitation</a>
END;
?>
      </div></td>

      <td><div class=about>
          <a href="about.html">About CCE</a>
      </div></td>

    </tr></table>

    <table><tr><td width=1000>

    <hr>

    <p>This is no ordinary chain letter. By donating a small amount
      to your favorate charity, and inviting others to do the same, you begin a
      cascade of giving greater than you can achieve by working alone. You can
      make a diference: DON'T BREAK THE CHAIN!</p>

    <p>Three easy steps:<ol>

      <li>Make a donation to a charity listed below, or add your own</li>

      <li>Tell us about your donation, so everyone can see the cumulative
        effect of all this giving</li>

      <li>Invite FIVE of your friends to also participate in The Charity
        Chain [<a href="invite.php?n=5">INVITE FRIENDS</a>]</li>

    </ol></p>

    <?php
$text = array();
foreach($donations as $domain => $value) {
    $c = $charities[$domain];
    if(!$c) { continue; }

    $text[] = <<<"END"
</tr><tr>
  <td class=charity-name>{$c->name}<br><span class=charity-url>
    (<a href="{$c->url}">{$domain}</a>)</span></td>
  <td class=charity-record><form action="change-donation.php" method="post">
    &dollar;<input type="number" name="value" min="0" step="0.01" value="{$value}" size="10" required>
    <input type="hidden" name="domain" value="{$domain}">
    <input type="submit" value="Change">
    </form></td>
END;
    if($auth && $countdown > 0) $text[] = <<<"END"
  <td class=charity-donate><a href="{$c->donate}" target="_blank">DONATE</a></td>
END;
}

echo "<table class=charity-table><tr>", PHP_EOL;
if(0 < count($text)) {
    echo "<th colspan=4>Your Donations</th>", PHP_EOL;
    echo "</tr><tr><th width=400>Charity</th><th>Donation</th>", PHP_EOL;
    foreach($text as $t) { echo $t; }
}

foreach($charities as $key => $rec) { $tims[$key] = $rec->timestamp; }
array_multisort($tims, SORT_DESC, SORT_NUMERIC, $charities);

$n = 0; $recs = array();
foreach($charities as $key => $rec) {
    if(++$n > 20) { break; }
    $recs[$key] = $rec;
}

foreach($recs as $key => $rec) { $vals[$key] = $rec->value; }
array_multisort($vals, SORT_DESC, SORT_NUMERIC,
                $tims, SORT_DESC, SORT_NUMERIC,
                $recs);

echo "</tr><tr><th colspan=4>Popular Charities</th>", PHP_EOL;
echo "</tr><tr><th width=400>Charity</th><th>Donations</th>", PHP_EOL;

foreach($recs as $domain => $c) {
    echo <<<"END"
</tr><tr>
  <td class=charity-name>{$c->name}<br><span class=charity-url>
    (<a href="{$c->url}">{$domain}</a>)</span></td>
  <td class=charity-value style="text-align:center">&dollar;{$c->value}</td>
END;
    if($auth && $countdown > 0) echo <<<"END"
  <td class=charity-donate><a href="{$c->donate}" target="_blank">DONATE</a></td>
  <td class=charity-record><form action="record-donation.php" method="post">
    &dollar;<input type="number" name="value" min="0" step="0.01" required>
    <input type="hidden" name="domain" value="{$domain}">
    <input type="submit" value="Record Donation">
END;
    echo "</form></td>", PHP_EOL;
}

echo "</tr></table>", PHP_EOL;

if($auth) {
    echo <<<"END"
<br>
<form action="new-charity.php" method="post">
<table><tr>
  <td align="right">Charity URL:</td>
  <td><input type="text" name="url" required></td>
</tr><tr>
  <td colspan=2 style="text-align:center">
    <input type="submit" value="Add Charity">
  </td>
</tr></table>
</form>
END;
}
?>
    </td></tr></table>

    Sample code starts here!!!<br>
    <div id="testload"></div><br>
    <a href="javascript:load_it();">click me</a>

  </body>
</html>
