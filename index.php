<?php
    require_once "auth.php";
    require_once "db.php";

    if($email) { setcookie('email', $email); }
    if($auth) { setcookie('auth', $auth); }

    $charities = load_charities();

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
            $now = new DateTime();
            $end = new DateTime("2017-04-15");
            $delay = $end->getTimestamp() - $now->getTimestamp();
            $clock_stopped = "The event has ended!";
            if($delay <= 0) { echo $clock_stopped; }
            else echo <<<"END"
<p><a href="invite1.php">Send an invitation</a></p>
Time until event ends:<div class=clock>00 days 00h 00m 00s
<script type="text/javascript">
  function startTimer() {
    var timer = {$delay}, days, hours, minutes, seconds;
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
</script></div>
END;
          ?>
      </div></td>

    </tr><tr>

      <td colspan=2><div class=welcome><?php
          if($email) { echo "Welcome {$email}"; }
          else echo <<<"END"
<a href="invite1.php">Send yourself an invitation</a>
END;
        ?>
      </div></td>

      <td><div class=about>
          <a href="about.php">About CCE</a>
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

      <li>Invite FIVE of your friends to also participate in the Charity
        Chain [<a href="invite5.php">INVITE FRIENDS</a>]</li>

    </ol></p>

    <?php

      foreach($charities as $key => $rec) {
          $keys[$key] = $key;
          $recs[$key] = $rec;
          $vals[$key] = $rec->value;
      }

      array_multisort($vals, SORT_DESC, SORT_NUMERIC,
                      $keys, SORT_ASC, SORT_NUMERIC,
                      $recs);

      echo "<table class=charity-table>", PHP_EOL;
      echo "<tr><th>Charity</th><th>Current Donations</th></tr>", PHP_EOL;
      foreach($recs as $c) {
          echo <<<"END"
<tr>
  <td class=charity-name>{$c->name}<br><span class=charity-url>
    (<a href="{$c->url}">{$c->url}</a>)</span></td>
  <td class=charity-value align="center">&dollar;{$c->value}</td>
END;
          if($auth && $delay > 0) echo <<<"END"
  <td class=charity-donate><a href="{$c->donate}" target="_blank">DONATE</a></td>
  <td class=charity-record><form action="record-donation.php" method="post">
    &dollar;<input type="number" name="amount" min="0" step="0.01" required>
    <input type="submit" value="Record Donation">
END;
          echo <<<"END"
  </form>
</tr>
END;
      }

      echo "</table>", PHP_EOL;

      if($auth) {
          echo <<<"END"
<br>
<form action="new-charity.php" method="post">
<table><tr>
  <td align="right">Charity Name:</td>
  <td><input type="text" name="name" required></td>
  <td align="right">Web Site URL:</td>
  <td><input type="url" name="url" required></td>
</tr><tr>
  <td align="right">URL for Donations:</td>
  <td><input type="url" name="donate" required></td>
  <td align="right">Initial Donation:</td>
  <td>&dollar;<input type="number" name="value" step="0.01"></td>
</tr><tr>
  <td colspan=4 align="center"><input type="submit" value="Add Charity"></td>
</tr></table>
</form>
END;
      }

    ?>
    </td></tr></table>

  </body>
</html>
