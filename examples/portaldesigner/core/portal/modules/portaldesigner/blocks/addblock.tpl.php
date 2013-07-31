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
            <p>Modulo:
          <form name="module" method="post" action="<?=$receiver;?>/portaldesigner/addblock">
            <select name="portaldesigner_module" onchange="this.form.submit();">
<?foreach($modules as $curmodule):?>
              <option value="<?=$curmodule;?>"<?=($module == $curmodule ? ' selected="selected"' : '');?>><?=$curmodule;?></option>
<?endforeach;?>
            </select>
            <input type="hidden" name="portaldesigner_row" value="<?=$row;?>"/>
            <input type="hidden" name="portaldesigner_column" value="<?=$column;?>"/>
            <input type="hidden" name="portaldesigner_position" value="<?=$position;?>"/>
          </form>

<?if(isset($blocks)):?>
          <form name="module" method="post" action="<?=$receiver;?>/portaldesigner/pages/?portaldesigner_action=addblock">
            Blocco:
            <select name="portaldesigner_block">
<?foreach($blocks as $block):?>
              <option value="<?=$block;?>"><?=$block;?></option>
<?endforeach;?>
            </select>

            <input type="submit" value="Aggiungi"/>
            
            <input type="hidden" name="portaldesigner_blockmodule" value="<?=$module;?>"/>
            <input type="hidden" name="portaldesigner_row" value="<?=$row;?>"/>
            <input type="hidden" name="portaldesigner_column" value="<?=$column;?>"/>
            <input type="hidden" name="portaldesigner_position" value="<?=$position;?>"/>
          </form>
<?endif;?>
            </p>
        </div>
      </div>