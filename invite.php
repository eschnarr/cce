<?php
require_once "auth.php";

function invitation($email="")
{
    if($email) {
        $link = "http://thecharitychain.org?email=" .
                urlencode($email) . "&auth=" . gen_auth($email);
    } else {
        $link = "";
    }

    return <<<"END"
<p>Welcome to the Charity Chain!</p>

<p>This is an experiment in viral giving. By donating a small amount to your
  favorate charity, and inviting others to do the same, you begin a cascade of
  donations greater than you can achieve by working alone. You can make a
  diference.</p>

<p>Below is your personal ticket to join The Charity Chain. Click on the ticket
  get started.</p>

<p><a href="{$link}">
  <button type="button">Ticket to enter The Charity Chain</button>
</a></p>

<p>Thank You,<br>The Charity Chain</p>
END;
}

if($countdown <= 0) {
    header("Location: index.php");
    exit;
}

$n = $_GET ? filter_var($_GET['n'], FILTER_SANITIZE_NUMBER_INT) : '';
if($n <= 0) { $n = 1; }

$found = FALSE;
foreach($_POST as $arg => $value) {
    if("email" == substr($arg,0,5)) {
        $to = filter_var($value, FILTER_SANITIZE_EMAIL);
        mail($to, "An Experiment in Charitable Giving", invitation($to));
        $found = TRUE;
    }
}

if($found) {
    header("Location: index.php");
    exit;
}
?>

<html><body>

<form action="invite.php?n={$n}" method="post">
<table width=800><tr>
<?php
    if($n <= 1) {
        echo <<<"END"
  <td colspan=3><p>Use this form to invite someone to join The Charity
    Chain. Or use it to re-invite yourself, if you lost your invitation. Simply
    enter an email address below, click send, and a message like the one shown
    will be sent.</p></td>
END;
    } else {
        echo <<<"END"
  <td colspan=3><p>Send invitations to {$n} of your friends. Simply enter
    their email addresses below, click send, and a message like the one shown
    will be sent to each person.</p></td>
END;
    }
?>
</tr><tr>
  <td>&nbsp;</td>
<?php
    for($i=1; $i<=$n; ++$i) {
        echo <<<"END"
</tr><tr>
  <td width=25>To:</td>
  <td><input type="email" name="email{$i}"></td>
END;
      if($i <= 1) {
          echo <<<"END"
  <td rowspan={$n}><input type="submit" value="Send"></td>
END;
      }
    }
?>
</tr><tr>
  <td colspan=3 width="800"><?php echo invitation(); ?></td>
</tr></table>
</form>

</body></html>
