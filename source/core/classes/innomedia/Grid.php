<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright 2008-2014 Innomatic Company
 * @license   http://www.innomatic.io/license/   BSD License
 * @link      http://www.innomatic.io
 * @since     Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 * This class handles the grid.
 *
 * @author    Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright 2008-2013 Innomatic Company
 * @since     1.0
 */
class Grid extends \Innomedia\Template
{
    /**
     * Innomedia context object.
     *
     * @var \Innomedia\Context
     * @access protected
     */
    protected $context;

    /**
     * Innomedia page object.
     *
     * @var \Innomedia\Page
     * @access protected
     */
    protected $page;

    /**
     * Array of the page blocks.
     *
     * @var array
     * @access protected
     */
    protected $blocks;

    /**
     * Full path of the grid template file.
     *
     * @var string
     * @access protected
     */
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

        // Check if a local template file exists.
        $tpl = $this->context->getGridsHome() . $this->page->getTheme() . '.local.tpl.php';
        if (!file_exists($tpl)) {
            // Check if a predefined template file exists.
            $tpl = $this->context->getGridsHome() . $this->page->getTheme() . '.tpl.php';
            if (!file_exists($tpl)) {
                // Check if a local default template file exists.
                $tpl = $this->context->getGridsHome() . 'default.local.tpl.php';
                if (!file_exists($tpl)) {
                    // Check if a default template file exists.
                    $tpl = $this->context->getGridsHome() . 'default.tpl.php';
                    if (!file_exists($tpl)) {
                        // No template file found, send error.
                        $this->context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'No theme grid found');
                    }
                }
            }
        }

        // Call the template constructor with the found template file.
        parent::__construct($tpl);

        // Set the blocks list in the template blocks variable.
        $this->setArray('blocks', $this->blocks);
    }
    /* }}} */

    /* public sortBlocks() {{{ */
    /**
     * Sorts the grid objects by number.
     *
     * @access public
     * @return void
     */
    public function sortBlocks()
    {
        ksort($this->blocks);
    }
    /* }}} */

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
        // Process the block and build the block HTML.
        $block->run($this->context->getRequest(), $this->context->getResponse());

        // Set default row if not given.
        if (!$row) {
            $row = 1;
        }

        // Set default column it not given.
        if (!$column) {
            $column = 1;
        }

        // Set default position inside the cell if not given.
        if (!$position) {
            $position = 1;
        }

        // Set block name.
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

    /* public parse() {{{ */
    /**
     * Parses the grid template.
     *
     * @access public
     * @return boolean
     */
    public function parse()
    {
        return parent::parse();
    }
    /* }}} */
}


