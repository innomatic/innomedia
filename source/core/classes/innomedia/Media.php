<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category  Class
 * @package   Media
 * @author    Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright 2008-2014 Innomatic Company
 * @license   http://www.innomatic.io/license/   BSD License
 * @link      http://www.innomatic.io
 * @since     Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 * Class Media 
 * 
 * @category Class
 * @package  Media
 * @author   Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @license  http://www.innomatic.io/license/ BSD License
 * @link     http://www.innomatic.io
 */
class Media
{
    protected $id = 0;
    protected $name;
    protected $path;
    protected $pageName;
    protected $pageId;
    protected $blockName;
    protected $blockCounter;
    protected $context;
    protected $type;
    protected $fileId;
    protected $maxFiles = 1;

    /**
     * Sets the id of the media and instantiates innomedia/context
     * @param integer $id identifier of the media
     */
    public function __construct($id = 0)
    {
        $id = (int)$id;
        if (!is_int($id)) {
            $id = 0;
        }
        $this->id = $id;

        $this->context = \Innomedia\Context::instance('\Innomedia\Context');
    }

    /**
     * Saving the media in storage
     * @param string  $mediaTempPath Url temporary medias
     * @param boolean $deleteSource  Delete the temporary file if requireds
     * @return object_media          returns the object Media createds
     */
    public function setMedia($mediaTempPath, $deleteSource = true)
    {
        // The temporary source media must exists
        if (!file_exists($mediaTempPath)) {
            return $this;
        }

        // If no name has been previously provided, guess it from
        // temporary media file name
        if (!strlen($this->name)) {
            $this->name = basename($mediaTempPath);
        }

        // Start Manage creation name of the images
        $mediaQuery = $this->getMediaForBlock(); 
        $suffix = $mediaQuery->getNumberRows();    
        if ($suffix > 0) {
            while (!$mediaQuery->eof) {
                $last_media_name = $mediaQuery->getFields('name');
                $mediaQuery->moveNext();
            }
            $string_explode = explode('-', explode('.', $last_media_name)[0]);
            $suffix = array_pop($string_explode)+1;
        } 

        $pos = strrpos($this->name, ".");
        if ($pos === false) { 
            // not found...
            $extension = "";
        } else {
            $extension = substr($this->name, $pos+1);
        }

        $current_language = \Innomedia\Locale\LocaleWebApp::getCurrentLanguage('backend'); 
        
        $new_name = str_replace("/", "", $this->pageName);
        $new_name .= "-".$this->pageId;
        $new_name .= "-".str_replace("/", "", $this->blockName);
        $new_name .= "-".$this->blockCounter;
        $new_name .= "-".$this->type;
        $new_name .= "-".str_replace('__', '', $current_language);
        $new_name .= "-".$suffix.".".$extension;
        $this->name = $new_name;
        // END Manage creation name of the images

        // Build the destination path in the storage
        $destPath = $this->getPath(true);

        // Check if the destination directory exists
        $dirName = dirname($destPath).'/';
        if (!file_exists($dirName)) {
            \Innomatic\Io\Filesystem\DirectoryUtils::mktree($dirName, 0755);
        }

        // Copy the file inside the media storage
        copy($mediaTempPath, $destPath);

        // Delete the temporary file if required
        if ($deleteSource == true) {
            unlink($mediaTempPath);
        }

        return $this;
    }

    /**
     * Count media uploaded for block
     * @return returns number of media for block
     */
    public function getMediaForBlock() 
    {
        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();
        
        $current_language = \Innomedia\Locale\LocaleWebApp::getCurrentLanguage('backend');

        $mediaQuery = $domainDa->execute(
            "SELECT *
            FROM innomedia_media
            WHERE page='$this->pageName'
                AND pageid ".($this->pageId != 0 ? "= {$this->pageId}" : "is NULL")."
                AND block='{$this->blockName}'
                AND blockcounter={$this->blockCounter}
                AND fileid='{$this->fileId}'
                AND name LIKE '%-".str_replace('__', '', $current_language)."-%'
            ORDER BY id ASC"
        );
     
        return $mediaQuery;
    }

    /**
     * Sets the name of Media
     * @param string $name name of media
     * @return object_media returns the object Media modified 
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the name of page 
     * @param string $pageName name of page
     * @return object_media returns the object Media modified 
     */
    public function setPageName($pageName)
    {
        $this->pageName = $pageName;
        return $this;
    }

    /**
     * Sets id of page
     * @param integer $pageId identifier of the page
     * @return object_media returns the object Media modified 
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * Sets the name of block
     * @param string $blockName name of block
     * @return object_media returns the object Media modified 
     */
    public function setBlockName($blockName)
    {
        $this->blockName = $blockName;
        return $this;
    }

    /**
     * Sets the counter of block
     * @param integer $blockCounter counter of block
     * @return object_media returns the object Media modified 
     */
    public function setBlockCounter($blockCounter)
    {
        $this->blockCounter = $blockCounter;
        return $this;
    }

    /**
     * Sets the type of Media
     * @param string $type type of Media file
     * @return object_media returns the object Media modified 
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set id of file
     * @param integer $id identifier of file
     * @return object_media returns the object Media modified 
     */
    public function setFileId($id)
    {
        $this->fileId = $id;
        return $this;
    }

    /**
     * Set maximum number of files that can be loaded
     * @param integer $maxFiles number max of file
     * @return object_media returns the object Media modified 
     */
    public function setMaxFiles($maxFiles)
    {

        $maxFiles = (int)$maxFiles;
        if (!is_int($maxFiles)) {
            $maxFiles = 1;
        }
        $this->maxFiles = $maxFiles;
        return $this;
    }

    /**
     * Gets id of Media
     * @return integer identifier of Media
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets url path of file media
     * @param boolean $fullPath Set if create a relative or absolute path
     * @return string url path of file media
     */
    public function getPath($fullPath = false)
    {
        $path = $this->path;
        if (strlen($this->path)) {
            $path = $this->path;
        } else {
            $path = $this->buildPath();
        }

        if ($fullPath) {
            $path = $this->context->getStorageHome().$this->getTypePath($this->type).'/'.$path;
        }

        return $path;
    }

    /**
     * Gets url path of file media
     * @return string url path of file media
     */
    public function getUrlPath()
    {
        $path = $this->path;
        if (strlen($this->path)) {
            $path = $this->path;
        } else {
            $path = $this->buildPath();
        }

        $path = 'storage/'.$this->getTypePath($this->type).'/'.$path;
        
        $array_path = array_map("rawurlencode", explode("/", $path));
        $path = implode("/", $array_path);

        return $path;
    }

    /**
     * Gets type of Media
     * @return string return the type of object media
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets id of file
     * @return integer identifier of file
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Gets max of file loaded
     * @return integer identifier of file
     */
    public function getMaxFiles()
    {
        return $this->maxFiles;
    }

    /**
     * Retrieve an object of type image
     * @return objet return a object media
     */
    public function retrieve()
    {
        if ($this->id == 0) {
            return false;
        }

        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $mediaQuery = $domainDa->execute(
            "SELECT *
            FROM    innomedia_media
            WHERE   id = {$this->id}"
        );

        if ($mediaQuery->getNumberRows() > 0) {
            $this->name         = $mediaQuery->getFields('name');
            $this->type         = $mediaQuery->getFields('filetype');
            $this->fileId       = $mediaQuery->getFields('fileid');
            $this->path         = $mediaQuery->getFields('path');
            $this->pageName     = $mediaQuery->getFields('page');
            $this->pageId       = $mediaQuery->getFields('pageid');
            $this->blockName    = $mediaQuery->getFields('block');
            $this->blockCounter = $mediaQuery->getFields('blockcounter');
            return true;
        } else {
            $this->id = 0;
            return false;
        }
    }

    /**
     * Saves changes to the current object
     * @return boolean  return if the action is successful or not
     */
    public function store()
    {
        if (strlen($this->name) == 0) {
            return false;
        }

        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $path = $this->buildPath();

        if ($this->id != 0) {
            // Update the media row
            return $domainDa->execute(
                "UPDATE innomedia_media SET ".
                " name = ".$domainDa->formatText($this->name).
                ", path = ".$domainDa->formatText($path).
                ", filetype = ".$domainDa->formatText($this->type).
                ", fileid = ".$domainDa->formatText($this->fileId).
                ", page = ".(strlen($this->pageName) ? $domainDa->formatText($this->pageName) : "NULL").
                ", pageid = ".($this->pageId != 0 ? $this->pageId : "NULL").
                ", block = ".(strlen($this->blockName) ? $domainDa->formatText($this->blockName) : "NULL").
                ", blockcounter = ".($this->blockCounter != 0 ? $this->blockCounter : "NULL").
                " WHERE id={$this->id}"
            );
        } else {
            // Create a new media row
            $id = $domainDa->getNextSequenceValue('innomedia_media_id_seq');

            if ($domainDa->execute(
                "INSERT INTO innomedia_media (id,name,path".
                (strlen($this->type) ? ",filetype" : "").
                (strlen($this->fileId) ? ",fileid" : "").
                (strlen($this->pageName) ? ",page" : "").
                ($this->pageId != 0 ? ",pageid" : "").
                (strlen($this->blockName) ? ",block" : "").
                ($this->blockCounter != 0 ? ",blockcounter" : "")."
                ) VALUES ($id, ".$domainDa->formatText($this->name).",".
                $domainDa->formatText($path).
                (strlen($this->type) ? ",".$domainDa->formatText($this->type): "").
                (strlen($this->fileId) ? ",".$domainDa->formatText($this->fileId): "").
                (strlen($this->pageName) ? ",".$domainDa->formatText($this->pageName): "").
                ($this->pageId != 0 ? ",{$this->pageId}" : "").
                (strlen($this->blockName) ? ",".$domainDa->formatText($this->blockName): "").
                ($this->blockCounter != 0 ? ",{$this->blockCounter}" : "").
                ")"
            )) {
                $this->id = $id;
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Delete a object media
     * @param string $fieldName name of field image in innomedia_blocks
     * @return boolean return if the action is successful or not
     */
    public function delete($fieldName='')
    {
        if ($this->id == 0) {
            return true;    
        }

        if (!strlen($this->name)) {
            return false;
        }

        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $domainDa->execute("DELETE FROM innomedia_media WHERE id = {$this->id}");

        $mediaPath = $this->getPath(true);      
        unlink($mediaPath);

        // Delete reference image from innomedia_blocks
        if (!empty($fieldName)) {
            $this->deleteFromBlock($fieldName);
        }

        // @todo Delete here all image aliases

        $this->id = 0;
    }


    /**
     * Delete a object media from innomedia_blocks
     * @param string $fieldName name of field image in innomedia_blocks
     * @return boolean return if the action is successful or not
     */
    public function deleteFromBlock($fieldName)
    {
        // Delete ref image from innomedia_blocks
        $domainDa = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $checkQuery = $domainDa->execute(
            "SELECT     id, params
                FROM    innomedia_blocks
                WHERE   block = '$this->blockName'
                    AND counter = $this->blockCounter
                    AND page ".(!empty($this->pageName) ? "= '{$this->pageName}'" : "is NULL")."
                    AND pageid ".($this->pageId != 0 ? "= {$this->pageId}" : "is NULL")
        );

        if ($checkQuery->getNumberRows() > 0) {
            $row_id = $checkQuery->getFields('id');

            $json_params = json_decode($checkQuery->getFields('params'), true);
            
            // $ris = \Innomedia\Locale\LocaleWebApp::isTranslatedParams($json_params);
            list($blockModule, $blockName) = explode("/", $this->blockName);
            $context = \Innomedia\Context::instance('\Innomedia\Context');
            $is_nolocale = \Innomedia\Block::isNoLocale($context, $blockModule, $blockName);
            
            if ($is_nolocale)
                $current_language = 'nolocale';
            else 
                $current_language = \Innomedia\Locale\LocaleWebApp::getCurrentLanguage('backend');

            $params = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales($this->blockName, $json_params, 'backend');
            
            $key = @array_search($this->id, $params[$fieldName]);

            // remove id image selected
            unset($params[$fieldName][$key]); 

            // convet array in a not-associative array
            $params[$fieldName] = @array_values($params[$fieldName]);

            $json_params[$current_language] = $params;
            
            $domainDa->execute(
                "UPDATE innomedia_blocks
                SET params=".$domainDa->formatText(json_encode($json_params)).
                " WHERE id=$row_id"
            );
        
        }
    }


    /**
     * Get list of media
     * @param array $params list of params of page
     * @return returns the list of object Media saved
     */
    public static function getMediaByParams($params) 
    {
        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();
        
        $pageModule   = $params['pagemodule'];
        $pageName     = $params['pagename'];
        $pageId       = strlen($params['pageid']) ? $params['pageid'] : '0';
        $blockModule  = $params['blockmodule'];
        $blockName    = $params['blockname'];
        $blockCounter = $params['blockcounter'];
        $fileId       = $params['fileid'];
        $fieldName    = $params['fieldname'];

        $default_language = \Innomedia\Locale\LocaleWebApp::getDefaultLanguage();
        $current_language = \Innomedia\Locale\LocaleWebApp::getCurrentLanguage('backend');
        $list_language_available = \Innomedia\Locale\LocaleWebApp::getListLanguagesAvailable();

        if ($current_language == $default_language) {
            $i = 0;
            $string_not_like = '';
            foreach ($list_language_available as $key => $value) {
                if ($i != 0) $string_not_like .= " AND ";
                $string_not_like .= "params NOT LIKE '%\"$key\":%'";
                $i++;
            }
        }

        $page = "{$pageModule}/{$pageName}";
        $sql = "SELECT  params
                FROM    innomedia_blocks
                WHERE   page ".($page != "/" ? "= '{$page}'" : "is NULL")."
                    AND pageid ".($pageId != 0 ? "= {$pageId}" : "is NULL")."
                    AND block = '{$blockModule}/{$blockName}'
                    AND counter = {$blockCounter}
                    AND params LIKE '%\"{$fieldName}\":%'
                    AND (
                        (params LIKE '%\"$current_language\":%')
                        ".($current_language == $default_language ?  "OR ($string_not_like)" : '')."
                    )";
        
        $blocksQuery = $domainDa->execute($sql);

        $list_media = array();
        while (!$blocksQuery->eof) {

            $json_params = json_decode($blocksQuery->getFields('params'), true);

            $params = \Innomedia\Locale\LocaleWebApp::getParamsDecodedByLocales("{$blockModule}/{$blockName}", $json_params, 'backend');

            if (!is_array($params[$fieldName])) {
                $list_id_media[] = $params[$fieldName];
            } else {
                $list_id_media = $params[$fieldName];
            }

            $i = 0;
            foreach ($list_id_media as $value_id) {
                $page = "{$pageModule}/{$pageName}";
                $sql = "SELECT  *
                        FROM    innomedia_media
                        WHERE   page ".($page != "/" ? "= '{$page}'" : "is NULL")."
                            AND pageid ".($pageId != 0 ? "= {$pageId}" : "is NULL")."
                            AND block = '{$blockModule}/{$blockName}'
                            AND blockcounter = {$blockCounter}
                            AND fileid = '{$fileId}'
                            AND id = {$value_id}";

                $mediaQuery = $domainDa->execute($sql);

                if (is_object($mediaQuery) && $mediaQuery->resultrows > 0) {
                    $list_media[$i]['id']  = $mediaQuery->getFields('id');
                    $list_media[$i]['name'] = $mediaQuery->getFields('name');
                    $list_media[$i]['path'] = $mediaQuery->getFields('path');
                    $list_media[$i]['filetype'] = $mediaQuery->getFields('filetype'); 
                }
                
                $i++;
            }

            $blocksQuery->moveNext();

        }
        return $list_media;
    }

    /**
     * Create path of object media
     * @param  string $alias [description]
     * @return string   return path created
     */
    protected function buildPath($alias = '')
    {
        $path = '';

        if (strlen($this->pageName)) {
            $path .= strtolower($this->pageName);
            if ($this->pageId != 0) {
                $path .= '/'.$this->pageId;
            }
        }

        if (strlen($this->blockName)) {
            if (strlen($path) > 0) {
                $path .= '/';
            }
            $path .= 'block_'.strtolower($this->blockName);
            if ($this->blockCounter != 0) {
                $path .= '/'.$this->blockCounter;
            }

            if (strlen($this->fileId)) {
                $path .= '/'.$this->fileId;
            }
        }

        if (strlen($path) > 0) {

            $current_language = \Innomedia\Locale\LocaleWebApp::getCurrentLanguage('backend');
            $path .= '/'.str_replace('__', '', $current_language);

            $path .= '/';
        }

        $name = $this->name;
        if (strlen($alias)) {
            $name = pathinfo($this->name, PATHINFO_BASENAME).'_'.$alias.'.'.pathinfo($this->name, PATHINFO_EXTENSION);
        }
        $path .= $name;


        return $path;
    }

    /**
     * Get path folder of element by type
     * @param  string $type type of element media
     * @return string       path folder of element media
     */
    public static function getTypePath($type)
    {
        $path = '';

        switch ($type) {
        case 'image':
            $path = 'images';
            break;

        case 'file':
            $path = 'files';
            break;

        default:
            $path = 'files';
        }

        return $path;
    }
}
