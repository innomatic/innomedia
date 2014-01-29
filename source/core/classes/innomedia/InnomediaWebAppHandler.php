<?php
/****** BEGIN LICENSE BLOCK *****
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
namespace Innomedia;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
class InnomediaWebAppHandler extends \Innomatic\Webapp\WebAppHandler
{

    /**
     * Inits the webapp handler.
     */
    public function init()
    {}

    public function doGet(\Innomatic\Webapp\WebAppRequest $req, \Innomatic\Webapp\WebAppResponse $res)
    {
        // Start Innomatic
        
        $innomatic = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $innomatic->setInterface(InnomaticContainer::INTERFACE_EXTERNAL);
        $root = \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer');
        $innomatic_home = $root->getHome() . 'innomatic/';
        $innomatic->bootstrap($innomatic_home, $innomatic_home . 'core/conf/innomatic.ini');
        
        // Start Innomatic domain
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->startDomain(\Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getCurrentWebApp()
            ->getName());
        
        // Innomedia page
        
        // Get module and page name
        $location = explode('/', $req->getPathInfo());
        $module_name = isset($location[1]) ? $location[1] : '';
        $page_name = isset($location[2]) ? $location[2] : '';
        
        // Define Innomatic context
        $home = \Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getCurrentWebApp()->getHome();
        $context = InnomediaContext::instance('\Innomedia\InnomediaContext', $home, $req, $res);
        
        // Build Innomedia page
        $page = new InnomediaPage($context, $req, $res, $module_name, $page_name);
        $page->build();
    }

    public function doPost(\Innomatic\Webapp\WebAppRequest $req, \Innomatic\Webapp\WebAppResponse $res)
    {
        // We do get instead
        $this->doGet($req, $res);
    }

    /**
     * Destroys the webapp handler.
     */
    public function destroy()
    {}
}

?>