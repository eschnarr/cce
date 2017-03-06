<?php
function invitation($to="")
{
    if($to) {
        $enc_to = urlencode($to);
        $enc_auth = gen_auth($to);
        $link = <<<"END"
<a href="https://thecharitychain.org?email={$enc_to}&auth={$enc_auth}">
<button>Ticket to enter The Charity Chain</button></a>

END;
    } else {
        $link = "&lt;&lt;ticket&gt;&gt;";
    }

    return <<<"END"
<p>Welcome,</p>

<p>You've been invited to join The Charity Chain, an experiment in viral
  giving. By donating to your favorite charities, and inviting others to do the
  same, you will begin a cascade of donations greater than you achieve by
  working alone. You can make a diference.</p>

<p>Below is your personal ticket to join The Charity Chain. Click on the ticket
  get started.</p>

<p>{$link}</p>

<p>Thank You,<br>The Charity Chain</p>

END;
}
?>
