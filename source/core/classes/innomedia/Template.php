<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  2008-2014 Innomatic Company
 * @license    http://www.innomatic.io/license/   BSD License
 * @link       http://www.innomatic.io
 * @since      Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright Copyright 2008-2013 Innomatic Company
 * @since 1.1
 */
abstract class Template implements \Innomatic\Tpl\Template
{
/* }}} */
    /**
     * Original PHP template object.
     *
     * @var \Innomatic\Php\PHPTemplate
     * @access protected
     */
    protected $tplEngine;

    /* public __construct($file) {{{ */
    /**
     * Class constructor.
     *
     * @param string $file Template file full path.
     * @access public
     * @return void
     */
    public function __construct($file)
    {
        $this->tplEngine = new \Innomatic\Php\PHPTemplate($file);
    }
    /* }}} */

    /**
     * Sets a variable and its value.
     *
     * The variable will be extracted to the local scope of the template with
     * the same name given as parameter to the method. So setting a variable
     * named "title" will result in a $title variable available to the template.
     *
     * The value can be a string, a number or a PhpTemplate instance. In the
     * latter case, the variable content will be the result of the whole parsed
     * template of the PhpTemplate instance.
     *
     * The proper method for setting arrays is setArray().
     *
     * @param string $name Name of the variable.
     * @param string|number|PhpTemplate $value variable content.
     * @see setArray()
     */
    public function set($name, $value)
    {
        $this->tplEngine->set($name, $value);
        return $this;
    }

    /**
     * Returns the current value of a variable.
     *
     * @param string $name Name of the variable.
     * @return string Variable value.
     * @see getArray()
     */
    public function get($name)
    {
        $value = $this->tplEngine->get($name);
        if ($value === false and ! ($this instanceof InnomaticGrid)) {
            $value = $this->getGrid()->get($name);
        }
        return $value;
    }

    /**
     * Sets an array by reference as variable.
     *
     * This method is similar to the set() one, with the difference that it
     * takes arrays by reference and that it doesn't support passing a
     * PhpTemplate as value.
     *
     * @param string $name Array name.
     * @param array $value Array.
     * @see get()
     */
    public function setArray($name, &$value)
    {
        if (method_exists($this->tplEngine, 'setArray')) {
            $this->tplEngine->setArray($name, $value);
        } else {
            $this->tplEngine->set($name, $value);
        }
        return $this;
    }

    /**
     * Returns the current value of a variable stored as array.
     *
     * @param string $name Name of the array.
     * @return string Array.
     * @see get()
     */
    public function &getArray($name)
    {
        if (method_exists($this->tplEngine, 'getArray')) {
            return $this->tplEngine->getArray($name);
        } else {
            return $this->tplEngine->get($name);
        }
    }

    /**
     * Parses the template.
     *
     * This method parses the template and returns the parsed output.
     *
     * @since 1.1
     * @return string
     */
    public function parse()
    {
        return $this->tplEngine->parse();
    }

    /**
     * Returns a list of the set tag names.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tplEngine->getTags();
    }

    /* public getGrid() {{{ */
    /**
     * Returns the Innomedia Grid object.
     *
     * @abstract
     * @access public
     * @return \Innomedia\Grid
     */
    public abstract function getGrid();
}
