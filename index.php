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

$end_message = "The event has ended!";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link type="text/css" rel="stylesheet" href="mockup-index_files/style.html">
    <title>The Charity Chain Experiment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="http://www.w3schools.com/lib/w3.css">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Roboto'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>* {font-family: "Helvetica", sans-serif; line-height:20px; color:#444;}</style>
    <script type="text/javascript">
<?php
echo <<<"END"
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
                  text = days + " days " + hours + "h " + minutes + "m " + seconds + "s";
                  document.querySelector('#clock1').textContent =
                  document.querySelector('#clock2').textContent = text;
              } else {
                  document.querySelector('#countdown1').textContent =
                  document.querySelector('#countdown2').textContent =
                      '{$end_message}';
              }
          }, 1000);
      }

      window.onload = function() {
          startTimer();
      };

      function load_it() {
          document.getElementById("testload").innerHTML='<object type="text/html" data="new-charity.php?url=\"http://localhost\""></object>';
      }
END;
?>
    </script>
  </head>

  <body class="w3-indigo">
    <div class="w3-content w3-margin-top w3-padding w3-white w3-card-4 w3-padding-32" style="max-width:900px;">

      <div class="w3-row">
        <div class="w3-twothird">
          <h1 class="title">The Charity Chain</h1>
          <h3 class="subtitle w3-padding-8">$<?php echo money_format("%i", $total_value); ?> donated so far!</h3>
          <div class="w3-hide-medium w3-hide-large w3-text-gray">
            <div id="countdown1">Time until event ends: <div id="clock1" style="display:inline;">00 days 00h 00m 00s</div> </div>
          </div>
        </div>

        <div class="w3-third w3-padding-16 w3-hide-small" style="text-align:center;">
          <div id="countdown2">Time until event ends<h4><div id="clock2">00 days 00h 00m 00s</div></h4> </div>
        </div>

        <div>
          <div style="margin-top:30px;" class="w3-hide-small">
            <a class="w3-button w3-hover-light-blue" onclick="document.getElementById('about').style.display='block'">About CCE</a>
<?php
if($countdown > 0) echo <<<"END"
            <a class="w3-button w3-hover-light-blue" onclick="document.getElementById('invite1').style.display='block'">Send an invitation</a>
END;
if($email) echo <<<"END"
            <div style="display:inline;margin-left:20px;">Welcome {$email}</div> (<a href="logout.php">logout</a>)
END;
?>
          </div>
          <div class="w3-hide-medium w3-hide-large">
            <div class="w3-row">
              <div class="w3-section w3-bottombar w3-border-light-blue"></div>
            </div>
<?php
if($email) echo <<<"END"
            <div>Welcome {$email}</div> (<a href="logout.php">logout</a>)
END;
?>
            <a onclick="document.getElementById('id01').style.display='block'">About CCE</a>
<?php
if($countdown > 0) echo <<<"END"
            <br><a onclick="document.getElementById('invite1').style.display='block'">Send an invitation</a>
END;
?>
          </div>
        </div>
      </div>

      <div class="w3-row">
        <div class="w3-section w3-bottombar w3-border-light-blue"></div>
      </div>

      <div class="w3-section">
        <div>

          <p>This is no ordinary chain letter. By donating a small amount to
            your favorate charity, and inviting others to do the same, you
            begin a cascade of giving greater than you can achieve by working
            alone. You can make a diference: DON'T BREAK THE CHAIN!</p>

          <p>Three easy steps:</p><ol>

            <li>Make a donation to a charity listed below, or add your own</li>

            <li>Tell us about your donation, so everyone can see the cumulative
              effect of all this giving</li>

            <li>Invite FIVE of your friends to also participate in the Charity Chain
              [<a onclick="document.getElementById('invite5').style.display='block'">INVITE FRIENDS</a>]</li>

          </ol>
        </div>
      </div>

      <div class="w3-row">
          <div class="w3-section w3-bottombar w3-border-light-blue"></div>
      </div>

<?php
$rows = array();
foreach($donations as $domain => $value) {
    if($value <= 0.0) { continue; }
    $c = $charities[$domain];
    if(!$c) { continue; }

    $value = money_format("%i", $c->value);

    $rows[] = <<<"END"
          <div class="w3-row">
              <div class="w3-half">
                <h4><b>{$c->name}</b></h4>
                <a href="{$c->url}">{$domain}</a>
END;
    if($c->url != $c->donate) $rows[] = <<<"END"
                (<a href="{$c->donate}">donate</a>)
END;
    $rows[] = <<<"END"
              </div>
              <div class="w3-half" style="text-align:right;">
              <form action="change-donation.php" method="post">
                $ <input type="number" name="value" value="{$value}" min="0" step="0.01" required style="width:120px">
                <input type="hidden" name="domain" value="{$domain}">
                <input type="submit" value="Update" class="w3-button w3-padding-4 w3-hover-light-blue" style="margin-top:5px;">
              </form>
              </div>
          </div>
END;
}

if(0 < count($rows)) {
    echo <<<"END"
        <div class="w3-section">

          <h5>Your Donations</h5>
          <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">
END;

    foreach($rows as $row) { echo $row; }

    echo <<<"END"
        </div>
END;
}
?>

<?php
$tims = array();
foreach($charities as $key => $rec) { $tims[$key] = $rec->timestamp; }
array_multisort($tims, SORT_DESC, SORT_NUMERIC, $charities);

$n = 0; $recs = array();
foreach($charities as $key => $rec) {
    if(++$n > 20) { break; }
    $recs[$key] = $rec;
}

$vals = array(); $tims = array();
foreach($recs as $key => $rec) {
    $tims[$key] = $rec->timestamp;
    $vals[$key] = $rec->value;
}
array_multisort($vals, SORT_DESC, SORT_NUMERIC,
                $tims, SORT_DESC, SORT_NUMERIC,
                $recs);

$rows = array();
foreach($recs as $domain => $c) {

    $value = money_format("%i", $c->value);

    $rows[] = <<<"END"
          <div class="w3-row">
              <div class="w3-quarter w3-padding-8">
              <h3><i class="fa fa-money w3-xlarge w3-text-green"></i> &dollar;{$value}</h3>
              </div>
              <div class="w3-half">
                <h4><b>{$c->name}</b></h4>
                <a href="{$c->url}">{$domain}</a>
END;
    if($c->url != $c->donate) $rows[] = <<<"END"
                (<a href="{$c->donate}">donate</a>)
END;
    $rows[] = <<<"END"
              </div>
              <div class="w3-quarter" style="text-align:right;">
              <form action="change-donation.php" method="post">
                $ <input type="number" name="value" min="0" step="0.01" required style="width:120px">
                <input type="hidden" name="domain" value="{$domain}">
                <input type="submit" value="Add" class="w3-button w3-padding-4 w3-hover-light-blue" style="margin-top:5px;">
              </form>
              </div>
          </div>
END;
}

if(0 < count($rows)) {
    echo <<<"END"
        <div class="w3-section">

          <h5>Popular Charities</h5>
          <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">
END;

    foreach($rows as $row) { echo $row; }

    echo <<<"END"
        </div>
END;
}
?>

        <br>
        <form action="new-charity.php" method="post">
          <table><tbody><tr>
                <td align="right">Charity URL:</td>
                <td><input name="url" required="" type="url"></td>
              </tr><tr>
                <td colspan="2" align="center">
                  <input type="submit" value="Add Charity">
                </td>
          </tr></tbody></table>
        </form>
      </div>

      Sample code starts here!!!<br>
      <div id="testload"></div><br>
      <a href="javascript:load_it();">click me</a>

    </div>

    <div id="id01" class="w3-modal">
      <div class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
          <span onclick="document.getElementById('id01').style.display='none'" class="w3-closebtn">&times;</span>
          <p>Woah, Charity dude!</p>
          <p>Give some money, get some karma. Pass it on, dude.</p>
        </div>
      </div>
    </div>

    <div id="about" class="w3-modal">
      <div class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
          <span onclick="document.getElementById('about').style.display='none'" class="w3-closebtn">&times;</span>

          <div class="w3-section">
            <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">
              <div class="w3-row">

                <h5>About the Charity Chain Experiment</h5>

                <p>The Charity Chain Experiment (CCE) was conceived for a
                  leadership challenge assignment, part of a recent management
                  training course. The assignment was to take $100 start-up
                  capital, and generate a greater value of good deeds for the
                  community and the world. So I invested capital in a domain
                  name and other virtual supplies, and enlisted the help of
                  friends to develop this site.</p>

                <p>The site's purpose is to encourage people to make charitable
                  donations now, rather than waiting for later, or not donating
                  at all. It's not specific: There are many good charities out
                  there that need support. This site simply connects people to
                  worthy charities. And like a chain letter, it's membership
                  grows expoentially.</p>

                <p>We ask people to tell us how much they've donated, so we can
                  show the total value of charitable donations generated. You
                  can visit this site again later to see how the total has
                  grown. Like my management training course, this experiment
                  ends in April, after which it will stop growing and recording
                  new donations.</p>

                <p>Thank you for your support,<br>&mdash;Eric Schnarr, CCE
                  Inventor</p>

              </div>

              <hr>

              <div class="w3-row">
                <h5>People behind the CCE</h5>
              </div>

              <div class="w3-row">
                <div class="w3-half w3-padding-small w3-right-align">CCE Inventor &amp; Site Creator:</div>
                <div class="w3-half w3-padding-small">Eric Schnarr</div>
              </div>
              <div class="w3-row">
                <div class="w3-half w3-padding-small w3-right-align">Web Designer:</div>
                <div class="w3-half w3-padding-small">Glenn Loos-Austin</div>
              </div>
              <div class="w3-row">
                <div class="w3-half w3-padding-small w3-right-align">Web Hosting:</div>
                <div class="w3-half w3-padding-small">Brian Casey</div>
              </div>

              <hr>

              <div class="w3-row w3-center">
                <a href="mailto:contact@thecharitychain.org">contact@thecharitychain.org</a>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>

    <div id="invite1" class="w3-modal">
      <div class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
           <span onclick="document.getElementById('invite1').style.display='none'" class="w3-closebtn">&times;</span>
          <p>Woah, Charity dude!</p>
          <p>Give some money, get some karma. Pass it on, dude.</p>
        </div>
      </div>
    </div>

  </body>
</html>
