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
 * The Original Code is InnoPHP.
 *
 * The Initial Developer of the Original Code is
 * Alex Pagnoni.
 * Portions created by the Initial Developer are Copyright (C) 2008
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

require_once('portal/PortalBlock.php');
require_once('portal/PortalModule.php');

class PortalDesignerPagesList extends PortalBlock {
    public function run(WebAppRequest $request, WebAppResponse $response) {
    	$pages_list = array ();
        $modules = $this->context->getModulesList();
        $open_module = '';
        if ($this->context->getSession()->isValid('portaldesigner_currentmodulemenu')) {
            if (in_array($this->context->getSession()->get('portaldesigner_currentmodulemenu'), $modules)) {
                $open_module = $this->context->getSession()->get('portaldesigner_currentmodulemenu');
            }
        }
        if ($request->parameterExists('portaldesigner_openmodulemenu')) {
            $open_module = $request->getParameter('portaldesigner_openmodulemenu');
            $this->context->getSession()->put('portaldesigner_currentmodulemenu', $open_module);
        }
        foreach ($modules as $module) {
                $module_obj = new PortalModule($this->context, $module);
                if (!$module_obj->hasPages()) {
                    continue;
                }
            $pages_list[$module] = array ();
            if (!strlen($open_module)) {
                $open_module = $module;
            }
            if ($module == $open_module) {
                $pages = $module_obj->getPagesList();
                foreach ($pages as $page) {
                    $pages_list[$module][] = $page;
                }
            }
        }
        $this->setArray('modules', $pages_list);
        $this->set('receiver', $this->grid->get('receiver'));
        $this->set('title', 'Pagine');
    }
}

?>