<?php
if($_SERVER['SERVER_NAME'] != "localhost" &&
   (!$_SERVER['HTTPS'] || $_SERVER['HTTPS'] == "off"))
{
    $redirect = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
    <title>Charity Chain Experiment</title>
  </head>

  <body>
    <table class=header><tr>
      <td width=200></td>
      <td width=600><center>
        <h1>The Charity Chain</h1>
        <h2>&ndash; Coming Soon &ndash;</h2>
      </center></td>
      <td width=200></td>
    </tr></table>
  </body>
</html>
