      <div id="content-center">
        <div id="path">
<?if(strlen($editingmodule) and strlen($editingpage)):?>
          <ul>
            <li>:: <?=$editingmodule;?></li>
            <li class="last">&gt; <?=$editingpage;?></li>
          </ul>
<?endif;?>
        </div>
        <div id="topborder"></div>
        <div id="content">
<?if(strlen($editingmodule) and strlen($editingpage)):?>
          <table class="gridtable">
<!-- Remove columns -->
<tr>
<?for($column = 0; $column <= $columns + 1; $column++):?>
  <td>
<?if($column == 0):?>
<?elseif($column == $columns+1):?>
<ul>
<li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=addcolumn';?>"><img src="<?=$baseurl;?>images/portaldesigner/add.png" alt=""/></a></li>
</ul>
<?elseif($columns > 1):?>
<ul>
<li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=removecolumn&amp;portaldesigner_column='.$column;?>"><img src="<?=$baseurl;?>images/portaldesigner/remove.png" alt=""/></a></li>
</ul>
<?endif;?>
  </td>
<?endfor;?>
</tr>

<?for($row = 1; $row <= $rows; $row++):?>
            <tr>
              <td>
<?if($rows > 1):?>
<ul>
<li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=removerow&amp;portaldesigner_row='.$row;?>"><img src="<?=$baseurl;?>images/portaldesigner/remove.png" alt=""/></a></li>
</ul>
<?endif;?>
              </td>
<?for($column = 1; $column <= $columns; $column++):?>
              <td>
<?if (isset($blocks[$row][$column])):?>
<?$positions = count($blocks[$row][$column]);?>
<?foreach ($blocks[$row][$column] as $position => $block):?>
<div class="gridblock">
<p><?=$block['module'].'/'.$block['name'];?></p>
<ul>
<li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=removeblock&amp;portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.$position;?>"><img src="<?=$baseurl;?>images/portaldesigner/remove.png" alt=""/></a></li>
<?if ($row > 1):?><li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=upblock&amp;portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.$position;?>"><img src="<?=$baseurl;?>images/portaldesigner/up.png" alt=""/></a></li><?endif;?>
<?if ($row < $rows):?><li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=downblock&amp;portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.$position;?>"><img src="<?=$baseurl;?>images/portaldesigner/down.png" alt=""/></a></li><?endif;?>
<?if ($column > 1):?><li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=leftblock&amp;portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.$position;?>"><img src="<?=$baseurl;?>images/portaldesigner/left.png" alt=""/></a></li><?endif;?>
<?if ($column < $columns):?><li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=rightblock&amp;portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.$position;?>"><img src="<?=$baseurl;?>images/portaldesigner/right.png" alt=""/></a></li><?endif;?>
<?if ($position > 1):?><li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=raiseblock&amp;portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.$position;?>"><img src="<?=$baseurl;?>images/portaldesigner/raise.png" alt=""/></a></li><?endif;?>
<?if ($position < $positions):?><li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=lowerblock&amp;portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.$position;?>"><img src="<?=$baseurl;?>images/portaldesigner/lower.png" alt=""/></a></li><?endif;?>
</ul>
</div>
<?endforeach;?>
<div class="newgridblock">
<ul>
<li><a href="<?=$receiver.'/portaldesigner/addblock/?portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position='.($position+1);?>"><img src="<?=$baseurl;?>images/portaldesigner/add.png" alt=""/></a></li>
</ul>
</div>
<?else:?>
<div class="newgridblock">
<ul>
<li><a href="<?=$receiver.'/portaldesigner/addblock/?portaldesigner_row='.$row.'&amp;portaldesigner_column='.$column.'&amp;portaldesigner_position=1';?>"><img src="<?=$baseurl;?>images/portaldesigner/add.png" alt=""/></a></li>
</ul>
</div>
<?endif;?>
              </td>
<?endfor;?>
            </tr>
<?endfor;?>
            <tr>
              <td>
<ul>
<li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=addrow';?>"><img src="<?=$baseurl;?>images/portaldesigner/add.png" alt=""/></a></li>
</ul>
              </td>
            </tr>
          </table>
          
          <ul>
            <li><a href="<?=$receiver.'/'.$editingmodule.'/'.$editingpage;?>" target="_blank"><img src="<?=$baseurl;?>images/portaldesigner/preview.png" alt="" />Mostra</a></li>
<?if($modified == true):?>
            <li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=save';?>"><img src="<?=$baseurl;?>images/portaldesigner/save.png" alt="" />Salva</a></li>
            <li><a href="<?=$receiver.'/portaldesigner/pages/?portaldesigner_action=revert';?>"><img src="<?=$baseurl;?>images/portaldesigner/revert.png" alt="" />Annulla modifiche</a></li>
<?endif;?>
          </ul>

<?endif;?>
        </div>
      </div>
