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
require_once('innomatic/webapp/WebAppContainer.php');

class ContentBlock extends InnomediaBlock {
	private $pages_root;

	public function run(WebAppRequest $request, WebAppResponse $response) {
		$page = $request->getParameter('content_page');
		$this->pages_root = $this->context->getHome().'core/innomedia/content/';
		// If no page has been given, it is set to the default one
		if (!strlen($page)) {
			$page = 'home/index.html';
		}

		// Retrieves real localized page
		$page_file = $this->getPage($page);

		// Security check
		$page_file = realpath($page_file);
		if (strpos($page_file, realpath($this->pages_root)) !== 0) {
			$page_file = '';
		}

		if (!strlen($page_file)) {
			// Retrieves 404 page
			$page_file = $this->getPage('common/404.html');
		}
		
		// Sets the page content
		if ($page_file) {
			$this->set('content', file_get_contents($page_file));
		} else {
			$this->set('content', 'Page not found.');
		}

		// Assigns page name
		$this->set('page', $page);

		// Assigns page file
		$this->set('pagefile', $page_file);

		/*
		// Gets site mappings
		$this->context->importModule('sitemap');
		Carthag :: import('com.innoteam.modules.sitemap.SiteMapStructure');
		$structure = SiteMapStructure :: getInstance($request->getContext(), $this->context->getModulesHome().'sitemap/sitemap.xml');

		// Featured image
		$map = $structure->getMapByMatch($page, array ('image'));
		$this->set('feature_image', $map[0]['details']['image']);

		// Path
		$map = $structure->getMapByMatch($page, array ('path'));
		$this->set('path', $map[0]['details']['path']);
		*/
	}

	public function getPage($page) {
		$pages_root = $this->context->getHome().'core/innomedia/content/';
		$locales = $this->context->getLocales();
		foreach ($locales as $locale) {
			if (file_exists($this->pages_root.$locale.'/'.$page)) {
				// Page for given language exists
				return $this->pages_root.$locale.'/'.$page;
			}
		}

		if (file_exists($this->pages_root.WebAppContainer::instance('webappcontainer')->getCurrentWebApp()->getInitParameter('InnomediaDefaultLanguage').'/'.$page)) {
			// Page for default language exists
			return $this->pages_root.WebAppContainer::instance('webappcontainer')->getCurrentWebApp()->getInitParameter('InnomediaDefaultLanguage').'/'.$page;
		}
		elseif (file_exists($this->pages_root.$page)) {
			// Page for no specific language exists
			return $this->pages_root.$page;
		}
		// No page exists
		return false;
	}
}
?>