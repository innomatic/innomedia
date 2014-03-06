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

    protected $theme;

    protected $grid;

    public function __construct(
        InnomediaContext $context,
        \Innomatic\Webapp\WebAppRequest $request,
        \Innomatic\Webapp\WebAppResponse $response,
        $module,
        $page
    )
    {
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
        if (! file_exists($this->pageDefFile)) {
            return false;
        }
        $def = simplexml_load_file($this->pageDefFile);
        // Gets page level theme if defined
        if (strlen("$def->theme")) {
            $this->theme = "$def->theme";
        }
        // Loads the grid
        $this->grid = new InnomediaGrid($this);
        // Gets block list
        foreach ($def->block as $blockDef) {
            $block = InnomediaBlock::load($this->context, $this->grid, "$blockDef->module", "$blockDef->name");
            if (! is_null($block)) {
                $this->grid->addBlock($block, "$blockDef->row", "$blockDef->column", "$blockDef->position");
            }
        }
    }

    public function getContext()
    {
        return $this->context;
    }

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
