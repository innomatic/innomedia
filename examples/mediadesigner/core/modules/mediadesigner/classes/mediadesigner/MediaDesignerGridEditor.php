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

class MediaDesignerGridEditor extends \Innomedia\Block {
    protected $blocks = array ();
    protected $theme;
    protected $rows;
    protected $columns;

    public function run(WebAppRequest $request, WebAppResponse $response) {
        $modules = $this->context->getModulesList();
        $module = '';
        $changed_page = false;

        if ($this->context->getSession()->isValid('mediadesigner_editmodule')) {
            if (in_array($this->context->getSession()->get('mediadesigner_editmodule'), $modules)) {
                $module = $this->context->getSession()->get('mediadesigner_editmodule');
            }
        }
        if ($request->parameterExists('mediadesigner_editmodule')) {
            if ($request->getParameter('mediadesigner_editmodule') != $module) {
                $changed_page = true;
            }
            $module = $request->getParameter('mediadesigner_editmodule');
            $this->context->getSession()->put('mediadesigner_editmodule', $module);
        }
        if (!strlen($module)) {
            $page = 'home';
        }

        $page = '';
        if ($this->context->getSession()->isValid('mediadesigner_editpage')) {
            $page = $this->context->getSession()->get('mediadesigner_editpage');
        }
        if ($request->parameterExists('mediadesigner_editpage')) {
            if ($request->getParameter('mediadesigner_editpage') != $page) {
                $changed_page = true;
            }
            $page = $request->getParameter('mediadesigner_editpage');
            $this->context->getSession()->put('mediadesigner_editpage', $page);
        }
        if (!strlen($page)) {
            $page = 'index';
        }

        if ($changed_page) {
            $this->resetChanges();
        }

        if ($this->context->getSession()->isValid('mediadesigner_blocks')) {
        	$this->set('modified', true);
        } else {
        	$this->set('modified', false);
        }

        $this->parsePage($this->context->getPagesHome($module).$page.'.yml');

        if ($request->parameterExists('mediadesigner_action')) {
            $row = $request->getParameter('mediadesigner_row');
            $column = $request->getParameter('mediadesigner_column');
            $position = $request->getParameter('mediadesigner_position');

            switch ($request->getParameter('mediadesigner_action')) {
                case 'addblock' :
                    $this->addBlock($request->getParameter('mediadesigner_blockmodule'), $request->getParameter('mediadesigner_block'), $row, $column, $position);
                    break;
                case 'removeblock' :
                    $this->removeBlock($row, $column, $position);
                    break;
                case 'upblock' :
                    $this->moveBlock($row, $column, $position, 'up');
                    break;
                case 'downblock' :
                    $this->moveBlock($row, $column, $position, 'down');
                    break;
                case 'leftblock' :
                    $this->moveBlock($row, $column, $position, 'left');
                    break;
                case 'rightblock' :
                    $this->moveBlock($row, $column, $position, 'right');
                    break;
                case 'raiseblock' :
                    $this->moveBlock($row, $column, $position, 'raise');
                    break;
                case 'lowerblock' :
                    $this->moveBlock($row, $column, $position, 'lower');
                    break;
                case 'save' :
                    if ($this->savePage($module, $page)) {
                    	$this->set('modified', false);
                    }
                    break;
                case 'revert' :
                    $this->resetChanges();
                    $this->parsePage($this->context->getPagesHome($module).$page.'.yml');
		        	$this->set('modified', false);
                    break;
                case 'addrow' :
                    $this->addRow();
                    break;
                case 'addcolumn' :
                    $this->addColumn();
                    break;
                case 'removerow' :
                    $this->removeRow($row);
                    break;
                case 'removecolumn' :
                    $this->removeColumn($column);
                    break;
            }
        }

        $this->setArray('blocks', $this->blocks);
        $this->set('rows', $this->rows);
        $this->set('columns', $this->columns);
        $this->set('editingmodule', $module);
        $this->set('editingpage', $page);
        $this->set('receiver', $this->grid->get('receiver'));
        $this->set('baseurl', $this->grid->get('baseurl'));
    }

    protected function parsePage($page) {
        if (!file_exists($page)) {
            return;
        }

        // Checks if the page definition exists in session
        if (!$this->context->getSession()->isValid('mediadesigner_blocks')) {
            $def = yaml_parse_file($page);
            $result = array ();
            $rows = $columns = 0;
            $theme = $def['theme'];
            foreach ($def['blocks'] as $blockDef) {
                $result[$blockDef['row']][$blockDef['column']][$blockDef['position']] = array ('module' => $blockDef['module'], 'name' => $blockDef['name']);
                if ($blockDef['row'] > $rows) {
                    $rows = $blockDef['row'];
                }
                if ($blockDef['column'] > $columns) {
                    $columns = $blockDef['column'];
                }
            }
            ksort($result);
            foreach ($result as $row => $column) {
                ksort($result[$row]);
                foreach ($result[$row] as $row2 => $column2) {
                	// TODO fixare warning togliendo @
                    @ksort($result[$row][$column2]);
                }
            }
            // Stores page definition in the session
            $this->context->getSession()->put('mediadesigner_blocks', $result);
            $this->context->getSession()->put('mediadesigner_theme', $theme);
            $this->context->getSession()->put('mediadesigner_rows', $rows);
            $this->context->getSession()->put('mediadesigner_columns', $columns);
        } else {
            // Retrieves page definition from the session
            $result = $this->context->getSession()->get('mediadesigner_blocks');
            $theme = $this->context->getSession()->get('mediadesigner_theme');
            $rows = $this->context->getSession()->get('mediadesigner_rows');
            $columns = $this->context->getSession()->get('mediadesigner_columns');
        }

        $this->blocks = & $result;
        $this->rows = $rows ? $rows : 1;
        $this->columns = $columns ? $columns : 1;
        $this->theme = $theme;
    }

    protected function savePage($module, $page) {
        $file = $this->context->getPagesHome($module).$page.'.yml';
        $yaml = array();

		if (strlen($this->theme)) {
            $yaml['theme'] = $this->theme;
		}

		foreach ($this->blocks as $row => $columns) {
		    foreach ($columns as $column => $positions) {
		        foreach ($positions as $position => $block) {
                    $yaml['blocks'][] = array(
                        'module' => $block['module'],
                        'name' => $block['name'],
                        'row' => $row,
                        'column' => $colum,
                        'position' => $position
                    );
		        }
		    }
		}

        if (!yaml_emit_file($file, $yaml)) {
            return false;
        }

		$this->resetChanges();
		return true;
    }

    protected function addBlock($module, $block, $row, $column, $position) {
        $this->blocks[$row][$column][$position] = array('module' => $module, 'name' => $block);
        $this->context->getSession()->put('mediadesigner_blocks', $this->blocks);
    }

    protected function moveBlock($row, $column, $position, $direction) {
        switch ($direction) {
            case 'up' :
                if ($row == 1) {
                    break;
                }
                $positions = count($this->blocks[$row -1][$column]);
                $this->blocks[$row -1][$column][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'down' :
                $positions = count($this->blocks[$row +1][$column]);
                $this->blocks[$row +1][$column][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'right' :
                $positions = count($this->blocks[$row][$column +1]);
                $this->blocks[$row][$column +1][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'left' :
                if ($colum == 1) {
                    break;
                }
                $positions = count($this->blocks[$row][$column -1]);
                $this->blocks[$row][$column -1][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'raise' :
                if ($position == 1) {
                    break;
                }
                $old_block = $this->blocks[$row][$column][$position];
                $this->blocks[$row][$column][$position] = $this->blocks[$row][$column][$position -1];
                $this->blocks[$row][$column][$position -1] = $old_block;
                $this->context->getSession()->put('mediadesigner_blocks', $this->blocks);
                break;
            case 'lower' :
                if ($position == count($this->blocks[$row][$column])) {
                    break;
                }
                $old_block = $this->blocks[$row][$column][$position];
                $this->blocks[$row][$column][$position] = $this->blocks[$row][$column][$position +1];
                $this->blocks[$row][$column][$position +1] = $old_block;
                $this->context->getSession()->put('mediadesigner_blocks', $this->blocks);
                break;
        }
    }

    protected function removeBlock($row, $column, $position) {
        if (count($this->blocks[$row][$column]) > $position) {
            for ($i = $position; $i < count($this->blocks[$row][$column]); $i ++) {
                $this->blocks[$row][$column][$i] = $this->blocks[$row][$column][$i +1];
            }
        }
        unset ($this->blocks[$row][$column][count($this->blocks[$row][$column])]);
        $this->context->getSession()->put('mediadesigner_blocks', $this->blocks);
    }

    protected function removeRow($row) {
        if ($this->rows > $row) {
            for ($i = $row; $i < $this->rows; $i ++) {
                $this->blocks[$i] = $this->blocks[$i +1];
            }
        }
        unset ($this->blocks[$this->rows]);
        $this->rows--;
        $this->context->getSession()->put('mediadesigner_rows', $this->rows);
        $this->context->getSession()->put('mediadesigner_blocks', $this->blocks);
    }

    protected function removeColumn($column) {
        $rows = count($this->blocks);
        $columns = $this->columns;

        if ($columns > $column) {
            for ($i = 1; $i <= $rows; $i++) {
                for ($j = $column; $j <= $columns; $j++) {
                    $this->blocks[$i][$j] = $this->blocks[$i][$j +1];
                }
            }
        }

        for ($i = 1; $i <= $rows; $i ++) {
            unset ($this->blocks[$i][$this->columns]);
        }
        $this->columns--;
        $this->context->getSession()->put('mediadesigner_columns', $this->columns);
        $this->context->getSession()->put('mediadesigner_blocks', $this->blocks);
    }

    protected function addRow() {
        $rows = $this->context->getSession()->get('mediadesigner_rows');
        $this->context->getSession()->put('mediadesigner_rows', $rows +1);
        $this->rows++;
    }

    protected function addColumn() {
        $columns = $this->context->getSession()->get('mediadesigner_columns');
        $this->context->getSession()->put('mediadesigner_columns', $columns +1);
        $this->columns++;
    }

    protected function resetChanges() {
        $this->context->getSession()->remove('mediadesigner_blocks');
        $this->context->getSession()->remove('mediadesigner_theme');
        $this->context->getSession()->remove('mediadesigner_rows');
        $this->context->getSession()->remove('mediadesigner_columns');
    }
}

?>
