<form method="POST">
<? if ($user_loggedin == '0'): ?>
Username: <input type="string" name="username" /><br/>
<? endif; ?>
Vecchia password: <input type="password" name="oldpassword" /><br/>
Nuova password: <input type="password" name="newpassword1" /><br/>
Ripeti la nuova password: <input type="password" name="newpassword2" />
<input type="submit" name="changepassword" />
</form>

<? switch($change_password_response): ?>
<? case 'username_not_found': ?>
Utente errato.
<? break; ?>

<? case 'wrong_old_password': ?>
La vecchia password è errata.
<? break; ?>

<? case 'wrong_new_password': ?>
Le due nuove password non coincidono.
<? break; ?>

<? case 'new_password_same_as_old': ?>
La nuova password deve essere diversa da quella vecchia.
<? break; ?>

<? case 'ok': ?>
Password cambiata con successo. <? if ($user_logged_in == '0'): ?>Ora puoi effettuare il <a href="<?=$baseurl;?>user/login">login</a>.<? endif; ?>
<? endswitch; ?>