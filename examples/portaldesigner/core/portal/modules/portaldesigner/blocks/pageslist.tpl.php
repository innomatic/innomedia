        <p class="titlemenusection"><?=$title;?></p>
        <?foreach($modules as $module => $pages):?>
        <p class="menusection"><a href="<?=$receiver;?>/portaldesigner/pages/?portaldesigner_openmodulemenu=<?=$module;?>"><?=$module;?></a></p>
          <?foreach($pages as $page):?>
        <p class="submenu"><a href="<?=$receiver;?>/portaldesigner/pages/?portaldesigner_editmodule=<?=$module;?>&amp;portaldesigner_editpage=<?=$page;?>"><?=$page;?></a></p>
          <?endforeach;?>
        <?endforeach;?>
