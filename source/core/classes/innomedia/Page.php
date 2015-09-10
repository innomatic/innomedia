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
 * @license    http://www.innomatic.io/license/   BSD License
 * @link       http://www.innomatic.io
 * @since      Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright Copyright 2008-2014 Innoteam Srl
 * @since 1.0
 */
class Page
{
    /**
     * Innomedia context.
     *
     * @var \Innomedia\Context
     */
    protected $context;

    /**
     * Data access for current tenant.
     *
     * @var \Innomatic\Dataaccess\DataAccess
     */
    protected $domainDa;

    protected $scopeSession;

    /**
     * Module name.
     *
     * @var string
     */
    protected $module;

    /**
     * Page type.
     *
     * @var string
     */
    protected $page;

    /**
     * Content page id.
     *
     * Not set for static pages.
     *
     * @var integer
     */
    protected $id;

    /**
     * Content page parent id.
     *
     * Set to 0 when the page is a child of the home page.
     * @var integer
     */
    protected $parentId = 0;

    /**
     * Page definition file path in web app file system.
     *
     * @var string
     */
    protected $pageDefFile;

    /**
     * Layout name
     *
     * @var string
     */
    protected $layout;

    /**
     * Page parameters array.
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * Page theme to be used for rendering the page.
     *
     * @var string
     */
    protected $theme;

    protected $grid;

    protected $isValid = true;

    /**
     * Boolean set to true when this is a content page.
     *
     * @var boolean
     */
    protected $requiresId = true;

    /**
     * Internal name for content pages.
     *
     * @var string
     */
    protected $name;

    protected $blocks = array();

    protected $userBlocks = array();

    protected $instanceBlocks = array();

    protected $cellParameters = array();

    public function __construct(
        $module,
        $page,
        $id = 0,
        $scopeSession = 'frontend'
    ) {
        $this->context = Context::instance('\Innomedia\Context');

        $this->domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        // TODO Add fallback module/page as optional welcome page
        $this->scopeSession = $scopeSession;
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
                "SELECT blocks, params, name
                FROM innomedia_pages
                WHERE page=".$this->domainDa->formatText($this->module.'/'.$this->page).
                " AND id={$this->id}"
            );

            if ($pagesParamsQuery->getNumberRows() > 0) {
                $this->name           = $pagesParamsQuery->getFields('name');

                $params = json_decode($pagesParamsQuery->getFields('params'), true);
                $this->parameters = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales(null, $params, $this->scopeSession);

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
        } else {
            // This is not a content page, however it may have page level
            // parameters in database
            $pagesParamsQuery = $this->domainDa->execute(
                "SELECT params
                FROM innomedia_pages
                WHERE page=".$this->domainDa->formatText($this->module.'/'.$this->page)
            );

            if ($pagesParamsQuery->getNumberRows() > 0) {
                $json_params = json_decode($pagesParamsQuery->getFields('params'), true);
                $this->parameters = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales(null, $json_params, $this->scopeSession);
                // Parameters variable must be an array
                if (!is_array($this->parameters)) {
                    $this->parameters = array();
                }
            }
        }

        // Get parameters for global scope blocks
        $blocksParamsQuery = $this->domainDa->execute(
            "SELECT block, params, counter
            FROM innomedia_blocks
            WHERE page IS NULL AND pageid IS NULL"
        );
        while (!$blocksParamsQuery->eof) {
            $block = $blocksParamsQuery->getFields('block');
            $json_params = json_decode($blocksParamsQuery->getFields('params'), true);
            $params_for_lang = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales($block, $json_params, $this->scopeSession);
            $blockParams[$block][$blocksParamsQuery->getFields('counter')] = $params_for_lang;
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
            if (isset($layout_def['blocks'])) {
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
            $block = $blocksParamsQuery->getFields('block');
            $json_params = json_decode($blocksParamsQuery->getFields('params'), true);
            $params_for_lang = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales($block, $json_params, $this->scopeSession);
            $blockParams[$block][$blocksParamsQuery->getFields('counter')] = $params_for_lang;
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
            $block = $blocksParamsQuery->getFields('block');
            $json_params = json_decode($blocksParamsQuery->getFields('params'), true);
            $params_for_lang = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales($block, $json_params, $this->scopeSession);
            $blockParams[$block][$blocksParamsQuery->getFields('counter')] = $params_for_lang;
            $blocksParamsQuery->moveNext();
        }

        foreach ($instanceBlocks as $blockDef) {
            $counter = isset($blockDef['counter']) ? $blockDef['counter'] : 1;
            $this->instanceBlocks[] = array(
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
        $this->setPredefinedTags($this->grid);

        if (!is_array($this->instanceBlocks)) {
            $this->instanceBlocks = array();
        }
        // Merge the blocks lists
        $blocks = array_merge($this->blocks, $this->userBlocks, $this->instanceBlocks);

        // Load the parsed blocks
        foreach ($blocks as $blockDef) {
            $block = Block::load(
                $this->context,
                $this,
                $this->grid,
                $blockDef['module'],
                $blockDef['name'],
                $blockDef['counter'],
                $blockDef['row'],
                $blockDef['column'],
                $blockDef['position'],
                $blockDef['params']
            );
            if (! is_null($block)) {
                $this->setPredefinedTags($block);
                $block->set('block_module'   , $blockDef['module']);
                $block->set('block_name'     , $blockDef['name']);
                $block->set('block_row'      , $blockDef['row']);
                $block->set('block_column'   , $blockDef['column']);
                $block->set('block_position' , $blockDef['position']);
                $block->set('block_counter'  , $blockDef['counter']);

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

    public function setPredefinedTags($block)
    {
        // Base tags
        $block->set('receiver', $this->context->getRequest()->getUrlPath(true));
        $block->set('baseurl', $this->context->getRequest()->getUrlPath(false) . '/');
        $block->set('module', $this->getModule());
        $block->set('page', $this);

        // Internal page name
        $block->set('page_name', $this->getName());

        // Set page parameters as tags
        $pageParams = $this->getParameters();
        foreach ($pageParams as $paramName => $paramValue) {
            $block->set('page_'.$paramName, $paramValue);
        }

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

        $block->set('xajax_js', $xajax_js);

        return $this;
    }

    public function getBlocksParameters()
    {
        if ($this->id == 0) {
            return array();
        }

        $blockParams = array();
        $blocksQuery = $this->domainDa->execute(
            "SELECT block, counter, params
            FROM innomedia_blocks
            WHERE pageid={$this->id}"
        );

        while (!$blocksQuery->eof) {
            $block = $blocksQuery->getFields('block');
            $json_params = json_decode($blocksQuery->getFields('params'), true);
            $params_for_lang = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales($block, $json_params, $this->scopeSession);
            $blockParams[$block][$blocksQuery->getFields('counter')] = $params_for_lang;
            $blocksQuery->moveNext();
        }

        return $blockParams;
    }

    public function savePageLevelParameters()
    {
        if ($this->id != 0) {
            return false;
        }

        // Check if parameters already exist in database
        $pagesParamsQuery = $this->domainDa->execute(
            "SELECT params
            FROM innomedia_pages
            WHERE page=".$this->domainDa->formatText($this->module.'/'.$this->page)
        );

        if ($pagesParamsQuery->getNumberRows() > 0) {

            $params = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocalesForUpdate(
                null,
                $pagesParamsQuery->getFields('params'),
                $this->parameters,
                'backend'
            );

            return $this->domainDa->execute(
                "UPDATE innomedia_pages
                SET
                params =".$this->domainDa->formatText(json_encode($params))."
                WHERE page=".$this->domainDa->formatText($this->module.'/'.$this->page)
            );
        } else {
            $id = $this->domainDa->getNextSequenceValue('innomedia_pages_id_seq');

            $current_language = \Innomedia\Locale\LocaleWebApp::getCurrentLanguage('backend');

            $params = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocalesForUpdate(
                null,
                null,
                $this->parameters,
                'backend'
            );

            if ($this->domainDa->execute(
                'INSERT INTO innomedia_pages (id, page, params) VALUES ('.
                $id.','.$this->domainDa->formatText($this->module.'/'.$this->page).','.
                $this->domainDa->formatText(json_encode($params, true)).')'
            )) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Add a content page in the database.
     *
     * @param number $parentId Parent page number. 0 = home page.
     * @return boolean
     */
    public function addContent($parentId = 0)
    {
        $id = $this->domainDa->getNextSequenceValue('innomedia_pages_id_seq');

        if ($this->domainDa->execute(
            'INSERT INTO innomedia_pages (id, page, name) VALUES ('.
            $id.','.$this->domainDa->formatText($this->module.'/'.$this->page).','.
            $this->domainDa->formatText($this->name).')'
        )) {
            $this->id = $id;

            // Set the page name for the page tree path.
            $pageName = isset($this->parameters['slug']) ? $this->parameters['slug'] : '';

            // Fallback to page title if the page slug is empty.
            if (!strlen($pageName)) {
                $pageName = isset($this->parameters['title']) ? $this->parameters['title'] : '';
            }

            // Fallback to internal page name if the page title is empty.
            if (!strlen($pageName)) {
                $pageName = $this->name;
            }

            // Fallback to page id if the page name is still empty.
            if (!strlen($pageName)) {
                $pageName = $id;
            }

            // Add the page to the pages tree.
            $tree = new PageTree();
            $tree->addPage($this->module, $this->page, $id, $parentId, $pageName);

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

        $pagesParamsQuery = $this->domainDa->execute(
            "SELECT params
            FROM innomedia_pages
            WHERE id = {$this->id}"
        );

        $params = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocalesForUpdate(
            null,
            $pagesParamsQuery->getFields('params'),
            $this->parameters,
            'backend'
        );

        // Update the page database row.
        $updated = $this->domainDa->execute(
            "UPDATE innomedia_pages
            SET
            name        =".$this->domainDa->formatText($this->name).",
            params      =".$this->domainDa->formatText(json_encode($params)).",
            blocks      =".$this->domainDa->formatText(json_encode($this->instanceBlocks))."
            WHERE id={$this->id}"
        );

        if (!$updated) {
            return false;
        }

        // Set the page name for the page tree path.
        $pageName = isset($this->parameters['slug']) ? $this->parameters['slug'] : '';

        // Fallback to page title if the page slug is empty.
        if (!strlen($pageName)) {
            $pageName = isset($this->parameters['title']) ? $this->parameters['title'] : '';
        }

        // Fallback to internal page name if the page title is empty.
        if (!strlen($pageName)) {
            $pageName = $this->name;
        }

        // Fallback to page id if the page name is still empty.
        if (!strlen($pageName)) {
            $pageName = $id;
        }

        // Rename the page tree path if needed.
        $tree = new PageTree();
        $tree->renamePage($this->id, $pageName);
    }

    /* public deleteContent($deleteChildren = true) {{{ */
    /**
     * Delete a content page from the database.
     *
     * @param bool $deleteChildren Set to true if the method should also delete
     * children pages.
     * @access public
     * @return void
     */
    public function deleteContent($deleteChildren = true)
    {
        if ($this->id == 0) {
            return false;
        }

        // If the page children must also be removed, let the page tree class
        // handle content delete action.
        if ($deleteChildren == true) {
            $tree = new PageTree();
            return $tree->removePage($this->id);
        }

        $innomaticContainer = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');

        if ($this->domainDa->execute("DELETE FROM innomedia_pages WHERE id={$this->id}")) {
            $this->domainDa->execute("DELETE FROM innomedia_blocks WHERE pageid={$this->id}");

            // @TODO: convert the following code in the code with the use of hook
            // Start - code for delete element of menu ordering
            $app_deps = new \Innomatic\Application\ApplicationDependencies(
                $innomaticContainer->getDataAccess()
            );
            if ($app_deps->isInstalled('innomedia-menu-editor') && $app_deps->isEnabled('innomedia-menu-editor', $innomaticContainer->getCurrentTenant()->getDomainId())) {

                //delete element from secondary menu ordering
                $editorMenu = new \Innomedia\Menu\Editor\Menu(
                    DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
                    null,
                    'menu/includemenu'
                );
                $editorMenu->removeElementFromMenuByPageID($this->id);
            }
            // End - code for delete element of menu ordering

            $this->id = 0;
            return true;
        } else {
            return false;
        }
    }
    /* }}} */

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

        $pagesParamsQuery = $this->domainDa->execute(
            "SELECT blocks
            FROM innomedia_pages
            WHERE page=".$this->domainDa->formatText($this->module.'/'.$this->page).
            " AND id={$this->id}"
        );

        if ($pagesParamsQuery->getNumberRows() > 0) {
            return json_decode($pagesParamsQuery->getFields('blocks'), true);
        }

        return array();
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

    public function getPageUrl($fullPath = false)
    {
        $url = '';

        // If this is a content page, try to get the url from its page path.
        if (strlen($this->id)) {
            $tree = new PageTree();
            $url = $tree->getPagePath($this->id);
        }

        // If the page path was not found, or the page is static, build the url
        // from the module and page name.
        if ($url === false or strlen($url) == 0) {
            $url = $this->module.'/'.$this->page;
            if (strlen($this->id)) {
                $url .= '/'.$this->id;
            }
        }

        // If $fullPath is set to true, return an absolute url.
        if ($fullPath) {
            $webAppUrl = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['webappurl'];
            if (substr($webAppUrl, -1) != '/') {
                $url = '/'.$url;
            }
            $url = $webAppUrl.$url;
        }

        return $url;
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

    /* public getModulePageFromId($pageId) {{{ */
    /**
     * Finds the module and page type for the given content page id.
     *
     * @param integer $pageId Content page id number.
     * @static
     * @access public
     * @return array
     */
    public static function getModulePageFromId($pageId)
    {
        $dataAccess = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $pagesParamsQuery = $dataAccess->execute(
            "SELECT page
            FROM innomedia_pages
            WHERE id = $pageId"
        );

        if ($pagesParamsQuery->getNumberRows() == 0) {
            return false;
        } else {
            list($module, $page) = explode('/', $pagesParamsQuery->getFields('page'));
            return [
                'module' => $module,
                'page'   => $page
            ];
        }
    }
    /* }}} */
}

?>
