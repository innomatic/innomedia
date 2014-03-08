<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  2008-2014 Innoteam Srl
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
class WebAppHandler extends \Innomatic\Webapp\WebAppHandler
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
        $innomatic->setInterface(\Innomatic\Core\InnomaticContainer::INTERFACE_EXTERNAL);
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
        $context = Context::instance('\Innomedia\Context', $home, $req, $res);

        // Build Innomedia page
        $page = new Page($context, $req, $res, $module_name, $page_name);
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
