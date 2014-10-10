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
    protected $container;
	protected $dataAccess;
	static protected $nodesParentCache = array();
	static protected $pagesPathCache = array();

	public function __construct()
	{
	    $this->container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
		$this->dataAccess = $da;
	}

	/**
	 * Adds a new node to the nodes tree.
	 * 
	 * @todo handle empty names
	 * @todo handle already existing names (appending an increasing number)
	 * 
	 * @param integer $nodeId New node id.
	 * @param integer $parentId Parent node id.
	 * @param string $nodeName Node name to be used in node path.
	 * @return boolean
	 */
	public function addNode($module, $page, $pageId, $parentId, $nodeName)
	{
		// Checks if the node ids are numeric.
		if (!is_numeric($pageId) or !is_numeric($parentId)) {
			return false;
		}

		// Checks if the parent node really exists.
		if (!$this->getNodeExists($parentId)) {
			return false;
		}

		// Checks if the node name is empty.
		if (!strlen($nodeName)) {
			return false;
		}
		
		// Normalize page name.
		$nodeName = self::normalizePageName($nodeName);

		$parent_path = $this->generatePath($parentId);
		if (strlen($parent_path)) {
			$parent_path .= '/';
		}
		$page_path = $parent_path.$nodeName;

		return $this->dataAccess->execute(
			'INSERT INTO innomedia_pages_tree '.
		    'VALUES ('.
		    $this->dataAccess->formatText($module).','.
		    $this->dataAccess->formatText($page).','.
		    $pageId.','.
		    $parentId.','.
		    $this->dataAccess->formatText($page_path).')'
		);
	}

	/**
	 * Completely removes a node and all of his children, including the
	 * stored page objects.
	 * 
	 * @param integer $nodeId Node to be removed from the tree.
	 */
	public function removeNode($nodeId)
	{
		$children = $this->getNodeChildren($nodeId);
		if (count($children) > 0) {
			foreach($children as $child) {
				$this->removeNode($child);
			}
		}

		require_once('innopublish/page/InnopublishPage.php');
		$page = new InnopublishPage(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $this->dataAccess, $nodeId);
		$page->removeCurrentOnly();
		$this->dataAccess->execute('DELETE FROM innomedia_pages_tree WHERE page_id='.$nodeId);
	}

	/**
	 * Moves a node to a new parent node.
	 * 
	 * @param integer $nodeId Id of the node to be moved.
	 * @param integer $destinationParentId Id of the new parent node.
	 * @return boolean
	 */
	public function moveNode($nodeId, $destinationParentId)
	{
		// Checks if the node to be moved exists.
		if (!$this->getNodeExists($nodeId)) {
			return false;
		}

		// Checks if the destination parent node exists.
		if (!$this->getNodeExists($destinationParentId)) {
			return false;
		}

		// A node can't be parent of itself.
		if ($nodeId == $destinationParentId) {
			return false;
		}

		// Updates the node parent id.
		$this->dataAccess->execute(
			'UPDATE innomedia_pages_tree '.
		    'SET parent_id='.$destinationParentId.' '.
		    'WHERE page_id='.$nodeId
		);

		// Updates the node children tree nodes path.
		return $this->updateNodeChildrenTreePaths($nodeId);
	}

	/**
	 * Renames a node name and updates the path of its children tree.
	 * 
	 * @param integer $nodeId Node id.
	 * @param string $nodeName New node name.
	 * @return boolean
	 */
	public function renameNode($nodeId, $nodeName)
	{
		if (!$this->getNodeExists($nodeId)) {
			return false;
		}

		// Normalize page name.
		$nodeName = self::normalizePageName($nodeName);
		
		// Checks if the node name has been really changed.
		$check_query = $this->dataAccess->execute(
		    'SELECT page_path '.
		    'FROM innomedia_pages_tree '.
		    'WHERE page_id='.$nodeId
	    );

		$old_path = $check_query->getFields('page_path');
		$parent_path = dirname($check_query->getFields('page_path'));
		if ($parent_path == '.') {
			$parent_path = '';
		}
		if (strlen($parent_path)) {
			$parent_path .= '/';
		}
		$prev_name = basename($old_path);
		if ($prev_name == $nodeName) {
			return true;
		}
			// The name is new.
		if (!$this->dataAccess->execute(
				'UPDATE innomedia_pages_tree
				SET page_path = '.$this->dataAccess->formatText($parent_path.$nodeName).'
				WHERE page_id='.$nodeId)) {
			return false;
		}

		return $this->updateNodeChildrenTreePaths($nodeId);
	}

	/**
	 * Generates the full path string for a given node.
	 * 
	 * @param integer $nodeId
	 * @return string Full path.
	 */
	public function generatePath($nodeId)
	{
		$full_path = '';
		$nodes_a = array();
		$nodes_a[] = $nodeId;
		$nodes = array_merge($nodes_a, $this->getNodeParents($nodeId));
		$start = true;
		while (true) {
			$node = array_pop($nodes);
			if ($node === NULL) {
				break;
			}
			if (isset($this->pagesPathCache[$node])) {
				$page_path = $this->pagesPathCache[$node];
			} else {
				$query = $this->dataAccess->execute(
				    'SELECT page_path '.
				    'FROM innomedia_pages_tree '.
				    'WHERE page_id='.$this->dataAccess->formatInteger($node)
		        );
				if ($node != 2 and $query->getNumberRows() == 0) {
					return false;
				}
				$page_path = basename($query->getFields('page_path'));
			}

			if ($node == 2) {
			} else {
				if ($start == true) {
					$start = false;
				} else {
					$full_path .= '/';
				}
			}
			
			$full_path .= $page_path;
		}
		
		return $full_path;
	}

	/**
	 * Updates the node path for the children tree nodes of a node.
	 * 
	 * @param integer $nodeId
	 */
	public function updateNodeChildrenTreePaths($nodeId)
	{
		$children = $this->getNodeChildren($nodeId);
		if (count($children) > 0)
		{
			$parent_path = $this->generatePath($nodeId);
			foreach ($children as $child) {
				// Retrieves current node name.
				$node_query = $this->dataAccess->execute(
					'SELECT page_path '.
				    'FROM innomedia_pages_tree '.
				    'WHERE page_id='.$child
		        );
				$node_name = basename($node_query->getFields('page_path'));

				// Updates node path.
				$this->dataAccess->execute(
					'UPDATE innomedia_pages_tree '.
				    'SET page_path='.$this->dataAccess->formatText($parent_path.'/'.$node_name).' '.
				    'WHERE page_id='.$child
				);
				$this->updateNodeChildrenTreePaths($child);
			}
		}
	}

	/**
	 * Returns node parent id.
	 * 
	 * @param integer $nodeId
	 * @return integer
	 */
	public function getNodeParent($nodeId)
	{
		if (!is_numeric($nodeId)) {
			return false;
		}

		$query = $this->dataAccess->execute(
			'SELECT parent_id '.
		    'FROM innomedia_pages_tree '.
		    'WHERE page_id='.$this->dataAccess->formatInteger($nodeId)
		);

		if ($query->getNumberRows() == 0) {
			return false;
		}

		return $query->getFields('parent_id');
	}

	/**
	 * Returns node full path.
	 * 
	 * @param integer $nodeId
	 * @return string Node full path.
	 */
	public function getNodePath($nodeId)
	{
		if (!is_numeric($nodeId)) {
			return false;
		}

		$query = $this->dataAccess->execute(
		    'SELECT page_path '.
		    'FROM innomedia_pages_tree '.
		    'WHERE page_id='.$this->dataAccess->formatInteger($nodeId)
		);

		if ($query->getNumberRows() == 0) {
			return false;
		}

		return $query->getFields('page_path');
	}

	/**
	 * Return an array containing the list of the node parents in reverse order.
	 * 
	 * @param integer $nodeId
	 * @return array Node parents list.
	 */
	public function getNodeParents($nodeId)
	{
		$nodes = array();

		while ($nodeId != 2) {
			if (isset($this->nodeParentsCache[$nodeId])) {
				// Retrieves the parent from the cache.
				$nodes[] = $nodeId = $this->nodeParentsCache[$nodeId];
				continue;
			}

			$query = $this->dataAccess->execute(
				'SELECT parent_id '.
			    'FROM innomedia_pages_tree '.
			    'WHERE page_id='.$this->dataAccess->formatInteger($nodeId)
		    );

			if ($query->getNumberRows() > 0) {
				// Caches the result.
				$this->nodeParentsCache[$nodeId] = $query->getFields('parent_id');
				$nodes[] = $nodeId = $this->nodeParentsCache[$nodeId];
			} else {
				break;
			}
		};

		return $nodes;
	}

	/**
	 * Returns the first level children of the given node.
	 * 
	 * @param integer $nodeId
	 * @return array Array of the node children (only first level).
	 */
	public function getNodeChildren($nodeId)
	{
		$nodes = array();

		$query = $this->dataAccess->execute(
		    'SELECT page_id '.
		    'FROM innomedia_pages_tree '.
		    'WHERE parent_id='.$this->dataAccess->formatInteger($nodeId)
		);

		while(!$query->eof) {
			$nodes[] = $query->getFields('page_id');
			$query->moveNext();
		}

		return $nodes;
	}

	/**
	 * Returns a multidimensional array of the complete children tree of the given node.
	 * 
	 * @param integer $nodeId
	 * @return array Array of the node children.
	 */
	public function getNodeChildrenTree($nodeId)
	{
		$nodes = array();

		$tmp_nodes = $this->getNodeChildren($nodeId);
		foreach ($tmp_nodes as $node) {
			$children_nodes = $this->getNodeChildrenTree($node);
			if (count($children_nodes)) {
				$nodes[$node] = $children_nodes;
			} else {
				$nodes[$node] = '';
			}
		}
		return $nodes;
	}

	public function getNodeChildrenTreeParentList($nodeId)
	{
		$nodes = array();

		$tmp_nodes = $this->getNodeChildren($nodeId);
		foreach ($tmp_nodes as $node) {
			$children_nodes = $this->getNodeChildrenTreeParentList($node);
			if (count($children_nodes)) {
				$moving_nodes = $nodes;
				$nodes = array_merge($moving_nodes, $children_nodes);
			} else {
				$nodes[$node] = $nodeId;
			}
		}
		return $nodes;
	}
	
	/**
	 * Tells if a node with the given id exists.
	 *
	 * @param integer $nodeId
	 * @return boolean
	 */
	public function getNodeExists($nodeId)
	{
		// Node 2 is the root node and always exists.
		if ($nodeId == 2) {
			return true;
		}

		$query = $this->dataAccess->execute(
			'SELECT 1 FROM innomedia_pages_tree WHERE page_id='.$this->dataAccess->formatInteger($nodeId)
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
		// Checks if the path is empty, in that case this is the root node.
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

		if ($nodeQuery->getNumberRows() != 1) {
			return false;
		}

		// Build the result array
		return [
		    'module'  => $pageQuery->getFields('module'),
		    'page'    => $pageQuery->getFields('page'),
		    'page_id' => $pageQuery->getFields('page_id')
		];
	}

	public static function normalizePageName($name)
	{
	    $name = trim(strtolower($name));
	    $name = strtr($name, ' ', '-');
	    $name = strtr($name, "'", '-');
	    $name = strtr($name, "/", '-');
	    $name = strtr($name, "\\", '-');
	    
	    return $name;
	}

	/**
	 * Flushes all the caches.
	 */
	public function flushCaches()
	{
		$this->nodeParentsCache = array();
		$this->pagesPathCache   = array();
	}
}
?>
