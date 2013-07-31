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

require_once('innomatic/webapp/WebAppContainer.php');
require_once('innomatic/webapp/WebAppHandler.php');
require_once('portal/PortalContext.php');
require_once('portal/PortalPage.php');

/**
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2009 Innoteam
 * @since 1.0
 */
class PortalWebAppHandler extends WebAppHandler {
    /**
     * Inits the webapp handler.
     */
    public function init() {
    }

    public function doGet(WebAppRequest $req, WebAppResponse $res) {
		$location = explode('/', $req->getPathInfo());
		$home = WebAppContainer::instance('webappcontainer')->getCurrentWebApp()->getHome();
		$context = PortalContext::instance('PortalContext', $home, $req, $res);
		$page = new PortalPage($context, $req, $res, $location[1], $location[2]);
		$page->build();
    }

    public function doPost(WebAppRequest $req, WebAppResponse $res) {
		$this->doGet($req, $res);
    }

    /**
     * Destroys the webapp handler.
     */
    public function destroy() {
    }
}

?>