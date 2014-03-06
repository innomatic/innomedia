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
 * @since 1.1
 */
abstract class InnomediaTemplate implements \Innomatic\Tpl\Template
{

    protected $tplEngine;

    public function __construct($file)
    {
        $this->tplEngine = new \Innomatic\Php\PHPTemplate($file);
    }

    public function set($name, $value)
    {
        $this->tplEngine->set($name, $value);
        return $this;
    }

    public function get($name)
    {
        $value = $this->tplEngine->get($name);
        if ($value === false and ! ($this instanceof InnomaticGrid)) {
            $value = $this->getGrid()->get($name);
        }
        return $value;
    }

    public function setArray($name, &$value)
    {
        if (method_exists($this->tplEngine, 'setArray')) {
            $this->tplEngine->setArray($name, $value);
        } else {
            $this->tplEngine->set($name, $value);
        }
        return $this;
    }

    public function &getArray($name)
    {
        if (method_exists($this->tplEngine, 'getArray')) {
            return $this->tplEngine->getArray($name);
        } else {
            return $this->tplEngine->get($name);
        }
    }

    public function parse()
    {
        return $this->tplEngine->parse();
    }

    public function getTags()
    {
        return $this->tplEngine->getTags();
    }

    public abstract function getGrid();
}
