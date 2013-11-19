<? if ($user_loggedin == '1'):?>
Buongiorno <strong><?=$username;?></strong><br/><br/>
Utente: <?=$userid;?><br/>
<a href="<?=$baseurl;?>user/logout">Clicca qui</a> per effettuare il logout.<br/>
<a href="<?=$baseurl;?>user/changepassword">Clicca qui</a> per cambiare la password.<br/>

<? else: ?>
<a href="<?=$baseurl;?>user/login">Clicca qui</a> per effettuare il login.
<? endif; ?>