<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  2008-2014 Innomatic Company
 * @license    http://www.innomatic.io/license/   BSD License
 * @link       http://www.innomatic.io
 * @since      Class available since Release 2.2.0
 */
namespace Innomedia;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright Copyright 2008-2014 Innomatic Company
 * @since 2.2.0
 */
class PageTree {
    /**
     * Innomatic Container.
     *
     * @var \Innomatic\Core\InnomaticContainer
     * @access protected
     */
    protected $container;
    /**
     * Tenant data access.
     *
     * @var \Innomatic\Dataaccess\DataAccess
     * @access protected
     */
    protected $dataAccess;
    /**
     * In memory cache for pages parents.
     *
     * @static
     * @access protected
     */
    static protected $pagesParentCache = array();
    /**
     * In memory cache for pages paths.
     *
     * @static
     * @access protected
     */
    static protected $pagesPathCache = array();

    /* public __construct() {{{ */
    /**
     * Class constructor.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $this->dataAccess = $this->container->getCurrentTenant()->getDataAccess();
    }
    /* }}} */

    /**
     * Adds a new page to the pages tree.
     *
     * @todo handle empty names
     * @todo handle already existing names (appending an increasing number)
     *
     * @param string $module Page module name.
     * @param string $page Page type.
     * @param integer $pageId New page id.
     * @param integer $parentId Parent page id.
     * @param string $pageName Page name to be used in page path.
     * @return boolean
     */
    public function addPage($module, $page, $pageId, $parentId, $pageName)
    {
        // Checks if the page ids are numeric.
        if (!is_numeric($pageId) or !is_numeric($parentId)) {
            return false;
        }

        // Checks if the parent page really exists.
        if (!$this->getPageExists($parentId)) {
            return false;
        }

        // Checks if the page name is empty.
        if (!strlen($pageName)) {
            $pageName = $pageId;
        }

        // Normalize page name.
        $pageName = self::normalizePageName($pageName);

        $parentPath = $this->generatePath($parentId);
        if (strlen($parentPath)) {
            $parentPath .= '/';
        }
        $pagePath = $parentPath.$pageName;

        return $this->dataAccess->execute(
            'INSERT INTO innomedia_pages_tree '.
            'VALUES ('.
            $this->dataAccess->formatText($module).','.
            $this->dataAccess->formatText($page).','.
            $pageId.','.
            $parentId.','.
            $this->dataAccess->formatText($pagePath).')'
        );
    }

    /**
     * Completely removes a page and all of his children, including the
     * stored page objects.
     *
     * @param integer $pageId Page to be removed from the tree.
     */
    public function removePage($pageId)
    {
        // Before removing the page, let's remove its children.
        $children = $this->getPageChildren($pageId);
        if (count($children) > 0) {
            foreach($children as $child) {
                $this->removePage($child);
            }
        }

        // Get page module and page type.
        $pageInfo = Page::getModulePageFromId($pageId);
        if ($pageInfo === false) {
            return false;
        }

        // Delete page from the database.
        $page = new Page($pageInfo['module'], $pageInfo['page'], $pageId);
        $deleted = $page->deleteContent(false);

        if (!$deleted) {
            return false;
        }

        // Delete page from the pages tree.
        return $this->dataAccess->execute(
            'DELETE FROM innomedia_pages_tree '.
            'WHERE page_id='.$pageId
        );
    }

    /**
     * Moves a page to a new parent page.
     *
     * @param integer $pageId Id of the page to be moved.
     * @param integer $destinationParentId Id of the new parent page.
     * @return boolean
     */
    public function movePage($pageId, $destinationParentId)
    {
        // Checks if the page to be moved exists.
        if (!$this->getPageExists($pageId)) {
            return false;
        }

        // Checks if the destination parent page exists.
        if (!$this->getPageExists($destinationParentId)) {
            return false;
        }

        // A page can't be parent of itself.
        if ($pageId == $destinationParentId) {
            return false;
        }

        // Updates the page parent id.
        $this->dataAccess->execute(
            'UPDATE innomedia_pages_tree '.
            'SET parent_id='.$destinationParentId.' '.
            'WHERE page_id='.$pageId
        );

        // Updates the page children tree pages path.
        return $this->updatePageChildrenTreePaths($pageId);
    }

    /**
     * Renames a page name and updates the path of its children tree.
     *
     * @param integer $pageId Page id.
     * @param string $pageName New page name.
     * @return boolean
     */
    public function renamePage($pageId, $pageName)
    {
        if (!$this->getPageExists($pageId)) {
            // Compatibility mode: handling of content pages with no location
            // in page tree.
            $pageInfo = Page::getModulePageFromId($pageId);

            if ($pageInfo === false) {
                // Page really doesn't exist in content pages table.
                return false;
            }

            // Add the page in the pages tree.
            $this->addPage($pageInfo['module'], $pageInfo['page'], $pageId, 0, $pageName);
        }

        // Checks if the page name is empty.
        if (!strlen($pageName)) {
            $pageName = $pageId;
        }

        // Normalize page name.
        $pageName = self::normalizePageName($pageName);

        // Checks if the page name has been really changed.
        $check_query = $this->dataAccess->execute(
            'SELECT page_path '.
            'FROM innomedia_pages_tree '.
            'WHERE page_id='.$pageId
        );

        $old_path = $check_query->getFields('page_path');
        $parentPath = dirname($check_query->getFields('page_path'));
        if ($parentPath == '.') {
            $parentPath = '';
        }
        if (strlen($parentPath)) {
            $parentPath .= '/';
        }
        $prev_name = basename($old_path);
        if ($prev_name == $pageName) {
            return true;
        }
        // The name is new.
        if (!$this->dataAccess->execute(
            'UPDATE innomedia_pages_tree
            SET page_path = '.$this->dataAccess->formatText($parentPath.$pageName).'
            WHERE page_id='.$pageId))
        {
            return false;
        }

        return $this->updatePageChildrenTreePaths($pageId);
    }

    /**
     * Generates the full path string for a given page.
     *
     * @param integer $pageId ID of the page.
     * @return string Full path.
     */
    public function generatePath($pageId)
    {
        $fullPath = '';
        $pagesA = array();
        $pagesA[] = $pageId;
        $pages = array_merge($pagesA, $this->getPageParents($pageId));
        $start = true;
        while (true) {
            $page = array_pop($pages);
            if ($page === NULL) {
                break;
            }
            if (isset($this->pagesPathCache[$page])) {
                $pagePath = $this->pagesPathCache[$page];
            } else {
                $query = $this->dataAccess->execute(
                    'SELECT page_path '.
                    'FROM innomedia_pages_tree '.
                    'WHERE page_id='.$this->dataAccess->formatInteger($page)
                );
                if ($page != 0 and $query->getNumberRows() == 0) {
                    return false;
                }
                $pagePath = basename($query->getFields('page_path'));
            }

            if ($page == 0) {
            } else {
                if ($start == true) {
                    $start = false;
                } else {
                    $fullPath .= '/';
                }
            }

            $fullPath .= $pagePath;
        }

        return $fullPath;
    }

    /**
     * Updates the page path for the children tree pages of a page.
     *
     * @param integer $pageId
     */
    public function updatePageChildrenTreePaths($pageId)
    {
        $children = $this->getPageChildren($pageId);
        if (count($children) > 0)
        {
            $parentPath = $this->generatePath($pageId);
            foreach ($children as $child) {
                // Retrieves current page name.
                $pageQuery = $this->dataAccess->execute(
                    'SELECT page_path '.
                    'FROM innomedia_pages_tree '.
                    'WHERE page_id='.$child
                );
                $pageName = basename($pageQuery->getFields('page_path'));

                // Updates page path.
                $this->dataAccess->execute(
                    'UPDATE innomedia_pages_tree '.
                    'SET page_path='.$this->dataAccess->formatText($parentPath.'/'.$pageName).' '.
                    'WHERE page_id='.$child
                );
                $this->updatePageChildrenTreePaths($child);
            }
        }
    }

    /**
     * Returns page parent id.
     *
     * @param integer $pageId
     * @return integer
     */
    public function getPageParent($pageId)
    {
        if (!is_numeric($pageId)) {
            return false;
        }

        $query = $this->dataAccess->execute(
            'SELECT parent_id '.
            'FROM innomedia_pages_tree '.
            'WHERE page_id='.$this->dataAccess->formatInteger($pageId)
        );

        if ($query->getNumberRows() == 0) {
            return false;
        }

        return $query->getFields('parent_id');
    }

    /**
     * Returns page full path.
     *
     * @param integer $pageId
     * @return string Page full path.
     */
    public function getPagePath($pageId)
    {
        if (!is_numeric($pageId)) {
            return false;
        }

        $query = $this->dataAccess->execute(
            'SELECT page_path '.
            'FROM innomedia_pages_tree '.
            'WHERE page_id='.$this->dataAccess->formatInteger($pageId)
        );

        if ($query->getNumberRows() == 0) {
            return false;
        }

        return $query->getFields('page_path');
    }

    /**
     * Return an array containing the list of the page parents in reverse order.
     *
     * @param integer $pageId
     * @return array Page parents list.
     */
    public function getPageParents($pageId)
    {
        $pages = array();

        while ($pageId != 0) {
            if (isset($this->pagesParentsCache[$pageId])) {
                // Retrieves the parent from the cache.
                $pages[] = $pageId = $this->pagesParentsCache[$pageId];
                continue;
            }

            $query = $this->dataAccess->execute(
                'SELECT parent_id '.
                'FROM innomedia_pages_tree '.
                'WHERE page_id='.$this->dataAccess->formatInteger($pageId)
            );

            if ($query->getNumberRows() > 0) {
                // Caches the result.
                $this->pagesParentsCache[$pageId] = $query->getFields('parent_id');
                $pages[] = $pageId = $this->pagesParentsCache[$pageId];
            } else {
                break;
            }
        };

        return $pages;
    }

    /**
     * Returns the first level children of the given page.
     *
     * @param integer $pageId
     * @return array Array of the page children (only first level).
     */
    public function getPageChildren($pageId)
    {
        $pages = array();

        $query = $this->dataAccess->execute(
            'SELECT page_id '.
            'FROM innomedia_pages_tree '.
            'WHERE parent_id='.$this->dataAccess->formatInteger($pageId)
        );

        while(!$query->eof) {
            $pages[] = $query->getFields('page_id');
            $query->moveNext();
        }

        return $pages;
    }

    /**
     * Returns a multidimensional array of the complete children tree of the given page.
     *
     * @param integer $pageId
     * @return array Array of the page children.
     */
    public function getPageChildrenTree($pageId)
    {
        $pages = array();

        $tmpPages = $this->getPageChildren($pageId);
        foreach ($tmpPages as $page) {
            $childrenPages = $this->getPageChildrenTree($page);
            if (count($childrenPages)) {
                $pages[$page] = $childrenPages;
            } else {
                $pages[$page] = '';
            }
        }
        return $pages;
    }

    public function getPageChildrenTreeParentList($pageId)
    {
        $pages = array();

        $tmpPages = $this->getPageChildren($pageId);
        foreach ($tmpPages as $page) {
            $childrenPages = $this->getPageChildrenTreeParentList($page);
            if (count($childrenPages)) {
                $movingPages = $pages;
                $pages = array_merge($movingPages, $childrenPages);
            } else {
                $pages[$page] = $pageId;
            }
        }
        return $pages;
    }

    /**
     * Tells if a page with the given id exists.
     *
     * @param integer $pageId
     * @return boolean
     */
    public function getPageExists($pageId)
    {
        // Page 0 is the root page and always exists.
        if ($pageId == 0) {
            return true;
        }

        $query = $this->dataAccess->execute(
            'SELECT 1 '.
            'FROM innomedia_pages_tree '.
            'WHERE page_id='.$this->dataAccess->formatInteger($pageId)
        );

        return $query->getNumberRows() > 0 ? true : false;
    }

    /**
     * Search a page by a path and returns its id if found.
     *
     * @param string $path Page full path.
     * @return array
     */
    public static function findPageByPath($path)
    {
        // Checks if the path is empty, in that case this is the root page.
        if (!strlen($path)) {
            return [
                'module'  => 'home',
                'page'    => 'index',
                'page_id' => 0
            ];
        }

        $domainDA = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $pageQuery = $domainDA->execute(
            'SELECT module, page, page_id '.
            'FROM innomedia_pages_tree '.
            'WHERE page_path='.$domainDA->formatText($path)
        );

        if ($pageQuery->getNumberRows() != 1) {
            return false;
        }

        // Build the result array.
        //
        return [
            'module'  => $pageQuery->getFields('module'),
            'page'    => $pageQuery->getFields('page'),
            'page_id' => $pageQuery->getFields('page_id')
            ];
    }

    /* public normalizePageName($name) {{{ */
    /**
     * Creates a normalized string for the page URL.
     *
     * @param string $name String to be normalized as a valid URL.
     * @static
     * @access public
     * @return string
     */
    public static function normalizePageName($name)
    {
        $name = trim(strtolower($name));
        $name = str_replace([' ', "'", "/", "\\", "?", ',', '.', ':'], '-', $name);
        $name = str_replace('--', '-', $name);

        return $name;
    }
    /* }}} */

    /**
     * Flushes all the in memory caches.
     */
    public function flushCaches()
    {
        $this->pagesParentsCache = array();
        $this->pagesPathCache   = array();
    }
}
?>
