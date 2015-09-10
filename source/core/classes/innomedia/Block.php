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

use \Innomatic\Webapp;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
abstract class Block extends Template
{
    protected $counter;

    protected $row;

    protected $column;

    protected $position;

    protected $context;

    protected $page;

    protected $grid;

    /**
     * Supported block types.
     *
     * A block type defines which sort of generic functionalities are supported
     * by the block.
     *
     * Block types are used when there are cell parameters with "accepts"
     * parameter.
     *
     * @var array
     */
    protected $types;

    /**
     * Block parameters
     *
     * @var array
     */
    protected $parameters;

    public function __construct($file)
    {
        parent::__construct($file);
        $this->context = Context::instance('\Innomedia\Context');
    }

    public function setGrid(Grid $grid)
    {
        $this->grid = $grid;
        return $this;
    }

    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }

    /* public setParameters($params) {{{ */
    /**
     * Sets block parameters array.
     *
     * @param array $params Array of parameters.
     *
     * @return \Innomedia\Block itself.
     */
    public function setParameters($params)
    {
        $this->parameters = $params;
        return $this;
    }
    /* }}} */

    /* public setCounter($counter) {{{ */
    /**
     * Sets the block counter in the page.
     *
     * @param integer $counter
     * @return \Innomedia\Block the block object
     */
    public function setCounter($counter)
    {
        $this->counter = (int)$counter;
        return $this;
    }

    public function setRow($row)
    {
        $this->row = (int)$row;
        return $this;
    }

    public function setColumn($column)
    {
        $this->column = (int)$column;
        return $this;
    }

    public function setPosition($position)
    {
        $this->position = (int)$position;
        return $this;
    }

    /* public getCounter($counter) {{{ */
    /**
     * Returns the block counter.
     *
     * If not set, it returns 1.
     *
     * @return integer
     */
    public function getCounter()
    {
        return $this->counter != null ? $this->counter : 1;
    }

    /* }}} */

    /* }}} */
    /* public hasBlockManager() {{{ */
    /**
     * Returns true if the block has a manager class.
     *
     * When a block provides an administration interface it must override this
     * method and return true. It also must return a valid implementation of
     * \Innomedia\BlockManager with the method getBlockManager().
     *
     * @return boolean true
     */
    public static function hasBlockManager()
    {
        return false;
    }
    /* }}} */

    /* public getBlockManager() {{{ */
    /**
     * Returns an instance of the current block manager class, if available.
     *
     * @also hasBlockManager()
     * @return \Innomedia\BlockManager|null
     */
    public static function getBlockManager()
    {
        return null;
    }
    /* }}} */

    public static function load(Context $context, Page $page, Grid $grid, $module, $name, $counter, $row, $column, $position, $params = array())
    {
        if (! strlen($module)) {
            return;
        }

        $block_yml_file = $context->getBlocksHome($module) . $name . '.local.yml';
        if (!file_exists($block_yml_file)) {
            $block_yml_file = $context->getBlocksHome($module) . $name . '.yml';
        }
        if (!file_exists($block_yml_file)) {
            $context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'Missing block definition file ' . $name . '.yml');
            return;
        }
        // Imports block class and return an instance
        $def  = yaml_parse_file($block_yml_file);
        $fqcn = $def['class'];
        if (!strlen($fqcn)) {
            // @todo convert to new class loader
            $fqcn = '\\Innomedia\\EmptyBlock';
        }

        // @todo convert to new namespace convention
        if (!class_exists($fqcn)) {
            $context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'Missing class ' . $fqcn);
            return;
        }

        $tpl_root = $context->getBlocksHome($module);
        $tpl_file = '';
        $locales  = $context->getLocales();
        foreach ($locales as $locale) {
            if (file_exists($tpl_root . $name.'_'.$locale.'.local.tpl.php')) {
                // Local template for given language exists
                $tpl_file = $tpl_root . $name.'_'.$locale.'.local.tpl.php';
                break;
            }
            if (file_exists($tpl_root . $name.'_'.$locale.'.tpl.php')) {
                // Template for given language exists
                $tpl_file = $tpl_root . $name.'_'.$locale.'.tpl.php';
                break;
            }
        }

        if (! strlen($tpl_file)) {
            $webapp = WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getCurrentWebApp();
            if (file_exists($tpl_root . $webapp->getInitParameter('InnomediaDefaultLanguage') . '.' . $name.'.local.tpl.php')) {
                // Local template for default language exists
                $tpl_file = $tpl_root . $webapp->getInitParameter('InnomediaDefaultLanguage') . '.' . $name.'.local.tpl.php';
            } elseif (file_exists($tpl_root . $webapp->getInitParameter('InnomediaDefaultLanguage') . '.' . $name.'.tpl.php')) {
                // Template for default language exists
                $tpl_file = $tpl_root . $webapp->getInitParameter('InnomediaDefaultLanguage') . '.' . $name.'.tpl.php';
            } elseif (file_exists($tpl_root.$name.'.local.tpl.php')) {
                // Local template for no specific language exists
                $tpl_file = $tpl_root . $name.'.local.tpl.php';
            } else {
                // Template for no specific language exists
                $tpl_file = $tpl_root . $name.'.tpl.php';
            }
        }

        // Build block
        $obj = new $fqcn($tpl_file);
        $obj->setPage($page)
            ->setGrid($grid);

        // Set block counter
        $obj->setCounter($counter)
            ->setRow($row)
            ->setColumn($column)
            ->setPosition($position);

        // Set block parameters
        $obj->setParameters($params);

        // Set block types
        $obj->setTypes((isset($def['types']) && is_array($def['types'])) ? $def['types'] : array());

        return $obj;
    }

    /* public getClass(Context $context, $module, $name) {{{ */
    /**
     * Find the class of a block.
     *
     * @param Context $context
     * @param string $module
     * @param string $name
     * @return string class name.
     */
    public static function getClass(Context $context, $module, $name)
    {
        if (! strlen($module)) {
            return;
        }

        $block_yml_file = $context->getBlocksHome($module) . $name . '.local.yml';
        if (!file_exists($block_yml_file)) {
            $block_yml_file = $context->getBlocksHome($module) . $name . '.yml';
        }
        if (!file_exists($block_yml_file)) {
            return;
        }
        // Imports block class and return an instance of it.
        $def = yaml_parse_file($block_yml_file);
        $fqcn = $def['class'];
        if (! strlen($fqcn)) {
            $fqcn = '\\Innomedia\\EmptyBlock';
        }

        return $fqcn;
    }
    /* }}} */

    public static function getScopes(Context $context, $module, $name)
    {
        if (! strlen($module)) {
            return array();
        }

        $block_yml_file = $context->getBlocksHome($module) . $name . '.local.yml';
        if (!file_exists($block_yml_file)) {
            $block_yml_file = $context->getBlocksHome($module) . $name . '.yml';
        }
        if (!file_exists($block_yml_file)) {
            return array();
        }

        $def = yaml_parse_file($block_yml_file);
        if (isset($def['scopes']) && is_array($def['scopes'])) {
            return $def['scopes'];
        } else {
            return array();
        }
    }

    public static function isNoLocale(Context $context, $module, $name)
    {
        if (! strlen($module)) {
            return array();
        }

        $class = self::getClass($context, $module, $name);
        $scopes = self::getScopes($context, $module, $name);
        if (in_array('global', $scopes) && $class::hasBlockManager()) {
            return true;
        }

        $block_yml_file = $context->getBlocksHome($module) . $name . '.local.yml';
        if (!file_exists($block_yml_file)) {
            $block_yml_file = $context->getBlocksHome($module) . $name . '.yml';
        }
        if (!file_exists($block_yml_file)) {
            return array();
        }
        $def = yaml_parse_file($block_yml_file);
        if (isset($def['nolocale']) && $def['nolocale'] == true) {
            return $def['nolocale'];
        } else {
            return false;
        }
    }

    private function getTemplateFile($page)
    {
        $locales = $this->context->getLocales();
        foreach ($locales as $locale) {
            if (file_exists($pages_root . $locale . '/' . $page)) {
                // Page for given language exists
                return $pages_root . $locale . '/' . $page;
            }
        }

        if (file_exists($pages_root . $this->context->getRequest()
            ->getContext()
            ->getConfig()
            ->getInitParameter('contentDefaultLanguage') . '/' . $page)) {
            // Page for default language exists
            return $pages_root . $this->default_lang . '/' . $page;
        } elseif (file_exists($pages_root . $page)) {
            // Page for no specific language exists
            return $pages_root . $page;
        }
        // No page exists
        return false;
    }

    public function getGrid()
    {
        return $this->grid;
    }

    /* public getParameters() {{{ */
    /**
     * Returns block parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    /* }}} */

    public function setTypes($types = array())
    {
        $this->types = $types;
    }

    public function getTypes()
    {
        return $this->types;
    }

    abstract public function run(
        \Innomatic\Webapp\WebAppRequest $request,
        \Innomatic\Webapp\WebAppResponse $response
    );

    /* public getBlocksByTypes($searchTypes) {{{ */
    /**
     * Gets a list of all the blocks supporting at least one of the given types.
     *
     * @param array $searchTypes an array of types to check
     * @static
     * @access public
     * @return array
     */
    public static function getBlocksByTypes($searchTypes)
    {
        $blocks = array();

        $modulesList = Context::instance('\Innomedia\Context')->getModulesList();

        foreach ($modulesList as $module) {
            $moduleObj = new Module($module);
            $blocksList = $moduleObj->getBlocksList();

            foreach ($blocksList as $block) {
                $blockDefFileBase = Context::instance('\Innomedia\Context')->getModulesHome().$module.'/blocks/'.$block;
                if (file_exists($blockDefFileBase.'.local.yml')) {
                    // Local block definition file
                    $blockDefFile = $blockDefFileBase.'.local.yml';
                } elseif (file_exists($blockDefFileBase.'.yml')) {
                    // Standard block definition file
                    $blockDefFile = $blockDefFileBase.'.yml';
                } else {
                    // Block definition file doesn't exist
                    continue;
                }

                // Get block definition
                $blockDef = yaml_parse_file($blockDefFile);
                if (!(isset($blockDef['types']) && is_array($blockDef['types']))) {
                    // The block has no types
                    continue;
                }

                // Check if at least one of the given types is support by the
                // current block
                foreach ($searchTypes as $searchType) {
                    if (in_array($searchType, $blockDef['types'])) {
                        // Type found
                        $blocks[] = $module.'/'.$block;
                        continue;
                    }
                }
            }
        }

        return $blocks;
    }
    /* }}} */
}

?>
