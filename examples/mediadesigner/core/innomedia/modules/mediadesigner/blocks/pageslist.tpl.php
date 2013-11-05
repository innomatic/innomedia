        <p class="titlemenusection"><?=$title;?></p>
        <?foreach($modules as $module => $pages):?>
        <p class="menusection"><a href="<?=$receiver;?>/mediadesigner/pages/?mediadesigner_openmodulemenu=<?=$module;?>"><?=$module;?></a></p>
          <?foreach($pages as $page):?>
        <p class="submenu"><a href="<?=$receiver;?>/mediadesigner/pages/?mediadesigner_editmodule=<?=$module;?>&amp;mediadesigner_editpage=<?=$page;?>"><?=$page;?></a></p>
          <?endforeach;?>
        <?endforeach;?>
