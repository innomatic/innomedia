<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright 2008-2014 Innoteam Srl
 * @license   http://www.innomatic.org/license/   BSD License
 * @link      http://www.innomatic.org
 * @since     Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 * This class handles the grid.
 *
 * @author    Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright 2008-2013 Innoteam Srl
 * @since     1.0
 */
class Grid extends \Innomedia\Template
{

    protected $page;

    protected $blocks;

    /* public __construct(Page $page) {{{ */
    /**
     * Class constructor.
     *
     * @param Page $page Current page object.
     */
    public function __construct($page)
    {
        $this->page = $page;
        $this->blocks = array();

        $tpl = $this->page->getContext()->getGridsHome() . $this->page->getTheme() . '.local.tpl.php';
        if (!file_exists($tpl)) {
            $tpl = $this->page->getContext()->getGridsHome() . $this->page->getTheme() . '.tpl.php';
        }
        if (!file_exists($tpl)) {
            $tpl = $this->page->getContext()->getGridsHome() . 'default.local.tpl.php';
        }
        if (!file_exists($tpl)) {
            $tpl = $this->page->getContext()->getGridsHome() . 'default.tpl.php';
        }
        if (!file_exists($tpl)) {
            $this->page->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'No theme grid found');
        }
        parent::__construct($tpl);
        $this->setPredefinedTags();
        $this->setArray('blocks', $this->blocks);
    }
    /* }}} */

    public function setPredefinedTags()
    {
        $this->set('receiver', $this->page->getRequest()->getUrlPath(true));
        $this->set('baseurl', $this->page->getRequest()->getUrlPath(false) . '/');
        $this->set('module', $this->page->getModule());
        $this->set('page', $this->page->getPage());

        // Ajax support
        $xajax = \Innomatic\Ajax\Xajax::instance('\Innomatic\Ajax\Xajax', $this->page->getRequest()->getUrlPath(false) . '/ajax/');
        $xajax->ajaxLoader = false;
        $xajax->setLogFile(
            $this->page->getContext()
                ->getHome() . 'core/log/ajax.log'
        );

        // Set debug mode
        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getState() == \Innomatic\Core\InnomaticContainer::STATE_DEBUG) {
            $xajax->debugOn();
        }

        // Register Ajax calls parsing the ajax.xml configuration file
        if (file_exists(\Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getCurrentWebApp()->getHome() . 'core/conf/ajax.xml')) {
            $cfg = \Innomatic\Ajax\XajaxConfig::getInstance(\Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getCurrentWebApp(), \Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getCurrentWebApp()->getHome() . 'core/conf/ajax.xml');

            if (isset($cfg->functions)) {
                foreach ($cfg->functions as $name => $functionData) {
                    $xajax->registerExternalFunction(
                        array(
                            $name,
                            $functionData['classname'],
                            $functionData['method']
                        ),
                        $functionData['classfile']
                    );
                }
            }
        }

        // Build the base javascript for ajax
        $xajax_js = $xajax->getJavascript(
            $this->page
                ->getRequest()
                ->getUrlPath(false) . '/' . 'shared/javascript', 'xajax.js'
        );

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

        return $this;
    }

    public function sortBlocks()
    {
        ksort($this->blocks);
    }

    /* public addBlock(Block $block, $row, $column, $position) {{{ */
    /**
     * Adds a Block to the grid at the given position.
     *
     * @param Block $block    Block object to be added at the grid
     * @param int   $row      Block row in the grid
     * @param int   $column   Block column in the grid
     * @param int   $position Block position in the cell
     *
     * @return Grid grid object
     */
    public function addBlock(Block $block, $row, $column, $position)
    {
        $block->run($this->page->getRequest(), $this->page->getResponse());
        if (! $row) {
            $row = 1;
        }
        if (! $column) {
            $column = 1;
        }
        if (! $position) {
            $position = 1;
        }
        $block_name = 'block_' . $row . '_' . $column . '_' . $position;
        $this->set($block_name, $block);
        $this->blocks[$row][$column][$position] = $block_name;

        return $this;
    }
    /* }}} */

    /* public getBlocks() {{{ */
    /**
     * Returns the blocks array.
     *
     * @return array blocks.
     */
    public function getBlocks()
    {
        return $this->blocks;
    }
    /* }}} */

    /* public getGrid() {{{ */
    /**
     * Returns the grid object.
     *
     * @return \Innomedia\Grid
     */
    public function getGrid()
    {
        return $this;
    }
    /* }}} */

    /* public getPage() {{{ */
    /**
     * Returns the page object given to the grid.
     *
     * @return \Innomedia\Page the page object
     */
    public function getPage()
    {
        return $this->page;
    }
    /* }}} */
}

