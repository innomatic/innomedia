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
 * @since     1.0.0
 */
namespace Innomedia;

/**
 *
 * @author    Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright 2008-2014 Innomatic Company
 * @since     1.0.0
 */
class Module
{
    /**
     * Module name.
     *
     * @var string
     * @access protected
     */
    protected $name;

    /* public __construct($moduleName) {{{ */
    /**
     * Class constructor.
     *
     * @param string $moduleName Module name.
     * @access public
     * @return void
     */
    public function __construct($moduleName)
    {
        $this->name = $moduleName;
    }
    /* }}} */

    /* public getName() {{{ */
    /**
     * Returns module name.
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /* }}} */

    /* public getHome() {{{ */
    /**
     * Returns module full path in tenant webapp folder.
     *
     * @access public
     * @return void
     */
    public function getHome()
    {
        return Context::instance('\Innomedia\Context')->getModulesHome() . $this->name . '/';
    }
    /* }}} */

    /* public hasPages() {{{ */
    /**
     * Checks if the module provides frontend pages.
     *
     * @access public
     * @return boolean
     */
    public function hasPages()
    {
        return file_exists($this->getHome() . 'pages');
    }
    /* }}} */

    /* public hasBlocks() {{{ */
    /**
     * Checks if the module provides page blocks.
     *
     * @access public
     * @return boolean
     */
    public function hasBlocks()
    {
        return file_exists($this->getHome() . 'blocks');
    }
    /* }}} */

    /* public getPagesList() {{{ */
    /**
     * Gets the list of the provided page types.
     *
     * @access public
     * @return boolean
     */
    public function getPagesList()
    {
        $list = array();

        // Check if there is any page.
        if (!$this->hasPages()) {
            return $list;
        }

        // Build pages list.
        if ($dh = opendir($this->getHome() . 'pages')) {
            while (($file = readdir($dh)) !== false) {
                if (
                    $file != '.'
                    && $file != '..'
                    && is_file($this->getHome() . 'pages/' . $file)
                    && strrpos($file, '.yml')
                    && !strrpos($file, '.local.yml')
                ) {
                    $list[] = substr($file, 0, strrpos($file, '.yml'));
                }
            }
            closedir($dh);
        }

        return $list;
    }
    /* }}} */

    /* public getBlocksList() {{{ */
    /**
     * Gets the list of the provided page blocks.
     *
     * @access public
     * @return boolean
     */
    public function getBlocksList()
    {
        $list = array();

        // Check if there is any block.
        if (!$this->hasBlocks()) {
            return $list;
        }

        // Build blocks list.
        if ($dh = opendir($this->getHome() . 'blocks')) {
            while (($file = readdir($dh)) !== false) {
                if (
                    $file != '.'
                    && $file != '..'
                    && is_file($this->getHome() . 'blocks/' . $file)
                    && strrpos($file, '.yml') && !strrpos($file, '.local.yml')
                ) {
                    $list[] = substr($file, 0, strrpos($file, '.yml'));
                }
            }
            closedir($dh);
        }

        return $list;
    }
    /* }}} */
}

?>
