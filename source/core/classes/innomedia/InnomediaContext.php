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
require_once ('innomatic/util/Singleton.php');
require_once ('innomatic/webapp/WebAppRequest.php');
require_once ('innomatic/webapp/WebAppResponse.php');
require_once ('innomatic/php/PHPSession.php');

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
class InnomediaContext extends Singleton
{

    protected $home;

    protected $request;

    protected $response;
    // protected $user;
    protected $session;

    protected $locales = array();

    protected $registeredAjaxCalls = array();

    protected $registeredAjaxSetupCalls = array();

    public function ___construct($home, WebAppRequest $request, WebAppResponse $response)
    {
        $this->home = realpath($home) . '/';
        $this->request = $request;
        $this->response = $response;
        $this->session = new PHPSession();
        $this->session->start();
        
        // Sets 'session.gc_maxlifetime' and 'session.cookie_lifetime' to the value
        // defined by the 'sessionLifetime' parameter in web.xml
        $lifetime = WebAppContainer::instance('webappcontainer')->getCurrentWebApp()->getInitParameter('sessionLifetime');
        if ($lifetime !== false) {
            $this->session->setLifeTime($lifetime);
        }
        
        // Process the request
        $this->process();
    }

    public function getHome()
    {
        return $this->home;
    }

    public function getThemesHome()
    {
        return $this->home . 'shared/themes/';
    }

    public function getModulesHome()
    {
        return $this->home . 'core/innomedia/modules/';
    }

    public function getBlocksHome($module)
    {
        return $this->home . 'core/innomedia/modules/' . $module . '/blocks/';
    }

    public function getPagesHome($module)
    {
        return $this->home . 'core/innomedia/modules/' . $module . '/pages/';
    }

    /**
     * Gets the webapp request object.
     *
     * @return WebAppRequest
     * @since 5.1
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Gets the webapp response object.
     *
     * @return WebAppResponse
     * @since 5.1
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function getSession()
    {
        return $this->session;
    }

    /**
     * Gets the list of allowed locales.
     *
     * Returns the list of the allowed locales.
     *
     * @return array
     * @since 5.1
     */
    public function getLocales()
    {
        return $this->locales;
    }

    public function getModulesList()
    {
        $list = array();
        if ($dh = opendir($this->home . 'core/innomedia/modules')) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' and $file != '..' and is_dir($this->home . 'core/innomedia/modules/' . $file)) {
                    $list[] = $file;
                }
            }
            closedir($dh);
        }
        return $list;
    }

    public function getThemesList()
    {
        $list = array();
        if ($dh = opendir($this->home . 'shared/themes')) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' and $file != '..' and is_dir($this->home . 'shared/themes/' . $file) and file_exists($this->home . 'shared/themes/' . $file . '/grid.tpl.php')) {
                    $list[] = $file;
                }
            }
            closedir($dh);
        }
        return $list;
    }

    /**
     * Imports a module.
     *
     * Adds module's classes to include_path
     *
     * @param string $module
     *            Module name.
     * @return void
     * @since 5.1
     */
    public function importModule($module)
    {
        if (is_dir($this->getModulesHome() . $module . '/classes/')) {
            set_include_path(get_include_path() . ':' . $this->getModulesHome() . $module . '/classes/');
        }
    }

    /**
     * Process and initializes the context.
     *
     * @return void
     * @since 5.1
     */
    private function process()
    {
        // Checks if the locale has been passed as parameter
        if ($this->request->parameterExists('innomedia_setlocale')) {
            // Stores the locale into the session
            $this->session->put('innomedia_locale', $this->request->getParameter('innomedia_setlocale'));
        }
        
        // Retrieves the locale from the session, if set
        if ($this->session->isValid('innomedia_locale')) {
            $this->locales[] = $this->session->get('innomedia_locale');
        }
        
        // Adds the locales supported by the web agent
        $this->locales = array_merge($this->locales, $this->request->getLocales());
    }

    public function registerAjaxCall($callName)
    {
        $this->registeredAjaxCalls[$callName] = true;
    }

    public function getRegisteredAjaxCalls()
    {
        return $this->registeredAjaxCalls;
    }

    public function isRegisteredAjaxCall($callName)
    {
        return isset($this->registeredAjaxCalls[$callName]);
    }

    public function unregisterAjaxCall($callName)
    {
        if (isset($this->registeredAjaxCalls[$callName])) {
            unset($this->registeredAjaxCalls[$callName]);
        }
    }

    public function countRegisteredAjaxCalls()
    {
        return count($this->registeredAjaxCalls);
    }

    public function registerAjaxSetupCall($call)
    {
        $this->registeredAjaxSetupCalls[] = $call;
    }

    public function getRegisteredAjaxSetupCalls()
    {
        return $this->registeredAjaxSetupCalls;
    }

    public function countRegisteredAjaxSetupCalls()
    {
        return count($this->registeredAjaxSetupCalls);
    }
}

?>