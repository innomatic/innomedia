<?='<?xml version="1.0" encoding="ISO-8859-1"?>';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
  <title><?=$title;?></title>
  <link rel="stylesheet" type="text/css" href="<?=$baseurl;?>themes/default/default.css" />
  <?=$xajax_js;?>
</head>
<body>
  <div><a id="top"/></div>
  <div id="container">

  <?ksort($blocks);?>
  <?foreach($blocks as $rowid => $blocks_row):?>
    <div class="row-container">
    <?if (isset($blocks_row[1]) and is_array($blocks_row[1])):?>
      <?ksort($blocks_row[1]);?>
      <?foreach($blocks_row[1] as $block):?><?=$$block;?><?endforeach;?>
    <?endif;?>
    <?if (isset($blocks_row[2]) and is_array($blocks_row[2])):?>    
      <?ksort($blocks_row[2]);?>
      <?foreach($blocks_row[2] as $block):?><?=$$block;?><?endforeach;?>
    <?endif;?>
    <?if (isset($blocks_row[3]) and is_array($blocks_row[3])):?>
      <?ksort($blocks_row[3]);?>
      <?foreach($blocks_row[3] as $block):?><?=$$block;?><?endforeach;?>
    <?endif;?>
    </div>
  <?endforeach;?>

  </div>
</body>
</html>