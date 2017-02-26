<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
    <link type="text/css" rel="stylesheet" href="style.css">
    <link type="text/css" rel="stylesheet" href="flipclock/flipclock.css">
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="flipclock/flipclock.min.js"></script>
    <title>Charity Chain</title>
  </head>

  <body>
    <div class=header>
      <div class=title>The Charity Chain</div>
      <div class=invite> </div>

      <div class=clock></div>
      <script type="text/javascript">
        var clock;
        $(document).ready(function() {
        var clock;
        clock = $('.clock').FlipClock({
        clockFace: 'DailyCounter',
        autoStart: false,
        callbacks: {
        stop: function() { $('.clock').html('The clock has stopped!'); } }
        });
        clock.setTime(5);
        clock.setCountdown(true);
        clock.start();
        });
      </script>

      <div class=toolbar>
        <div class=welcome> </div>
        <div class=about> </div>
        <div class=donations> </div>
      </div>

      <p>This is no ordinary chain letter. By donating a small amount to your
        favorate charity, and inviting others to do the same, you begin a
        cascade of giving greater than your generosity alone. You can make a
        diference, so don't break the chain.</p>

      <p>Just follow these three easy steps:<ol>

          <li>Choose a charity from below or add your own, and make a
            donation</li>

          <li>Tell us about your donation, so everyone can see the cumulative
            effect of all this giving</li>

          <li>Invite 5 of your friends to also participate in the Charity
            Chain</li>

      </ol></p>

      <?php
         require_once "db.php";

         $charities = load_charities();

         foreach($charities as $key => $rec) {
             $keys[$key] = $key;
             $recs[$key] = $rec;
             $vals[$key] = $rec->value;
         }

         array_multisort($vals, SORT_DESC, SORT_NUMERIC,
                         $keys, SORT_ASC, SORT_NUMERIC,
                         $recs);

         echo "<table class=charity_table>", PHP_EOL;
         echo "<tr><th>Charity</th><th>Current Donations</th></tr>", PHP_EOL;
         foreach($recs as $c) {
             echo "<tr>", PHP_EOL;
             echo "<td class=charity_name>{$c->name}<br>", PHP_EOL;
             echo "<span class=charity_url>(<a href=\"{$c->url}\">{$c->url}</a>)</span></td>", PHP_EOL;
             echo "<td class=charity_value>{$c->value}</td>", PHP_EOL;
             echo "<td class=charity_donate><a href=\"{$c->donate}\" target=\"_blank\">donate</a></td>", PHP_EOL;
             echo "</tr>", PHP_EOL;
         }
         echo "</table>", PHP_EOL;

         ?>

    </div>
  </body>
</html>
