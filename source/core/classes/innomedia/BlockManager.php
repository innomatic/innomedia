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
    protected $this->domainDa;

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

    public abstract function saveBlock($parameters);
}
