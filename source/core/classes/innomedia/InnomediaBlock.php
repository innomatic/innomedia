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
require_once('innomedia/InnomediaContext.php');
require_once('innomedia/InnomediaGrid.php');
require_once('innomatic/webapp/WebAppContainer.php');
require_once('innomatic/webapp/WebAppRequest.php');
require_once('innomatic/webapp/WebAppResponse.php');

/**
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
abstract class InnomediaBlock extends InnomediaTemplate {
    protected $context;
    protected $grid;
    protected $show = true;

    public function __construct($file) {
        parent::__construct($file);
    }

    public function setContext(InnomediaContext $context) {
		$this->context = $context;
    }
    
    public function setGrid(InnomediaGrid $grid) {
		$this->grid = $grid;
    }
    
    public static function load(InnomediaContext $context, InnomediaGrid $grid, $module, $name) {
		if (!strlen($module)) {
			return;
		}
		
    	// Adds module classes directory to classpath
        $context->importModule($module);

        $block_xml_file = $context->getBlocksHome($module).$name.'.xml';
        if (!file_exists($block_xml_file)) {
        	$context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'Missing block definition file '.$name.'.xml');
        	return;
        }
        // Imports block's class and return an instance of it.
        $def = simplexml_load_file($block_xml_file);
        $fqcn = "$def->class";
        if (!strlen($fqcn)) {
            $fqcn = 'innomedia/InnomediaEmptyBlock.php';
        }

        $included = @include_once($fqcn);
        if (!$included) {
        	$context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'Missing class '.$fqcn);
        	return;
        }

        $tpl_root = $context->getBlocksHome($module);
        $tpl_file = '';
        $locales = $context->getLocales();
        foreach ($locales as $locale) {
            if (file_exists($tpl_root.$locale.'.'."$def->template")) {
                // Page for given language exists
                $tpl_file = $tpl_root.$locale.'.'."$def->template";
                break;
            }
        }
        if (!strlen($tpl_file)) {
            if (file_exists($tpl_root.WebAppContainer::instance('webappcontainer')->getCurrentWebApp()->getInitParameter('InnomediaDefaultLanguage').'.'."$def->template")) {
                // Page for default language exists
                $tpl_file = $tpl_root.WebAppContainer::instance('webappcontainer')->getCurrentWebApp()->getInitParameter('InnomediaDefaultLanguage').'.'."$def->template";
            } else {
                // Page for no specific language exists
                $tpl_file = $tpl_root."$def->template";
            }
        }
        
        // Find block class
		$class = substr($fqcn, strrpos($fqcn, '/') ? strrpos($fqcn, '/') + 1 : 0, -4);
		if (!class_exists($class)) {
			$context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'Malformed block class '.$fqcn);
			return;
		}
		
		// Build block
        $obj = new $class($tpl_file);
        $obj->setContext($context);
        $obj->setGrid($grid);
        
        // Get all grid tags and set them in the block tags
        $grid_tags = $grid->getTags();
        foreach($grid_tags as $tag) {
        	$obj->set($tag, $grid->get($tag));
        }
        return $obj;
    }

    private function getTemplateFile($page) {
        $locales = $this->context->getLocales();
        foreach ($locales as $locale) {
            if (file_exists($pages_root.$locale.'/'.$page)) {
                // Page for given language exists
                return $pages_root.$locale.'/'.$page;
            }
        }

        if (file_exists($pages_root.$this->context->getRequest()->getContext()->getConfig()->getInitParameter('contentDefaultLanguage').'/'.$page)) {
            // Page for default language exists
            return $pages_root.$this->default_lang.'/'.$page;
        }
        elseif (file_exists($pages_root.$page)) {
            // Page for no specific language exists
            return $pages_root.$page;
        }
        // No page exists
        return false;
    }

    public function setShow($show) {
        $this->show = $show;
    }

    public function getShow() {
        return $this->show;
    }

    public function getContext() {
        return $this->context;
    }

    public function getGrid() {
        return $this->grid;
    }

    abstract public function run(WebAppRequest $request, WebAppResponse $response);
}

?>