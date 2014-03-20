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
    protected $context;

    protected $page;

    protected $blocks;

    protected $tplFile;

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
        $this->context = Context::instance('\Innomedia\Context');
        $tpl = $this->context->getGridsHome() . $this->page->getTheme() . '.local.tpl.php';
        if (!file_exists($tpl)) {
            $tpl = $this->context->getGridsHome() . $this->page->getTheme() . '.tpl.php';
            if (!file_exists($tpl)) {
                $tpl = $this->context->getGridsHome() . 'default.local.tpl.php';
                if (!file_exists($tpl)) {
                    $tpl = $this->context->getGridsHome() . 'default.tpl.php';
                    if (!file_exists($tpl)) {
                        $this->context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'No theme grid found');
                    }
                }
            }
        }
        parent::__construct($tpl);
        $this->setArray('blocks', $this->blocks);
    }
    /* }}} */

    public function setPredefinedTags()
    {
        $this->set('receiver', $this->context->getRequest()->getUrlPath(true));
        $this->set('baseurl', $this->context->getRequest()->getUrlPath(false) . '/');
        $this->set('module', $this->page->getModule());
        $this->set('page', $this->page->getPage());

        $this->set('page_name', $this->page->getName());
        $this->set('page_title', $this->page->getParameters()['page_title']);
        $this->set('page_meta_keys', $this->page->getParameters()['page_meta_keys']);
        $this->set('page_meta_title', $this->page->getParameters()['page_meta_title']);

        // Ajax support
        $xajax = \Innomatic\Ajax\Xajax::instance('\Innomatic\Ajax\Xajax', $this->context->getRequest()->getUrlPath(false) . '/ajax/');
        $xajax->ajaxLoader = false;
        $xajax->setLogFile(
            $this->context
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
            $this->context
                ->getRequest()
                ->getUrlPath(false) . '/' . 'shared/javascript', 'xajax.js'
        );

        // Setup calls.
        if ($this->context->countRegisteredAjaxSetupCalls() > 0) {
            $setup_calls = $this->context->getRegisteredAjaxSetupCalls();
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
        $block->run($this->context->getRequest(), $this->context->getResponse());
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

    public function parse()
    {
        $this->setPredefinedTags();
        return parent::parse();
    }
}


