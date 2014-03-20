<?php
/**
 * Innomatic
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright 2014 Innoteam Srl
 * @license   http://www.innomatic.org/license/   BSD License
 * @link      http://www.innomatic.org
 * @since     2.0.0
 */
namespace Shared\Components;

use \Innomatic\Io\Filesystem;
use \Innomatic\Core;

/**
 * Webapp Asset component handler.
 */
class WebappassetComponent extends \Innomatic\Application\ApplicationComponent
{
    public function __construct($rootda, $domainida, $appname, $name, $basedir)
    {
        parent::__construct($rootda, $domainida, $appname, $name, $basedir);
    }

    public static function getType()
    {
        return 'webappasset';
    }

    public static function getPriority()
    {
        return 0;
    }

    public static function getIsDomain()
    {
        return true;
    }

    public static function getIsOverridable()
    {
        return false;
    }

    public function doInstallAction($params)
    {
        $result = false;
        if (strlen($params['asset'])) {
            $file = $this->basedir . '/assets/' . $params['asset'];
            if (DirectoryUtils::dirCopy(
                $file.'/',
                InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/assets/' .basename($file).'/'
            )) {
                $result = true;
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webassetcomponent.doinstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty asset name',
                \Innomatic\Logging\Logger::ERROR
            );
        }
        return $result;
    }

    public function doUninstallAction($params)
    {
        $result = false;
        if (strlen($params['asset'])) {
            if (is_dir(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/assets/' . basename($params['asset']))) {
                DirectoryUtils::unlinkTree(
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().
                    'core/applications/'.$this->appname.'/assets/'.basename($params['asset'])
                );
                $result = true;
            } else {
                $result = true;
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webassetcomponent.douninstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty asset name',
                \Innomatic\Logging\Logger::ERROR
            );
        }
        return $result;
    }

    public function doUpdateAction($params)
    {
        if (strlen($params['asset'])) {
            if (is_dir(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/assets/' . basename($params['asset']))) {
                DirectoryUtils::unlinkTree(
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().
                    'core/applications/' . $this->appname . '/assets/' . basename($params['asset'])
                );
            }

            $file = $this->basedir . '/assets/' . $params['asset'];
            if (is_dir($file)) {
                if (DirectoryUtils::dirCopy(
                    $file.'/',
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/assets/' .basename($file).'/'
                )) {
                    $result = true;
                }
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webassetcomponent.douninstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty asset name',
                \Innomatic\Logging\Logger::ERROR
            );
        }
        return $result;
    }

    public function doEnableDomainAction($domainid, $params)
    {
        $domainQuery = $this->rootda->execute("SELECT domainid FROM domains WHERE id={$domainid}");
        if (!$domainQuery->getNumberRows()) {
            return false;
        }

        $domain = $domainQuery->getFields('domainid');

        $assetDestFolder = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/assets/'.basename($params['asset']).'/';

        if (!file_exists($assetDestFolder)) {
            DirectoryUtils::mkTree($assetDestFolder, 0755);
        }

        if (!DirectoryUtils::dirCopy(
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/assets/' .basename($params['asset']).'/',
            $assetDestFolder
        )) {
            return false;
        }
        return true;
    }

    public function doUpdateDomainAction($domainid, $params)
    {
        $domainQuery = $this->rootda->execute("SELECT domainid FROM domains WHERE id={$domainid}");
        if (!$domainQuery->getNumberRows()) {
            return false;
        }

        $domain = $domainQuery->getFields('domainid');

        $assetDestFolder = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/assets/'.basename($params['asset']).'/';

        if (!DirectoryUtils::dirCopy(
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/assets/' .basename($params['asset']).'/',
            $assetDestFolder
        )) {
            return false;
        }
        return true;

    }

    public function doDisableDomainAction($domainid, $params)
    {
        $domainQuery = $this->rootda->execute("SELECT domainid FROM domains WHERE id={$domainid}");
        if (!$domainQuery->getNumberRows()) {
            return false;
        }

        $domain = $domainQuery->getFields('domainid');

        $assetDestFolder = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/assets/'.basename($params['asset']).'/';

        if (is_dir($assetDestFolder)) {
            return DirectoryUtils::unlinkTree($assetDestFolder);
        } else {
            return false;
        }
    }

}
