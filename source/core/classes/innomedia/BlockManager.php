<?php

namespace Innomedia;

use \Innomatic\Core;

abstract class BlockManager
{
    public $id;
    public $blockName = '';
    public $parameters = array();
    public $pageName;
    public $blockCounter = 1;
    public $pageId;
    protected $domainDa;

    public function __construct($pageName = '', $blockCounter = 1, $pageId = 0)
    {
        $this->pageName = $pageName;
        $this->pageId = $pageId;
        $this->blockCounter = $blockCounter;
        $this->domainDa = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();
    }

    public abstract function getManagerXml();

    public function getParamPrefix()
    {
        return str_replace('/', '_', $this->blockName).'_'.$this->blockCounter;
    }

    /* public retrieve() {{{ */
    /**
     * Retrieves the block parameters from the storage.
     *
     * @return boolean true if the parameters have been retrieved.
     */
    public function retrieve()
    {
        $blockQuery = $this->domainDa->execute(
            "SELECT id,params
            FROM innomedia_blocks
            WHERE block = ".$this->domainDa->formatText($this->blockName).
            " AND counter = $this->blockCounter".
            ($this->pageId == 0 ? " AND page ".(strlen($this->pageName) ? " = ".$this->domainDa->formatText($this->pageName) : " IS NULL") : '')."
            AND pageid ".($this->pageId != 0 ? " = ".$this->pageId : " IS NULL")
        );

        if ($blockQuery->getNumberRows() > 0) {
            $this->parameters = json_decode($blockQuery->getFields('params'), true);
            $this->id = $blockQuery->getFields('id');
            return true;
        } else {
            return false;
        }
    }
    /* }}} */

    public function store()
    {
        if (is_array($this->parameters) && strlen($this->blockName)) {
            $checkQuery = $this->domainDa->execute(
                "SELECT id
                FROM innomedia_blocks
                WHERE block = ".$this->domainDa->formatText($this->blockName)."
                AND counter = $this->blockCounter
                AND page ".(strlen($this->pageName) ? " = ".$this->domainDa->formatText($this->pageName) : " IS NULL")."
                AND pageid ".($this->pageId != 0 ? " = ".$this->pageId : " IS NULL")
            );

            if ($checkQuery->getNumberRows() > 0) {
                $id = $checkQuery->getFields('id');

                return $this->domainDa->execute(
                    "UPDATE innomedia_blocks
                    SET params=".$this->domainDa->formatText(json_encode($this->parameters)).
                    " WHERE id=$id"
                );
            } else {
                $id = $this->domainDa->getNextSequenceValue('innomedia_blocks_id_seq');

                return $this->domainDa->execute(
                    "INSERT INTO innomedia_blocks (id,block,counter,params".
                    (strlen($this->pageName) ? ",page" : "").
                    ($this->pageId != 0 ? ",pageid" : "")."
                    ) VALUES ($id, ".$this->domainDa->formattext($this->blockName).",".$this->blockCounter.','.
                    $this->domainDa->formattext(json_encode($this->parameters)).
                    (strlen($this->pageName) ? ",".$this->domainDa->formattext($this->pageName): "").
                    ($this->pageId != 0 ? ",{$this->pageId}" : "").
                    ")"
                );
            }
        }
    }

    public function getUploadedFiles()
    {
        if (!(strlen($this->blockName) && strlen($this->pageName))) {
            return false;
        }

        return self::listdir($this->getUploadedFilesTempPath());
    }

    public function cleanUploadedFiles()
    {
        if (!(strlen($this->blockName) && strlen($this->pageName))) {
            return false;
        }

        $files = self::listdir($this->getUploadedFilesTempPath());
        if (!is_array($files)) {
            return true;
        }

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    protected function getUploadedFilesTempPath()
    {
        list($pageModule, $pageName) = explode('/', $this->pageName);
        list($blockModule, $blockName) = explode('/', $this->blockName);
        $pageId = strlen($this->pageId) ? $this->pageId : 0;
        $blockCounter = strlen($this->blockCounter) ? $this->blockCounter :  1;

        $root           = \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer');
        $innomatic_home = $root->getHome() . 'innomatic/';
        $domainName = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId();
        return $innomatic_home.'core/temp/dropzone/'.$domainName.'/'.$pageModule.'/'.$pageName.'/'.$pageId.'/'.$blockModule.'/'.$blockName.'/'.$blockCounter;
    }

    protected static function listdir($start_dir = '.') {
        $files = array();
        if (is_dir($start_dir)) {
            $fh = opendir($start_dir);
            while (($file = readdir($fh)) !== false) {
                # loop through the files, skipping . and .., and recursing if necessary
                if (strcmp($file, '.')==0 || strcmp($file, '..')==0) continue;
                $filepath = $start_dir . '/' . $file;
                if (is_dir($filepath)) {
                    $files = array_merge($files, self::listdir($filepath));
                } else {
                  array_push($files, $filepath);
                }
            }
            closedir($fh);
        } else {
          # false if the function was called with an invalid non-directory argument
          $files = false;
        }

        return $files;
    }

    public abstract function saveBlock($parameters);
}
