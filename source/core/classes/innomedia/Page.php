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
 * @since 1.0
 */
class Page
{
    protected $context;

    protected $domainDa;

    protected $module;

    protected $page;

    protected $id;

    protected $pageDefFile;

    /**
     * Layout name
     *
     * @var string
     */
    protected $layout;

    /**
     * Page parameters array
     *
     * @var array
     */
    protected $parameters = array();

    protected $theme;

    protected $grid;

    protected $isValid = true;

    protected $requiresId = true;

    protected $name;

    protected $urlKeywords;

    protected $blocks = array();

    protected $userBlocks = array();

    protected $instanceBlocks = array();

    protected $cellParameters = array();

    public function __construct(
        $module,
        $page,
        $id = 0
    ) {
        $this->context = Context::instance('\Innomedia\Context');

        $this->domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        // TODO Add fallback module/page as optional welcome page
        $this->module = strlen($module) ? $module : 'home';
        $this->page = strlen($page) ? $page : 'index';
        $this->id = (int)$id;
        if (!is_int($this->id)) {
            $this->id = 0;
        }
        $this->isValid = true;
        $this->theme = 'default';
    }

    protected function getPageDefFile()
    {
        return file_exists($this->context->getPagesHome($this->module).$this->page . '.local.yml') ?
            $this->context->getPagesHome($this->module) . $this->page . '.local.yml' :
            $this->context->getPagesHome($this->module) . $this->page . '.yml';
    }

    public function parsePage()
    {
        $pageDefFile = $this->getPageDefFile();

        // Check if the YAML file for the given page exists
        if (! file_exists($pageDefFile)) {
            return false;
        }

        // Load the page YAML structure
        $page_def = yaml_parse_file($pageDefFile);

        // Check if the page requires a valid id
        if (isset($page_def['properties']['requiresid']) && $page_def['properties']['requiresid'] == true) {
            $this->requiresId = true;

            // Check if the id has been given
            if ($this->id == 0) {
                $this->isValid = false;
                return false;
            }
        } else {
            $this->requiresId = false;
        }

        // Get page cells parameters
        if (isset($page_def['cells'])) {
            foreach ($page_def['cells'] as $cellDef) {
                $this->cellParameters[$cellDef['row']][$cellDef['column']] = $cellDef['parameters'];
            }
        }

        // Load page and instance blocks parameters for this instance of the page, if available
        $blockParams = array();
        $instanceBlocks = array();

        if ($this->id != 0) {
            $pagesParamsQuery = $this->domainDa->execute(
                "SELECT blocks, params, name, urlkeywords
                FROM innomedia_pages
                WHERE page=".$this->domainDa->formatText($this->module.'/'.$this->page).
                " AND id={$this->id}"
            );

            if ($pagesParamsQuery->getNumberRows() > 0) {
                $this->name           = $pagesParamsQuery->getFields('name');
                $this->urlKeywords    = $pagesParamsQuery->getFields('urlkeywords');
                $this->parameters     = json_decode($pagesParamsQuery->getFields('params'), true);
                // Parameters variable must be an array
                if (!is_array($this->parameters)) {
                    $this->parameters = array();
                }
                $this->instanceBlocks = $instanceBlocks = json_decode($pagesParamsQuery->getFields('blocks'), true);

                if (!is_array($instanceBlocks)) {
                    $instanceBlocks = array();
                }
            } elseif ($this->requiresId) {
                // This page id doesn't exist
                $this->isValid = false;
                return false;
            }
        }

        // Get parameters for global scope blocks
        $blocksParamsQuery = $this->domainDa->execute(
            "SELECT block, params, counter
            FROM innomedia_blocks
            WHERE page IS NULL AND pageid IS NULL"
        );
        while (!$blocksParamsQuery->eof) {
            $blockParams[$blocksParamsQuery->getFields('block')][$blocksParamsQuery->getFields('counter')] = json_decode($blocksParamsQuery->getFields('params'), true);
            $blocksParamsQuery->moveNext();
        }

        // Get page layout if defined and check if the YAML file for the given layout exists
        $layout = false;

        if (!strlen($page_def['layout'])) {
            $page_def['layout'] = 'default';
        }

        $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.local.yml';
        if (file_exists($layoutFileName)) {
            $layout = true;
        } else {
            $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.yml';
            if (file_exists($layoutFileName)) {
                $layout = true;
            }
        }

        if ($layout) {
            // Set the layout name
            $this->layout = $page_def['layout'];

            // Load the layout YAML structure
            $layout_def = yaml_parse_file($layoutFileName);

            // Get layout level theme if defined
            if (strlen($layout_def['theme'])) {
                $this->theme = $layout_def['theme'];
            }

            // Get block list
            foreach ($layout_def['blocks'] as $blockDef) {
                $counter = isset($blockDef['counter']) ? $blockDef['counter'] : 1;
                $this->blocks[] = array(
                    'module' => $blockDef['module'],
                    'name'   => $blockDef['name'],
                    'counter' => $counter,
                    'row' => $blockDef['row'],
                    'column' => $blockDef['column'],
                    'position' => $blockDef['position'],
                    'params' => isset($blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter] : array()
                );
            }
        }

        // Get page level theme if defined, overriding layout level theme
        if (strlen($page_def['theme'])) {
            $this->theme = $page_def['theme'];
        }

        // Get page level block parameters
        // If this is a content page, it must also retrieve block parameters with the page id
        $blocksParamsQuery = $this->domainDa->execute(
            "SELECT block,params,counter
            FROM innomedia_blocks
            WHERE page=".$this->domainDa->formatText($this->module.'/'.$this->page).
            ($this->requiresId() ? "AND (pageid IS NULL OR pageid={$this->id})" : "AND pageid IS NULL")
        );

        while (!$blocksParamsQuery->eof) {
            $blockParams[$blocksParamsQuery->getFields('block')][$blocksParamsQuery->getFields('counter')] = json_decode($blocksParamsQuery->getFields('params'), true);
            $blocksParamsQuery->moveNext();
        }

        // Get page block list
        foreach ($page_def['blocks'] as $blockDef) {
            $counter        = isset($blockDef['counter']) ? $blockDef['counter'] : 1;
            $this->blocks[] = array(
                'module'    => $blockDef['module'],
                'name'      => $blockDef['name'],
                'counter'   => $counter,
                'row'       => $blockDef['row'],
                'column'    => $blockDef['column'],
                'position'  => $blockDef['position'],
                'params'    => isset($blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter] : array()
            );
        }

        // Page level user blocks
        $userBlocks = array();
        if (isset($page_def['userblocks'])) {
            foreach ($page_def['userblocks'] as $blockDef) {
                // @todo check if the given block type is supported by the cell
                // in the parameters list

                $counter            = isset($blockDef['counter']) ? $blockDef['counter'] : 1;
                $this->userBlocks[] = array(
                    'module'        => $blockDef['module'],
                    'name'          => $blockDef['name'],
                    'counter'       => $counter,
                    'row'           => $blockDef['row'],
                    'column'        => $blockDef['column'],
                    'position'      => $blockDef['position'],
                    'params'        => isset($blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter] : array()
                );
            }
        }

        // Get page instance level block parameters
        $blocksParamsQuery = $this->domainDa->execute(
            "SELECT block,params,counter
            FROM innomedia_blocks
            WHERE pageid={$this->id}
            AND page=".$this->domainDa->formatText($this->module.'/'.$this->page));

        while (!$blocksParamsQuery->eof) {
            $blockParams[$blocksParamsQuery->getFields('block')][$blocksParamsQuery->getFields('counter')] = json_decode($blocksParamsQuery->getFields('params'), true);
            $blocksParamsQuery->moveNext();
        }

        foreach ($instanceBlocks as $blockDef) {
            $counter = isset($blockDef['counter']) ? $blockDef['counter'] : 1;
            $this->blocks[] = array(
                'module' => $blockDef['module'],
                'name'   => $blockDef['name'],
                'counter' => $counter,
                'row' => $blockDef['row'],
                'column' => $blockDef['column'],
                'position' => $blockDef['position'],
                'params' => isset($blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter] : array()
            );
        }
    }

    public function loadBlocks()
    {
        // Load the grid
        $this->grid = new Grid($this);

        // Merge the blocks lists
        $blocks = array_merge($this->blocks, $this->userBlocks, $this->instanceBlocks);

        // Load the parsed blocks
        foreach ($blocks as $blockDef) {
            $block = Block::load(
                $this->context,
                $this->grid,
                $blockDef['module'],
                $blockDef['name'],
                $blockDef['counter'],
                $blockDef['params']
            );

            if (! is_null($block)) {
                $this->grid->addBlock(
                    $block,
                    $blockDef['row'],
                    $blockDef['column'],
                    $blockDef['position']
                );
            }
        }

        // Blocks must be properly sorted for the grid loop
        $this->grid->sortBlocks();
    }

    public function addContent()
    {
        $id = $this->domainDa->getNextSequenceValue('innomedia_pages_id_seq');

        if ($this->domainDa->execute(
            'INSERT INTO innomedia_pages (id, page, name) VALUES ('.
            $id.','.$this->domainDa->formatText($this->module.'/'.$this->page).','.
            $this->domainDa->formatText($this->name).')'
        )) {
            $this->id = $id;
            return true;
        } else {
            return false;
        }
    }

    public function updateContent()
    {
        if ($this->id == 0) {
            return false;
        }

        return $this->domainDa->execute(
            "UPDATE innomedia_pages
            SET
            name        =".$this->domainDa->formatText($this->name).",
            params      =".$this->domainDa->formatText(json_encode($this->parameters)).",
            blocks      =".$this->domainDa->formatText(json_encode($this->instanceBlocks)).",
            urlkeywords =".$this->domainDa->formatText($this->urlkeywords)."
            WHERE id={$this->id}"
        );
    }

    public function deleteContent()
    {
        if ($this->id == 0) {
            return false;
        }

        if ($this->domainDa->execute("DELETE FROM innomedia_pages WHERE id={$this->id}")) {
            $this->domainDa->execute("DELETE FROM innomedia_blocks WHERE pageid={$this->id}");
            $this->id = 0;
            return true;
        } else {
            return false;
        }
    }

    public function getLayoutBlocks()
    {
        // Load the page YAML structure
        $page_def = yaml_parse_file($this->getPageDefFile());

        // Get page layout if defined and check if the YAML file for the given layout exists
        $layout = false;

        if (!strlen($page_def['layout'])) {
            $page_def['layout'] = 'default';
        }

        $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.local.yml';
        if (file_exists($layoutFileName)) {
            $layout = true;
        } else {
            $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.yml';
            if (file_exists($layoutFileName)) {
                $layout = true;
            }
        }

        if ($layout) {
            // Load the layout YAML structure
            $layout_def = yaml_parse_file($layoutFileName);

            $blocks = array();

            // Get block list
            foreach ($layout_def['blocks'] as $blockDef) {
                $blocks[] = $blockDef;
            }

            return $blocks;
        }

        return false;
    }

    public function getPageBlocks()
    {
        $blocks = array();

        // Load the page YAML structure
        $page_def = yaml_parse_file($this->getPageDefFile());

        // Get page block list
        foreach ($page_def['blocks'] as $blockDef) {
            $blocks[] = $blockDef;
        }

        return $blocks;
    }

    public function getPageInstanceBlocks()
    {
        if (!$this->isValid()) {
            return false;
        }

        return $this->instanceBlocks;
    }

    /* public getId() {{{ */
    /**
     * Returns the page id.
     *
     * @return integer page id.
     */
    public function getId()
    {
        return $this->id;
    }
    /* }}} */

    /* public requiresId() {{{ */
    /**
     * Checks if the page requires a valid id.
     *
     * A page requires a valid id when it is content based and it has multiple
     * instances.
     *
     * @return boolean true if the page requires a valid id.
     */
    public function requiresId()
    {
        return $this->requiresId;
    }
    /* }}} */

    /* public isValid() {{{ */
    /**
     * Checks if the given page id is valid in case the page requires the id.
     *
     * If the page doesn't require an id, it returns true.
     *
     * @return boolean true if the page is valid
     */
    public function isValid()
    {
        return $this->isValid;
    }
    /* }}} */

    /**
     * Returns the theme set for the current page.
     *
     * @return string current theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getPage()
    {
        return $this->page;
    }

    /* public getParameters() {{{ */
    /**
     * Return page parameters array.
     *
     * @return array parameters.
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    /* }}} */

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /* public setParameter($paramName, $paramValue) {{{ */
    /**
     * Sets a page parameter.
     *
     * @param string $paramName
     * @param string $paramValue
     * @return \Innomedia\Page
     */
    public function setParameter($paramName, $paramValue)
    {
        $this->parameters[$paramName] = $paramValue;
        return $this;
    }
    /* }}} */

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setUrlKeywords($keywords)
    {
        $this->urlKeywords = $keywords;
        return $this;
    }

    public function getUrlKeywords()
    {
        return $this->urlKeywords;
    }

    public function build()
    {
        $this->loadBlocks();

        if (is_object($this->grid)) {
            echo $this->grid->parse();
        } else {
            $this->context->getResponse()->sendError(\Innomatic\Webapp\WebAppResponse::SC_NOT_FOUND, $this->context->getRequest()->getRequestURI());
        }
    }

    /* public getPagesList() {{{ */
    /**
     * Returns a list of all the pages defined in the current webapp.
     *
     * @return array
     */
    public static function getPagesList()
    {
        $context = Context::instance('\Innomedia\Context');
        $list = array();
        if ($dm = opendir($context->getModulesHome())) {
            while (($module = readdir($dm)) !== false) {
                if ($module != '.' and $module != '..' and file_exists($context->getModulesHome().$module.'/pages/') and $dh = opendir($context->getModulesHome().$module.'/pages/')) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' and $file != '..' and is_file($context->getModulesHome().$module. '/pages/' . $file) and strrpos($file, '.yml') and !strrpos($file, '.local.yml')) {
                            $list[] = $module.'/'.substr($file, 0, strrpos($file, '.yml'));
                        }
                    }
                    closedir($dh);
                }
            }
            closedir($dm);
        }
        return $list;
    }
    /* }}} */

    /* public getInstancePagesList() {{{ */
    /**
     * Returns a list of all the pages in the current webapp requiring a valid id.
     *
     * @return array
     */
    public static function getInstancePagesList()
    {
        $context = Context::instance('\Innomedia\Context');

        $list = array();
        if ($dm = opendir($context->getModulesHome())) {
            while (($module = readdir($dm)) !== false) {
                if ($module != '.' and $module != '..' and file_exists($context->getModulesHome().$module.'/pages/') and $dh = opendir($context->getModulesHome().$module.'/pages/')) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' and $file != '..' and is_file($context->getModulesHome().$module. '/pages/' . $file) and strrpos($file, '.yml') and !strrpos($file, '.local.yml')) {
                            $pageName = substr($file, 0, strrpos($file, '.yml'));

                            if (file_exists($context->getModulesHome().$module. '/pages/'.$pageName.'.local.yml')) {
                                $yamlFile = $context->getModulesHome().$module. '/pages/'.$pageName.'.local.yml';
                            } else {
                                $yamlFile = $context->getModulesHome().$module. '/pages/'.$pageName.'.yml';
                            }
                            $pageDef = yaml_parse_file($yamlFile);
                            if (isset($pageDef['properties']['requiresid']) && $pageDef['properties']['requiresid'] == true) {
                                $list[] = $module.'/'.$pageName;
                            }
                        }
                    }
                    closedir($dh);
                }
            }
            closedir($dm);
        }
        return $list;
    }
    /* }}} */

    /* public getNoInstancePagesList() {{{ */
    /**
     * Returns a list of pages not requiring a valid id.
     *
     * @return array
     */
    public static function getNoInstancePagesList()
    {
        $context = Context::instance('\Innomedia\Context');

        $list = array();
        if ($dm = opendir($context->getModulesHome())) {
            while (($module = readdir($dm)) !== false) {
                if ($module != '.' and $module != '..' and file_exists($context->getModulesHome().$module.'/pages/') and $dh = opendir($context->getModulesHome().$module.'/pages/')) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' and $file != '..' and is_file($context->getModulesHome().$module. '/pages/' . $file) and strrpos($file, '.yml') and !strrpos($file, '.local.yml')) {
                            $pageName = substr($file, 0, strrpos($file, '.yml'));

                            if (file_exists($context->getModulesHome().$module. '/pages/'.$pageName.'.local.yml')) {
                                $yamlFile = $context->getModulesHome().$module. '/pages/'.$pageName.'.local.yml';
                            } else {
                                $yamlFile = $context->getModulesHome().$module. '/pages/'.$pageName.'.yml';
                            }
                            $pageDef = yaml_parse_file($yamlFile);
                            if (!isset($pageDef['properties']['requiresid']) or $pageDef['properties']['requiresid'] == false) {
                                $list[] = $module.'/'.$pageName;
                            }
                        }
                    }
                    closedir($dh);
                }
            }
            closedir($dm);
        }
        return $list;
    }
    /* }}} */

}

?>
