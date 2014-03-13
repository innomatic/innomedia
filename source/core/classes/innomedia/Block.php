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

use \Innomatic\Webapp;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
abstract class Block extends Template
{

    protected $context;

    protected $grid;

    /**
     * Block parameters
     *
     * @var array
     */
    protected $parameters;

    public function __construct($file)
    {
        parent::__construct($file);
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
        return $this;
    }

    public function setGrid(Grid $grid)
    {
        $this->grid = $grid;
        return $this;
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

    public static function load(Context $context, Grid $grid, $module, $name, $params = array())
    {
        if (! strlen($module)) {
            return;
        }

        // Adds module classes directory to classpath
        $context->importModule($module);

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
            $fqcn = 'innomedia/EmptyBlock.php';
        }

        // @todo convert to new namespace convention
        $included = @include_once $fqcn;
        if (!$included) {
            $context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'Missing class ' . $fqcn);
            return;
        }

        $tpl_root = $context->getBlocksHome($module);
        $tpl_file = '';
        $locales  = $context->getLocales();
        foreach ($locales as $locale) {
            if (file_exists($tpl_root . '.' . $name.'_'.$locale.'.local.tpl.php')) {
                // Local template for given language exists
                $tpl_file = $tpl_root . '.' . $name.'_'.$locale.'.local.tpl.php';
                break;
            }
            if (file_exists($tpl_root . '.' . $name.'_'.$locale.'.tpl.php')) {
                // Template for given language exists
                $tpl_file = $tpl_root . '.' . $name.'_'.$locale.'.tpl.php';
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

        // Find block class
        $class = substr($fqcn, strrpos($fqcn, '/') ? strrpos($fqcn, '/') + 1 : 0, - 4);
        if (! class_exists($class)) {
            $context->getResponse()->sendError(WebAppResponse::SC_INTERNAL_SERVER_ERROR, 'Malformed block class ' . $fqcn);
            return;
        }

        // Build block
        $obj = new $class($tpl_file);
        $obj->setContext($context);
        $obj->setGrid($grid);

        // Set block parameters
        $obj->setParameters($params);

        // Get all grid tags and set them in the block tags
        $grid_tags = $grid->getTags();
        foreach ($grid_tags as $tag) {
            $obj->set($tag, $grid->get($tag));
        }
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

        // Adds module classes directory to classpath
        $context->importModule($module);

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
            // @todo convert to new class loader
            $fqcn = 'innomedia/EmptyBlock.php';
        }

        return $fqcn;
    }
    /* }}} */

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

    public function getContext()
    {
        return $this->context;
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

    abstract public function run(
        \Innomatic\Webapp\WebAppRequest $request,
        \Innomatic\Webapp\WebAppResponse $response
    );
}

?>
