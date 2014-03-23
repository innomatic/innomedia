<?php
namespace Innomedia;

class Image
{
    protected $id = 0;
    protected $name;
    protected $path;
    protected $pageName;
    protected $pageId;
    protected $blockName;
    protected $blockCounter;
    protected $context;

    public function __construct($id = 0)
    {
        $id = (int)$id;
        if (!is_int($id)) {
            $id = 0;
        }
        $this->id = $id;

        $this->context = \Innomedia\Context::instance('\Innomedia\Context');
    }

    public function setImage($imageTempPath, $deleteSource = true)
    {
        // The temporary source image must exists
        if (!file_exists($imageTempPath)) {
            return $this;
        }

        // If no name has been previously provided, guess it with from
        // temporary image file name
        if (!strlen($this->name)) {
            $this->name = basename($imageTempPath);
        }

        // Build the destination path in the storage
        $destPath = $this->context->getImagesStorageHome().$this->buildPath();

        // Check if the destination directory exists
        $dirName = dirname($destPath).'/';
        if (!file_exists($dirName)) {
            \Innomatic\Io\Filesystem\DirectoryUtils::mktree($dirName, 0755);
        }

        // Copy the file inside the images storage
        copy($imageTempPath, $destPath);

        // Delete the temporary file if required
        if ($deleteSource == true) {
            unlink($imageTempPath);
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

    public function getId()
    {
        return $this->id;
    }

    public function getPath()
    {
        return strlen($this->path) > 0 ? $this->path : $this->buildPath();
    }

    public function retrieve()
    {
        if ($this->id == 0) {
            return false;
        }

        $domainDa = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $imageQuery = $domainDa->execute(
            "SELECT *
            FROM innomedia_images
            WHERE id={$this->id}"
        );

        if ($imageQuery->getNumberRows() > 0) {
            $this->name = $imageQuery->getFields('name');
            $this->path = $imageQuery->getFields('path');
            $this->page = $imageQuery->getFields('page');
            $this->pageId = $imageQuery->getFields('pageid');
            $this->blockName = $imageQuery->getFields('block');
            $this->blockCounter = $imageQuery->getFields('blockcounter');
            return true;
        } else {
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
            // Update the image row
            return $domainDa->execute(
                "UPDATE innomedia_images SET ".
                " name = ".$domainDa->formatText($this->name).
                " path = ".$domainDa->formatText($path).
                " page = ".(strlen($this->pageName) ? $domainDa->formatText($this->pageName) : "NULL").
                " pageid = ".($this->pageId != 0 ? $this->pageId : "NULL").
                " block = ".(strlen($this->blockName) ? $domainDa->formatText($this->blockName) : "NULL").
                " blockcounter = ".($this->blockCounter != 0 ? $this->blockCounter : "NULL").
                "WHERE id={$this->id}"
            );
        } else {
            // Create a new image row
            $id = $domainDa->getNextSequenceValue('innomedia_images_id_seq');

            if ($domainDa->execute(
                "INSERT INTO innomedia_images (id,name,path".
                (strlen($this->pageName) ? ",page" : "").
                ($this->pageId != 0 ? ",pageid" : "").
                (strlen($this->blockName) ? ",block" : "").
                ($this->blockCounter != 0 ? ",blockcounter" : "")."
                ) VALUES ($id, ".$domainDa->formatText($this->name).",".
                $domainDa->formatText($path).
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

        $domainDa->execute("DELETE FROM innomedia_images WHERE id = {$this->id}");

        $imagePath = $this->context->getImagesStorageHome().$this->buildPath();
        unlink($imagePath);

        $this->id = 0;
    }

    protected function buildPath()
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
        }

        if (strlen($path) > 0) {
            $path .= '/';
        }

        $path .= $this->name;

        return $path;
    }
}
