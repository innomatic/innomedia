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

require_once('innomedia/InnomediaTemplate.php');
require_once('innomedia/InnomediaBlock.php');

/**
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
class InnomediaGrid extends InnomediaTemplate {
    protected $page;
    protected $blocks;

    public function InnomediaGrid(InnomediaPage $page) {
    	$this->page = $page;
        $this->blocks = array ();

		$tpl = $this->page->getContext()->getThemesHome().$this->page->getTheme().'/grid.tpl.php';
        if (!file_exists($tpl)) {
			$tpl = $this->page->getContext()->getThemesHome().'default/grid.tpl.php';
		}
        if (!file_exists($tpl)) {
        	$this->page->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'No theme grid found');
		}
		parent::__construct($tpl);
		$this->setPredefinedTags();
        $this->setArray('blocks', $this->blocks);
	}

    public function setPredefinedTags() {
        $this->set('receiver', $this->page->getRequest()->getUrlPath(true));
        $this->set('baseurl', $this->page->getRequest()->getUrlPath(false).'/');
        $this->set('module', $this->page->getModule());
        $this->set('page', $this->page->getPage());

        // Ajax support
        require_once ('innomatic/ajax/Xajax.php');
        $xajax = Xajax::instance('Xajax');
        
        // Set debug mode
        if (InnomaticContainer::instance('innomaticcontainer')->getState() == InnomaticContainer::STATE_DEBUG) {
        	$xajax->debugOn();
        }

        $xajax_js = $xajax->getJavascript($this->page->getRequest()->getUrlPath(false) . '/' . 'shared/javascript', 'xajax.js');
        
        // Setup calls.
        if ($this->page->getContext()->countRegisteredAjaxSetupCalls() > 0) {
        	$setup_calls = $this->page->getContext()->getRegisteredAjaxSetupCalls();
        	$xajax_js .= '<script type="text/javascript">' . "\n";
        	foreach ($setup_calls as $call) {
        		$xajax_js .= $call . ";\n";
        	}
        	$xajax_js .= '</script>' . "\n";
        }

        $this->set('xajax_js', $xajax_js);
    }

    public function addBlock(InnomediaBlock $block, $row, $column, $position) {
        $block->Run($this->page->getRequest(), $this->page->getResponse());
        if (!$row) {
            $row = 1;
        }
        if (!$column) {
            $column = 1;
        }
        if (!$position) {
            $position = 1;
        }
        $block_name = 'block_'.$row.'_'.$column.'_'.$position;
        $this->set($block_name, $block);
        $this->blocks[$row][$column][$position] = $block_name;
    }
    
    public function getGrid() {
    	return $this;
    }
}

?>