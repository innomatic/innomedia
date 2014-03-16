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
    protected $parameters;

    protected $theme;

    protected $grid;

    protected $isValid = true;

    protected $requiresId = true;

    protected $title;

    protected $instanceBlocks = array();

    public function __construct(
        $module,
        $page,
        $id = 0
    ) {
        $this->context = Context::instance('\Innomedia\Context');
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
        }

        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        // Load the grid
        $this->grid = new Grid($this);

        // Load page and parameters for this instance of the page, if available
        $blockParams = array();
        $instanceBlocks = array();

        if ($this->id != 0) {
            $pagesParamsQuery = $domainDa->execute(
                "SELECT blocks, params, title
                FROM innomedia_pages
                WHERE page=".$domainDa->formatText($this->module.'/'.$this->page).
                " AND id={$this->id}"
            );

            if ($pagesParamsQuery->getNumberRows() > 0) {
                $this->title = $pagesParamsQuery->getFields('title');
                $this->parameters = json_decode($pagesParamsQuery->getFields('params'), true);
                $this->instanceBlocks = $instanceBlocks = json_decode($pagesParamsQuery->getFields('blocks'), true);

                if (!is_array($instanceBlocks)) {
                    $instanceBlocks = array();
                }
            } elseif ($this->requiresId) {
                // This page id doesn't exist
                $this->isValid = false;
                return false;
            }

            $blocksParamsQuery = $domainDa->execute(
                "SELECT block, params, counter
                FROM innomedia_blocks
                WHERE page IS NULL AND pageid IS NULL"
            );

            while (!$blocksParamsQuery->eof) {
                $blockParams[$blocksParamsQuery->getFields('block')][$blocksParamsQuery->getFields('counter')] = json_decode($blocksParamsQuery->getFields('params'), true);
                $blocksParamsQuery->moveNext();
            }
        }

        // Get page layout if defined and check if the YAML file for the given layout exists
        $layout = false;

        if (strlen($page_def['layout'])) {
            $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.local.yml';
            if (file_exists($layoutFileName)) {
                $layout = true;
            } else {
                $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.yml';
                if (file_exists($layoutFileName)) {
                    $layout = true;
                }
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
                // Load the block
                $block = Block::load(
                    $this->context,
                    $this->grid,
                    $blockDef['module'],
                    $blockDef['name'],
                    $counter,
                    isset($blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter] : array()
                );

                if (! is_null($block)) {
                    // Add the block
                    $this->grid->addBlock(
                        $block,
                        $blockDef['row'],
                        $blockDef['column'],
                        $blockDef['position']
                    );
                }
            }
        }

        // Get page level theme if defined, overriding layout level theme
        if (strlen($page_def['theme'])) {
            $this->theme = $page_def['theme'];
        }

        // Get page level block parameters
        // If this is a content page, it must also retrieve block parameters with the page id
        $blocksParamsQuery = $domainDa->execute(
            "SELECT block,params,counter
            FROM innomedia_blocks
            WHERE page=".$domainDa->formatText($this->module.'/'.$this->page).
            ($this->requiresId() ? "AND (pageid IS NULL OR pageid={$this->id})" : "AND pageid IS NULL")
        );

        while (!$blocksParamsQuery->eof) {
            $blockParams[$blocksParamsQuery->getFields('block')][$blocksParamsQuery->getFields('counter')] = json_decode($blocksParamsQuery->getFields('params'), true);
            $blocksParamsQuery->moveNext();
        }

        // Get page block list
        foreach ($page_def['blocks'] as $blockDef) {
            $counter = isset($blockDef['counter']) ? $blockDef['counter'] : 1;
            $block = Block::load(
                $this->context,
                $this->grid,
                $blockDef['module'],
                $blockDef['name'],
                $counter,
                isset($blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter] : array()
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

        // Get page instance level block parameters
        $blocksParamsQuery = $domainDa->execute(
            "SELECT block,params,counter
            FROM innomedia_blocks
            WHERE pageid={$this->id}
            AND page=".$domainDa->formatText($this->module.'/'.$this->page));

        while (!$blocksParamsQuery->eof) {
            $blockParams[$blocksParamsQuery->getFields('block')][$blocksParamsQuery->getFields('counter')] = json_decode($blocksParamsQuery->getFields('params'), true);
            $blocksParamsQuery->moveNext();
        }

        foreach ($instanceBlocks as $blockDef) {
            $counter = isset($blockDef['counter']) ? $blockDef['counter'] : 1;
            $block = Block::load(
                $this->context,
                $this->grid,
                $blockDef['module'],
                $blockDef['name'],
                $counter,
                isset($blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']][$counter] : array()
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

        $this->grid->sortBlocks();
    }

    public function addContent()
    {
        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $id = $domainDa->getNextSequenceValue('innomedia_pages_id_seq');

        if ($domainDa->execute(
            'INSERT INTO innomedia_pages (id, page, title) VALUES ('.
            $id.','.$domainDa->formatText($this->module.'/'.$this->page).','.
            .$domainDa->formatText($this->title).')'
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

        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        return $domainDa->execute(
            "UPDATE innomatic_pages
            SET
            title=".$domainDa->formatText($this->title).",
            params=".$domainDa->formatText(json_encode($this->parameters)).",
            blocks=".$domainDa->formatText(json_encode($this->instanceBlocks))."
            WHERE id={$this->id}"
        );
    }

    public function deleteContent()
    {
        if ($this->id == 0) {
            return false;
        }

        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        if ($domainDa->execute("DELETE FROM innomatic_pages WHERE id={$this->id}")) {
            $this->id = 0;
            return true;
        } else {
            return false;
        }
    }

    public function getLayoutBlocks()
    {
        $blocks = array();

        // Load the page YAML structure
        $page_def = yaml_parse_file($this->getPageDefFile());

        // Get page layout if defined and check if the YAML file for the given layout exists
        $layout = false;

        if (strlen($page_def['layout'])) {
            $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.local.yml';
            if (file_exists($layoutFileName)) {
                $layout = true;
            } else {
                $layoutFileName = $this->context->getLayoutsHome().$page_def['layout'].'.yml';
                if (file_exists($layoutFileName)) {
                    $layout = true;
                }
            }
        }

        if ($layout) {
            // Load the layout YAML structure
            $layout_def = yaml_parse_file($layoutFileName);

            // Get block list
            foreach ($layout_def['blocks'] as $blockDef) {
                $blocks[$blockDef['module']][] = $blockDef['name'];
            }
        }

        return $blocks;
    }

    public function getPageBlocks($cumulative = false)
    {
        $blocks = array();

        // Load the page YAML structure
        $page_def = yaml_parse_file($this->getPageDefFile());

        // Get page block list
        foreach ($page_def['blocks'] as $blockDef) {
            $blocks[$blockDef['module']][] = $blockDef['name'];
        }

        return $blocks;
    }

    /*
    public function getPageInstanceBlocks($cumulative = false)
    {
        if (!$this->isValid()) {
            return false;
        }
    }
     */

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
     * Returns Context object.
     */
    public function getContext()
    {
        return $this->context;
    }

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
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function build()
    {
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
