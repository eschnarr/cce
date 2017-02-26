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
        stop: function() {
          $('.clock').html('The clock has stopped!');
        }
      }
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
favorate charity, and inviting others to do the same, you begin a cascade of
giving greater than your generosity alone. You can make a diference, so don't
break the chain.</p>

<p>Just follow these three easy steps:<ol>
    <li>Choose a charity and make a donation</li>
    <li>Tell us about your donation, so everyone can see the cumulative effect of all this giving</li>
    <li>Invite 5 of your friends to also participate in the Charity Chain</li>
</ol></p>

<?php echo "hello world"; ?>

</body></html>
