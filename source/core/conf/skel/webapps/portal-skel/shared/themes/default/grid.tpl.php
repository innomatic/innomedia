<html>
<head>
<title><?=$title;?></title>
</head>
<body>
  <?foreach($blocks as $rowid => $blocks_row):?>
  <table>
    <tr>
      <?if (isset($blocks_row[1])):?>
      <td width="160"><?foreach($blocks_row[1] as $block):?><?=$$block;?><?endforeach;?></td>
      <?endif;?>
      <td width="100%"><?foreach($blocks_row[2] as $block):?><?=$$block;?><?endforeach;?></td>
      <?if (isset($blocks_row[3])):?>
      <td width="160"><?foreach($blocks_row[3] as $block):?><?=$$block;?><?endforeach;?></td>
      <?endif;?>
    </tr>
  </table>
  <?endforeach;?>
</body>
</html>