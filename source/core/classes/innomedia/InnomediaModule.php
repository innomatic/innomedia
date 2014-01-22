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

require_once('innomedia/InnomediaContext.php');

/**
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
class InnomediaModule
{
    protected $context;
    protected $name;
    
    public function __construct(InnomediaContext $context, $moduleName)
    {
        $this->context = $context;
        $this->name = $moduleName;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getHome()
    {
        return $this->context->getHome().'core/innomedia/modules/'.$this->name.'/';
    }
    
    public function hasPages()
    {
        return file_exists($this->getHome().'pages');
    }
    
    public function hasBlocks()
    {
        return file_exists($this->getHome().'blocks');
    }
    
    public function getPagesList()
    {
        $list = array ();
        if (!$this->hasPages()) {
            return $list;
        }
        if ($dh = opendir($this->getHome().'pages')) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' and $file != '..' and is_file($this->getHome().'pages/'.$file) and strrpos($file, '.xml')) {
                    $list[] = substr($file, 0, strrpos($file, '.xml'));
                }
            }
            closedir($dh);
        }
        return $list;
    }
    
    public function getBlocksList()
    {
        $list = array ();
        if (!$this->hasBlocks()) {
            return $list;
        }
        if ($dh = opendir($this->getHome().'blocks')) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' and $file != '..' and is_file($this->getHome().'blocks/'.$file) and strrpos($file, '.xml')) {
                    $list[] = substr($file, 0, strrpos($file, '.xml'));
                }
            }
            closedir($dh);
        }
        return $list;
    }
}

?>