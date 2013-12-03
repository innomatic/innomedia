<form method="POST">
Email: <input type="string" name="username" value="<?=htmlentities($username);?>" />
Password: <input type="password" name="password" value="<?=htmlentities($password);?>" />
<input type="submit" name="dologin" />
</form>

<? switch($login_response): ?>
<? case 'username_not_found': ?>
Utente non trovato.
<? break;?>

<? case 'wrong_password': ?>
Password errata.
<? break;?>

<? case 'must_change_password': ?>
Questo è il tuo primo accesso. Devi <a href="<?=$baseurl;?>user/changepassword">cambiare la password</a> per poter accedere.
<? break;?>

<? case 'ok': ?>
Benvenuto <a href="<?=$baseurl;?>user/user"><?=$username;?></a>
<? endswitch;?>