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
 * @license    http://www.innomatic.io/license/   BSD License
 * @link       http://www.innomatic.io
 * @since      Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
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
        $root           = \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer');
        $innomatic_home = $root->getHome() . 'innomatic/';
        $innomatic->bootstrap($innomatic_home, $innomatic_home . 'core/conf/innomatic.ini');

        // Start Innomatic domain
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->startDomain(\Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')
            ->getCurrentWebApp()
            ->getName());

        // Innomedia page
        $scope_page = 'frontend';

        // Check if the page exists in the page tree
        $pageSearch = PageTree::findPageByPath(substr($req->getPathInfo(), 1));

        if ($pageSearch === false) {
            // This is a static page (excluding the home page).
            $location    = explode('/', $req->getPathInfo());
            $module_name = isset($location[1]) ? $location[1] : '';
            $page_name   = isset($location[2]) ? $location[2] : '';
            $pageId      = isset($location[3]) ? $location[3] : 0;
        } else {
            // This is the home page or a content page.
            $module_name = $pageSearch['module'];
            $page_name   = $pageSearch['page'];
            $pageId      = $pageSearch['page_id'];
        }

        // Define Innomatic context
        $home    = \Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getCurrentWebApp()->getHome();
        $context = Context::instance('\Innomedia\Context');
        $context
            ->setRequest($req)
            ->setResponse($res)
            ->process();

        // Build Innomedia page
        $page = new Page($module_name, $page_name, $pageId, $scope_page);
        $page->parsePage();

        // Check if the page is valid
        if (!$page->isValid()) {
            $res->sendError(\Innomatic\Webapp\WebAppResponse::SC_NOT_FOUND, $req->getRequestURI());
        } else {
            $page->build();
        }
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
