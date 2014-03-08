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

    protected $request;

    protected $response;

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

    protected $theme;

    protected $grid;

    public function __construct(
        Context $context,
        \Innomatic\Webapp\WebAppRequest $request,
        \Innomatic\Webapp\WebAppResponse $response,
        $module,
        $page,
        $id = 0
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->response = $response;
        // TODO Add fallback module/page as optional welcome page
        $this->module = strlen($module) ? $module : 'home';
        $this->page = strlen($page) ? $page : 'index';
        $this->id = $id;
        $this->theme = 'default';
        $this->pageDefFile = $context->getPagesHome($this->module) . $this->page . '.yml';
        $this->parsePage();
    }

    protected function parsePage()
    {
       // Check if the YAML file for the given page exists
        if (! file_exists($this->pageDefFile)) {
            return false;
        }

        // Load the grid
        $this->grid = new Grid($this);

        // Load block parameters for this instance of the page, is available
        $blockParams = array();
        if ($this->id != 0) {
            $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
                ->getCurrentDomain()
                ->getDataAccess();

            $blocksParamsQuery = $domainDa->execute(
                "SELECT block,params
                FROM innomedia_blocks
                WHERE page=".$domainDa->formatText($this->module.'/'.$this->page).
                "AND pageid={$this->id}");

            while (!$blocksParamsQuery->eof) {
                $blockParams[$blocksParamsQuery->getFields('block')] = unserialize($blocksParamsQuery->getFields('params'));
                $blocksParamsQuery->moveNext();
            }
        }
        // Load the page YAML structure
        $page_def = yaml_parse_file($this->pageDefFile);

        // Get page layout if defined and check if the YAML file for the given layout exists
        if (
            strlen($page_def['layout'])
            && file_exists($this->context->getLayoutsHome().$page_def['layout'].'.yml')) {
            // Set the layout name
            $this->layout = $page_def['layout'];

            // Load the layout YAML structure
            $layout_def = yaml_parse_file(
                $this->context->getLayoutsHome().
                $this->layout.
                '.yml'
            );

            // Get layout level theme if defined
            if (strlen($layout_def['theme'])) {
                $this->theme = $layout_def['theme'];
            }

            // Get block list
            foreach ($layout_def['blocks'] as $blockDef) {
                // Load the block
                $block = Block::load(
                    $this->context,
                    $this->grid,
                    $blockDef['module'],
                    $blockDef['name'],
                    isset($blockParams[$blockDef['module'].'/'.$blockDef['name']]) ? $blockParams[$blockDef['module'].'/'.$blockDef['name']] : array()
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

        // Get block list
        foreach ($page_def['blocks'] as $blockDef) {
            $block = Block::load(
                $this->context,
                $this->grid,
                $blockDef['module'],
                $blockDef['name']
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
    }

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

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function build()
    {
        if (is_object($this->grid)) {
            echo $this->grid->parse();
        } else {
            $this->response->sendError(\Innomatic\Webapp\WebAppResponse::SC_NOT_FOUND, $this->request->getRequestURI());
        }
    }
}

?>
