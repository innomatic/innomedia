<?php 
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is InnoMedia.
 *
 * The Initial Developer of the Original Code is
 * Alex Pagnoni.
 * Portions created by the Initial Developer are Copyright (C) 2008-2013
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

require_once('innomedia/InnomediaBlock.php');
require_once('innomedia/InnomediaModule.php');

class MediaDesignerAddBlock extends InnomediaBlock {
    public function run(WebAppRequest $request, WebAppResponse $response) {
        $modules = $this->context->getModulesList();
        $modules_list = array ();
        foreach ($modules as $module) {
            $module_obj = new InnomediaModule($this->context, $module);
            if ($module_obj->hasBlocks()) {
                $modules_list[] = $module;
            }
        }
        $this->setArray('modules', $modules_list);

        if ($request->parameterExists('mediadesigner_module')) {
            $module_obj = new InnomediaModule($this->context, $request->getParameter('mediadesigner_module'));
            $this->setArray('blocks', $module_obj->getBlocksList());
            $this->set('module', $request->getParameter('mediadesigner_module'));
        }
        
        $this->set('row', $request->getParameter('mediadesigner_row'));
        $this->set('column', $request->getParameter('mediadesigner_column'));
        $this->set('position', $request->getParameter('mediadesigner_position'));
        $this->set('receiver', $this->grid->get('receiver'));
        $this->set('baseurl', $this->grid->get('baseurl'));

        $module = '';
        if ($this->context->getSession()->isValid('mediadesigner_editmodule')) {
            if (in_array($this->context->getSession()->get('mediadesigner_editmodule'), $modules)) {
                $module = $this->context->getSession()->get('mediadesigner_editmodule');
            }
        }
        if (!strlen($module)) {
            $page = 'home';
        }

        $page = '';
        if ($this->context->getSession()->isValid('mediadesigner_editpage')) {
            $page = $this->context->getSession()->get('mediadesigner_editpage');
        }
        if (!strlen($page)) {
            $page = 'index';
        }
        $this->set('editingmodule', $module);
        $this->set('editingpage', $page);
    }
}

?>