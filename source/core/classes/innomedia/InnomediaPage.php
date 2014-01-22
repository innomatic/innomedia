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

require_once('innomedia/InnomediaContext.php');
require_once('innomedia/InnomediaGrid.php');
require_once('innomedia/InnomediaBlock.php');
require_once('innomatic/webapp/WebAppRequest.php');
require_once('innomatic/webapp/WebAppResponse.php');

/**
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
class InnomediaPage
{
	protected $context;
	protected $request;
	protected $response;
	protected $module;
	protected $page;
	protected $pageDefFile;
	protected $theme;
	protected $grid;

	public function __construct(InnomediaContext $context, WebAppRequest $request, WebAppResponse $response, $module, $page)
	{
		$this->context = $context;
		$this->request = $request;
		$this->response = $response;
		// TODO Add fallback module/page as optional welcome page 
		$this->module = strlen($module) ? $module : 'home';
		$this->page = strlen($page) ? $page : 'index';
		$this->theme = 'default';
		$this->pageDefFile = $context->getPagesHome($this->module).$this->page.'.xml';
		$this->parsePage();
	}

	protected function parsePage()
	{
		if (!file_exists($this->pageDefFile)) {
			return false;
		}
		$def = simplexml_load_file($this->pageDefFile);
		// Gets page level theme if defined
		if (strlen("$def->theme")) {
			$this->theme = "$def->theme";
		}
		// Loads the grid
		$this->grid = new InnomediaGrid($this);
		// Gets block list
		foreach ($def->block as $blockDef) {
			$block = InnomediaBlock::load($this->context, $this->grid, "$blockDef->module", "$blockDef->name");
			if (!is_null($block)) {
				$this->grid->addBlock($block, "$blockDef->row", "$blockDef->column", "$blockDef->position");
			}
		}
	}

	public function getContext()
	{
		return $this->context;
	}

	public function getTheme()
	{
		return $this->theme;
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function getResponse()
	{
		return $this->response;
	}

	public function getModule()
	{
		return $this->module;
	}

	public function getPage()
	{
		return $this->page;
	}

	public function build()
	{
		if (is_object($this->grid)) {
			echo $this->grid->parse();
		} else {
			$this->response->sendError(WebAppResponse::SC_NOT_FOUND, $this->request->getRequestURI());
		}
	}
}

?>