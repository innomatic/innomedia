<?php
namespace Innomedia;

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

    public function __construct($id = 0)
    {
        $id = (int)$id;
        if (!is_int($id)) {
            $id = 0;
        }
        $this->id = $id;

        $this->context = \Innomedia\Context::instance('\Innomedia\Context');
    }

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

        // Build the destination path in the storage
        $destPath = $this->context->getStorageHome().$this->getTypePath($this->type).'/'.$this->buildPath();

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

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setPageName($pageName)
    {
        $this->pageName = $pageName;
        return $this;
    }

    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
        return $this;
    }

    public function setBlockName($blockName)
    {
        $this->blockName = $blockName;
        return $this;
    }

    public function setBlockCounter($blockCounter)
    {
        $this->blockCounter = $blockCounter;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setFileId($id)
    {
        $this->fileId = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

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

    public function getType()
    {
        return $this->type;
    }

    public function getFileId()
    {
        return $this->fileId;
    }

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
            FROM innomedia_media
            WHERE id={$this->id}"
        );

        if ($mediaQuery->getNumberRows() > 0) {
            $this->name         = $mediaQuery->getFields('name');
            $this->type         = $mediaQuery->getFields('filetype');
            $this->fileId       = $mediaQuery->getFields('fileid');
            $this->path         = $mediaQuery->getFields('path');
            $this->page         = $mediaQuery->getFields('page');
            $this->pageId       = $mediaQuery->getFields('pageid');
            $this->blockName    = $mediaQuery->getFields('block');
            $this->blockCounter = $mediaQuery->getFields('blockcounter');
            return true;
        } else {
            $this->id = 0;
            return false;
        }
    }

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

    public function delete()
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

        $mediaPath = $this->context->getStorageHome().$this->getTypePath($this->type).'/'.$this->buildPath();
        unlink($mediaPath);

        // @todo Delete here all image aliases

        $this->id = 0;
    }

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
            $path .= '/';
        }

        $name = $this->name;
        if (strlen($alias)) {
            $name = pathinfo($this->name, PATHINFO_BASENAME).'_'.$alias.'.'.pathinfo($this->name, PATHINFO_EXTENSION);
        }
        $path .= $name;

        return $path;
    }

    protected function getTypePath($type)
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
