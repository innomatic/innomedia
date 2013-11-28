<?php  
/* ***** BEGIN LICENSE BLOCK *****
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

require_once('innomatic/tpl/Template.php');

/**
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.1
 */
abstract class InnomediaTemplate implements Template {
	protected $tplEngine;
	
	public function __construct($file) {
		require_once('innomatic/php/PHPTemplate.php');
		$this->tplEngine = new PHPTemplate($file);
	}
	
	public function set($name, $value) {
		$this->tplEngine->set($name, $value);
	}
	
	public function get($name)
	{
		$value = $this->tplEngine->get($name);
		if ($value === false and !($this instanceof InnomaticGrid)) {
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
	
	public function getTags() {
		return $this->tplEngine->getTags();
	}
	
	public abstract function getGrid();
}