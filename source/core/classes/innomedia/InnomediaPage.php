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
class InnomediaPage
{

    protected $context;

    protected $request;

    protected $response;

    protected $module;

    protected $page;

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
        InnomediaContext $context,
        \Innomatic\Webapp\WebAppRequest $request,
        \Innomatic\Webapp\WebAppResponse $response,
        $module,
        $page
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->response = $response;
        // TODO Add fallback module/page as optional welcome page
        $this->module = strlen($module) ? $module : 'home';
        $this->page = strlen($page) ? $page : 'index';
        $this->theme = 'default';
        $this->pageDefFile = $context->getPagesHome($this->module) . $this->page . '.xml';
        $this->parsePage();
    }

    protected function parsePage()
    {
        // Check if the XML file for the given page exists
        if (! file_exists($this->pageDefFile)) {
            return false;
        }

        // Load the grid
        $this->grid = new InnomediaGrid($this);

        // Load the page XML structure
        $page_def = simplexml_load_file($this->pageDefFile);

        // Get page layout if defined and check if the XML file for the given layout exists
        if (
            strlen("$page_def->layout")
            && file_exists($context->getLayoutsHome().$this->layout.'/layout.xml')) {
            // Set the layout name
            $this->layout = "$page_def->layout";

            // Load the layout XML structure
            $layout_def = simplexml_load_file(
                $context->getLayoutsHome().
                $this->layout.'/layout.xml'
            );

            // Get layout level theme if defined
            if (strlen("$layout_def->theme")) {
                $this->theme = "$layout_def->theme";
            }

            // Get block list
            foreach ($layout_def->block as $blockDef) {
                // Load the block
                $block = InnomediaBlock::load(
                    $this->context,
                    $this->grid,
                    "$blockDef->module",
                    "$blockDef->name"
                );

                if (! is_null($block)) {
                    // Add the block
                    $this->grid->addBlock(
                        $block,
                        "$blockDef->row",
                        "$blockDef->column",
                        "$blockDef->position"
                    );
                }
            }
        }

        // Get page level theme if defined, overriding layout level theme
        if (strlen("$page_def->theme")) {
            $this->theme = "$page_def->theme";
        }

        // Get block list
        foreach ($page_def->block as $blockDef) {
            $block = InnomediaBlock::load(
                $this->context,
                $this->grid,
                "$blockDef->module",
                "$blockDef->name"
            );

            if (! is_null($block)) {
                $this->grid->addBlock(
                    $block,
                    "$blockDef->row",
                    "$blockDef->column",
                    "$blockDef->position"
                );
            }
        }
    }

    /**
     * Returns InnomediaContext object.
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
