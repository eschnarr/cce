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

require_once "invitation.php";

if(isset($_GET['logout'])) {
    unset($email); unset($auth);
    setcookie('email', "", 0);
    setcookie('auth', "", 0);

    header('HTTP/1.1 303 See Other');
    header("Location: .");
    exit;

} else {
    if($auth) {
        if(!isset($_COOKIE) || !isset($_COOKIE['auth']) ||
           $auth!=$_COOKIE['auth']) { write_log('login',array($email)); }
        setcookie('email', $email);
        setcookie('auth', $auth);
    }
}

$message = "";
$message_state = 'none';
$new_charity_state = 'none';
if(isset($_GET) && isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
}

$domain = ""; $name = ""; $url = ""; $donate = "";
if($auth && $countdown > 0 && isset($_POST['new-charity'])) {
    $url = !isset($_POST['url']) ? "" :
        filter_var($_POST['url'], FILTER_SANITIZE_URL);
    if($url && !strpos($url,"://")) { $url = "http://{$url}"; }
    $domain = get_domain($url);

    $name = !isset($_POST['name']) ? "" : trim($_POST['name']);
    $donate = !isset($_POST['donate']) ? "" :
        filter_var($_POST['donate'], FILTER_SANITIZE_URL);
    $value = !isset($_POST['value']) ? 0.0 :
        (float)filter_var($_POST['value'],
                          FILTER_SANITIZE_NUMBER_FLOAT,
                          FILTER_FLAG_ALLOW_FRACTION |
                          FILTER_FLAG_ALLOW_THOUSAND);
    if($value < 0.0) { $value = 0.0; }
}

$do_update = $domain && $url && $name;

$lock = fopen(LOCK_FILE, 'rw');
flock($lock, $do_update ? LOCK_EX : LOCK_SH);

$charities = load_charities();
$donations = $email ? load_donations($email) : array();

$newC = NULL;
$newC_exists = FALSE;
if($domain) {

    $newC = isset($charities[$domain]) ? $charities[$domain] : FALSE;
    if(!$newC) { $newC = new Charity(); }
    else { $newC_exists = TRUE; }

    $new_charity_state = 'block';

    if($do_update) {
        $newC->url = $url;
        $newC->name = $name;
        $newC->donate = $donate;
        if(!$newC->donate) { $newC->donate = $url; }
        $old = $newC->value; $newC->value += $value;

        write_log('donate', array($email,$domain,$old,$newC->value,'add'));

        $message = "Charity saved.";
        if($value > 0.0) {
            $donations[$domain] += $value;
            save_donations($email, $donations);

            $message .= " $".money_format("%i",$value)." donation recorded.";
            $message .= " Don't forget to visit {$newC->name} to actually make your donation.";
        }

        $charities[$domain] = $newC;
        save_charities($charities);
    }
}

flock($lock, LOCK_UN);
fclose($lock);

if($domain && $url && !$name) {
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

if($newC) {
    if(!$url) { $url = $newC->url; }
    if(!$name) { $name = $newC->name; }
    if(!$donate && $newC->donate != $newC->url) { $donate = $newC->donate; }
} else {
    $newC = new Charity();
}

$total_value = 0.0;
foreach($charities as $c) {
    $total_value += $c->value;
}

$sitekey = "6LdtohcUAAAAABL79g-YlLX7xGELUVKZatW4W4uh";

$subject = $email
    ? "An invitation from {$email}"
    : "Invitation to join The Charity Chain";
if(isset($_POST) && isset($_POST['subject'])) {
    $subject = trim($_POST['subject']);
}

if($message) {
    $new_charity_state = 'none';
    $message_state = 'block';
}

$contact = <<<"END"
<a href="mailto:contact@thecharitychain.org">contact@thecharitychain.org</a>
END;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>The Charity Chain Experiment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Roboto'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
      * {font-family: "Helvetica", sans-serif; line-height:20px; color:#444;}
      a {color:#0000CC;}
    </style>
    <script type="text/javascript">
      var CaptchaCallback = function() {
          grecaptcha.render('RecaptchaField1', {'sitekey' : '<?php echo $sitekey; ?>'});
          grecaptcha.render('RecaptchaField2', {'sitekey' : '<?php echo $sitekey; ?>'});
       };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=CaptchaCallback&render=explicit" async defer></script>
    <script type="text/javascript">
<?php
echo <<<"END"
      var timer={$countdown};
      function update_clock() {
          var days, hours, minutes, seconds;
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
              "The event has ended!";
          }
      }

      window.onload = function() {
          setInterval(update_clock, 1000);
          update_clock();
      };

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
            <div id="countdown1">This event ends in <div id="clock1" style="display:inline;">00 days 00h 00m 00s</div> </div>
          </div>
        </div>

        <div class="w3-third w3-padding-16 w3-hide-small" style="text-align:center;">
          <div id="countdown2">This event ends in <h4><div id="clock2">00 days 00h 00m 00s</div></h4> </div>
        </div>

        <div>
          <div style="margin-top:30px;" class="w3-hide-small">
            <a class="w3-button w3-hover-light-blue" onclick="document.getElementById('about').style.display='block'">About CCE</a>
<?php
if($countdown > 0) echo <<<"END"
            <a class="w3-button w3-hover-light-blue" onclick="document.getElementById('invite1').style.display='block'">Send an invitation</a>

END;
if($email) echo <<<"END"
            <div style="display:inline;margin-left:20px;">Welcome {$email}</div> (<a href="?logout">logout</a>)

END;
?>
          </div>
          <div class="w3-hide-medium w3-hide-large">
            <div class="w3-row">
              <div class="w3-section w3-bottombar w3-border-light-blue"></div>
            </div>
<?php
if($email) echo <<<"END"
            <div style="display:inline;">Welcome {$email} (<a href="?logout">logout</a>)</div><br>

END;
?>
            <a href="#" onclick="document.getElementById('about').style.display='block'">About CCE</a>
<?php
if($countdown > 0) echo <<<"END"
            <br><a href="#" onclick="document.getElementById('invite1').style.display='block'">Send an invitation</a>

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

          <p>Hello Good Samaritan,</p>

<?php
if($auth) {
    echo <<<"END"
          <p>You've been invited to join <b>The Charity Chain</b>, an
            experiment in viral giving. By donating to your favorite charities,
            and inviting others to do the same, you will begin a cascade of
            giving greater than you achieve by working alone. You can make a
            difference, and that difference continues to grow as you reach out
            to family and friends. <i>So let's not break the chain!</i></p>

          <p>Three easy steps:</p><ol>

            <li>Make a <b>donation</b> to a charity listed below, or
              <a href="#add-charity">add your own</a></li>

            <li>Tell us about your donations, so we can show their cumulative
              effect</li>

            <li><a href="#" onclick="document.getElementById('invite5').style.display='block'">
              Invite FIVE of your friends</a> to also participate in <b>The Charity Chain</b><br>
              <small>(Note: You ticket is linked to your email, so use this site to send new invitations.)</small>

          </ol>

END;
} else {
    echo <<<"END"
          <p>The Charity Chain is an experiment in viral giving.  By donating
            to your favorite charities, and inviting others to do the same, you
            can begin a cascade of giving greater than you achieve by working
            alone. You can make a difference!</p>

          <p class="w3-center">
            <a href="#" onclick="document.getElementById('invite1').style.display='block'">
              Send yourself an invitation</a> to get started.</p>

END;
}
?>
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

    $value = money_format("%i", $value);
    $rows[] = <<<"END"
          <div class="w3-row">
            <div class="w3-half">
              <h4 style="margin-bottom:0px"><b>{$c->name}</b></h4>
              <a href="{$c->url}" target="_blank">{$domain}</a>

END;
    if($c->url != $c->donate) $rows[] = <<<"END"
              (<a href="{$c->donate}" target="_blank">donate</a>)

END;
    $rows[] = <<<"END"
            </div>
            <div class="w3-half" style="text-align:right;">
              <form action="change-donation.php" method="post">
                $ <input type="number" name="value" value="{$value}" min="0" step="0.01" required style="width:120px">
                <input type="hidden" name="domain" value="{$domain}">
                <input type="submit" value="Change" class="w3-button w3-padding-4 w3-hover-light-blue" style="margin-top:5px;">
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

END;
    if($auth) {
        $rows[] = <<<"END"
            <div class="w3-half">

END;
    } else {
        $rows[] = <<<"END"
            <div class="w3-threequarter">

END;
    }
    $rows[] = <<<"END"
              <h4 style="margin-bottom:0px"><b>{$c->name}</b></h4>
              <a href="{$c->url}" target="_blank">{$domain}</a>

END;
    if($c->url != $c->donate) $rows[] = <<<"END"
              (<a href="{$c->donate}" target="_blank">donate</a>)

END;
    $rows[] = <<<"END"
            </div>

END;
    if($auth) $rows[] = <<<"END"
            <div class="w3-quarter" style="text-align:right;">
              <form action="record-donation.php" method="post">
                $ <input type="number" name="value" min="0" step="0.01" required style="width:120px">
                <input type="hidden" name="domain" value="{$domain}">
                <input type="submit" value="Add" class="w3-button w3-padding-4 w3-hover-light-blue" style="margin-top:5px;">
              </form>
            </div>

END;
    $rows[] = <<<"END"
          </div>

END;
}

if(0 < count($rows)) {
    echo <<<"END"
      <div class="w3-section">

        <div class="w3-row">
          <h5 class="w3-quarter">Popular Charities</h5>

END;
    if($auth) {
        echo <<<"END"
          <div class="w3-half w3-hide-small"><div style="margin-top:10px">&darr;&nbsp;Go&nbsp;to&nbsp;the&nbsp;charity&nbsp;web&nbsp;site&nbsp;to&nbsp;donate&nbsp;&darr;</div></div>
          <div class="w3-quarter w3-center w3-hide-small"><div style="margin-top:10px">&darr;&nbsp;Record&nbsp;donation&nbsp;&darr;</div></div>

END;
    }
    echo <<<"END"
        </div>
        <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">

END;

    foreach($rows as $row) { echo $row; }

    echo <<<"END"
        </div>
      </div>

END;
}
?>

<?php
if($auth) {
    echo <<<"END"
      <div id="add-charity" class="w3-section w3-center">
        <div class="w3-content" style="max-width:480px">
          Enter the address of a charity's web site below to add it to the
          list. This form can also be used to update a charity that is already
          on the list.
        </div>
        <form action="." method="post">
          <input type="hidden" name="new-charity">
          Charity URL: <input type="text" name="url" required>
          <input type="submit" value="Add a charity">
        </form>
      </div>

END;
}
?>

      <div class="w3-row">
        <div class="w3-section w3-bottombar w3-border-light-blue"></div>
      </div>
      <div class="w3-row w3-center"><?php echo "{$contact}"; ?></div>

    </div>

    <div id="about" class="w3-modal" onclick="closeIfOutside('aboutContainer')">
      <div id="aboutContainer" class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
          <div class="w3-section">
            <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">
              <div class="w3-row">

                <h4>About the Charity Chain Experiment
                  <span onclick="document.getElementById('about').style.display='none'" class="w3-closebtn w3-xxlarge w3-text-grey w3-hover-text-red" style="margin-top:-0.15em;">&times;</span>
                </h4>

                <p>The <b>Charity Chain Experiment</b> (CCE) was conceived as
                  part of a leadership challenge assignment in a management
                  training course. The assignment was to take $100 start-up
                  capital, and generate a greater value of good deeds for the
                  community and the world. So I invested capital in a domain
                  name and other virtual supplies, and enlisted the help of
                  friends to develop this site.</p>

                <p>The site's purpose is to <b>encourage people to make
                  charitable donations now</b>, rather than waiting for later,
                  or not donating at all. It's not specific: There are many
                  good charities out there that need support. This site simply
                  connects people to worthy charities. And like a chain letter,
                  it's membership grows expoentially.</p>

                <p>We ask people to <b>tell us how much they've donated</b>, so
                  we can show the total value of charitable donations
                  generated. You can visit this site again later to see how the
                  total has grown.</p>

                <p>Thank you for your support,<br>Eric Schnarr &ndash; CCE
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

              <div class="w3-row w3-center"><?php echo "{$contact}"; ?></div>

            </div>
          </div>

        </div>
      </div>
    </div>

    <div id="invite1" class="w3-modal">
      <div class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
          <form action="invite.php" method="post">

            <div class="w3-section">
              <div class="w3-row">

                <h4>Invite Someone
                  <div class="w3-tooltip" style="display:inline;">
                    <span onclick="document.getElementById('invite1').style.display='none'" class="w3-closebtn w3-xxlarge w3-text-grey w3-hover-text-red" style="margin-top:-0.15em;">&times;</span>
                  </div>
                </h4>

                <p>Use this form to invite someone to join <b>The Charity
                  Chain</b>. Or use it to re-invite yourself, if you lost your
                  invitation. Simply enter an email address below, click send,
                  and a message like the one shown will be sent.</p>

                <hr>
              </div>

              <div class="w3-row" align="center">
                <div id="RecaptchaField1"></div>
              </div>

              <div class="w3-row w3-padding-16">
                <div class="w3-threequarter">
                  <div class="w3-row">
                    <div style="text-align:right;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-small">To:</div>
                    <div style="text-align:left;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-medium w3-hide-large">To:</div>
                    <input type="email" name="to1" size=38>
                  </div>

                  <div class="w3-row w3-padding-4">
                    <div style="text-align:right;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-small">Subject:</div>
                    <div style="text-align:left;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-medium w3-hide-large">Subject:</div>
                    <input type="text" name="subject" value="<?php echo "{$subject}"; ?>" size=38>
                  </div>
                </div>

                <div class="w3-quarter" align="center">
                  <input type="submit" class="w3-button w3-hover-light-blue" value="Send" style="margin-top:10px;">
                </div>
              </div>

              <div class="w3-row">
                <textarea name="note" maxlength="250" rows="4" style="width:100%; resize:none"
                          placeholder="Optionally add a personal message here. 250 characters max."
                          ></textarea>
              </div>

              <div class="w3-row">
                <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">

<?php echo invitation(); ?>

                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div id="invite5" class="w3-modal">
      <div class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
          <form action="invite.php" method="post">

            <div class="w3-section">
              <div class="w3-row">

                <h4>Send Invitations
                  <div class="w3-tooltip" style="display:inline;">
                    <span onclick="document.getElementById('invite5').style.display='none'" class="w3-closebtn w3-xxlarge w3-text-grey w3-hover-text-red" style="margin-top:-0.15em;">&times;</span>
                  </div>
                </h4>

                <p>Send invitations to up to <b>five of your friends.</b>
                  Simply enter their email addresses below, click send, and a
                  message like the one shown will be sent to each person.</p>

                <hr>
              </div>

              <div class="w3-row" align="center">
                <div id="RecaptchaField2"></div>
              </div>

              <div class="w3-row w3-padding-16">
                <div class="w3-threequarter">
<?php
for($i=0; $i<5; ++$i) {
    echo <<<"END"

                  <div class="w3-row w3-padding-4">
                    <div style="text-align:right;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-small">To:</div>
                    <div style="text-align:left;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-medium w3-hide-large">To:</div>
                    <input type="email" name="to{$i}" size=38>
                  </div>

END;
}
?>

                  <div class="w3-row w3-padding-4">
                    <div style="text-align:right;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-small">Subject:</div>
                    <div style="text-align:left;float:left;width:75px;margin-top:3px;margin-right:8px;" class="w3-text-grey w3-hide-medium w3-hide-large">Subject:</div>
                    <input type="text" name="subject" value="<?php echo "{$subject}"; ?>" size=38>
                  </div>
                </div>

                <div class="w3-quarter" align="center">
                  <input type="submit" class="w3-button w3-hide-small w3-hover-light-blue" value="Send" style="margin-top:80px;">
                  <input type="submit" class="w3-button w3-hide-medium w3-hide-large w3-hover-light-blue" value="Send" style="margin-top:10px;">
                </div>
              </div>

              <div class="w3-row">
                <textarea name="note" maxlength="250" rows="4" style="width:100%; resize:none"
                          placeholder="Optionally add a personal message here. 250 characters max."
                          ></textarea>
              </div>

              <div class="w3-row">
                <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">

<?php echo invitation(); ?>

                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div id="new-charity" class="w3-modal" style="<?php echo "display:{$new_charity_state}" ?>">
      <div class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
          <div class="w3-section">
            <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">
              <div class="w3-row">

                <h4>Add/Update Charity
                  <div class="w3-tooltip" style="display:inline;">
                    <a href="." class="w3-closebtn w3-xxlarge w3-text-grey w3-hover-text-red" style="margin-top:-0.15em;">&times;</a>
                  </div>
                </h4>

                <form action="." method="post">
                  <input type="hidden" name="new-charity">

                  <div class="w3-row-padding w3-padding-4">
                    <div class="w3-third w3-hide-small"><div class="w3-right">Charity Name:</div></div>
                    <div class="w3-third w3-hide-medium w3-hide-large">Charity Name</div>
                    <div class="w3-twothird">
                      <input style="width:100%" type="text" name="name" value="<?php echo "$name"; ?>" required>
                    </div>
                  </div>

                  <div class="w3-row-padding w3-padding-4">
                    <div class="w3-third w3-hide-small"><div class="w3-right">Charity Site URL:</div></div>
                    <div class="w3-third w3-hide-medium w3-hide-large">Charity Site URL</div>
                    <div class="w3-twothird">
                      <input style="width:100%" type="text" name="url" value="<?php echo "$url"; ?>" required>
                    </div>
                  </div>

                  <div class="w3-row-padding w3-padding-4">
                    <div class="w3-third w3-hide-small"><div class="w3-right">Donations Page URL:</div></div>
                    <div class="w3-third w3-hide-medium w3-hide-large">Donations Page URL</div>
                    <div class="w3-twothird">
                      <input style="width:100%" type="url" name="donate" value="<?php echo "$donate"; ?>">
                    </div>
                  </div>

                  <div class="w3-row-padding w3-padding-4">
                    <div class="w3-third w3-hide-small"><div class="w3-right"><?php echo ($c->value > 0.0 ? "New" : "Initial"); ?> Donation:</div></div>
                    <div class="w3-third w3-hide-medium w3-hide-large"><?php echo ($c->value > 0.0 ? "New" : "Initial"); ?> Donation</div>
                    <div class="w3-twothird">&dollar;
                      <input style="width:120px" type="number" name="value" step="0.01" value="<?php echo ($value > 0.0 ? "$value" : ""); ?>">
                    </div>
                  </div>

                  <div class="w3-row-padding w3-padding-4 w3-center">
                    <input type="submit" value="<?php echo ($newC_exists ?  "Update" : "Add"); ?> Charity">
                  </div>

                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="message" class="w3-modal" style="<?php echo "display:{$message_state}" ?>">
      <div class="w3-modal-content" style="width:600px;">
        <div class="w3-container">
          <div class="w3-section">
            <div class="w3-card-2 w3-white w3-padding w3-border w3-border-light-green">
              <div class="w3-row">

                <h4>Status Message
                  <div class="w3-tooltip" style="display:inline;">
                    <a href="." class="w3-closebtn w3-xxlarge w3-text-grey w3-hover-text-red" style="margin-top:-0.15em;">&times;</a>
                  </div>
                </h4>

                <p><?php echo "{$message}"; ?></p>

                <div class="w3-center">
                  <a href="."><button>Close</button></a>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </body>
</html>
