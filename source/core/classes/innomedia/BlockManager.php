<?php

namespace Innomedia;

use \Innomatic\Core;

abstract class BlockManager
{
    public $id;
    public $blockName = '';
    public $parameters = array();
    public $pageName;
    public $pageId;

    public function __construct($pageName = '', $pageId = 0)
    {
        $this->pageName = $pageName;
        $this->pageId = $pageId;
    }

    public abstract function getManagerXml();

    /* public retrieve() {{{ */
    /**
     * Retrieves the block parameters from the storage.
     *
     * @return boolean true if the parameters have been retrieved.
     */
    public function retrieve()
    {
        $domainDa = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $blockQuery = $domainDa->execute(
            "SELECT id,params
            FROM innomedia_blocks
            WHERE block = ".$domainDa->formatText($this->blockName).
            ($this->pageId == 0 ? " AND page ".(strlen($this->pageName) ? " = ".$domainDa->formatText($this->pageName) : " IS NULL") : '')."
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
            $domainDa = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
                ->getCurrentDomain()
                ->getDataAccess();

            $checkQuery = $domainDa->execute(
                "SELECT id
                FROM innomedia_blocks
                WHERE block = ".$domainDa->formatText($this->blockName)."
                AND page ".(strlen($this->pageName) ? " = ".$domainDa->formatText($this->pageName) : " IS NULL")."
                AND pageid ".($this->pageId != 0 ? " = ".$this->pageId : " IS NULL")
            );

            if ($checkQuery->getNumberRows() > 0) {
                $id = $checkQuery->getFields('id');

                return $domainDa->execute(
                    "UPDATE innomedia_blocks
                    SET params=".$domainDa->formatText(json_encode($this->parameters)).
                    " WHERE id=$id"
                );
            } else {
                $id = $domainDa->getNextSequenceValue('innomedia_blocks_id_seq');

                return $domainDa->execute(
                    "insert into innomedia_blocks (id,block,params".
                    (strlen($this->pageName) ? ",page" : "").
                    ($this->pageId != 0 ? ",pageid" : "")."
                    ) values ($id, ".$domainDa->formattext($this->blockName).",".
                    $domainDa->formattext(json_encode($this->parameters)).
                    (strlen($this->pageName) ? ",".$domainDa->formattext($this->pageName): "").
                    ($this->pageId != 0 ? ",{$this->pageId}" : "").
                    ")"
                );
            }
        }
    }
}
