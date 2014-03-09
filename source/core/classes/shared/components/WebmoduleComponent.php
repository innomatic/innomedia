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
 * Web Module component handler.
 */
class WebmoduleComponent extends \Innomatic\Application\ApplicationComponent
{
    public function __construct($rootda, $domainida, $appname, $name, $basedir)
    {
        parent::__construct($rootda, $domainida, $appname, $name, $basedir);
    }

    public static function getType()
    {
        return 'webmodule';
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
        if (strlen($params['module'])) {
            $file = $this->basedir . '/core/modules/' . $params['module'];
            if (DirectoryUtils::dirCopy(
                $file.'/',
                InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/modules/' .basename($file).'/'
            )) {
                $result = true;
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webmodulecomponent.doinstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty module name',
                \Innomatic\Logging\Logger::ERROR
            );
        }
        return $result;
    }

    public function doUninstallAction($params)
    {
        $result = false;
        if (strlen($params['module'])) {
            if (is_dir(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/modules/' . basename($params['module']))) {
                DirectoryUtils::unlinkTree(
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().
                    'core/applications/'.$this->appname.'/modules/'.basename($params['module'])
                );
                $result = true;
            } else {
                $result = true;
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webmodulecomponent.douninstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty module name',
                \Innomatic\Logging\Logger::ERROR
            );
        }
        return $result;
    }

    public function doUpdateAction($params)
    {
        if (strlen($params['module'])) {
            if (is_dir(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/modules/' . basename($params['module']))) {
                DirectoryUtils::unlinkTree(
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().
                    'core/applications/' . $this->appname . '/modules/' . basename($params['module'])
                );
            }

            $file = $this->basedir . '/core/modules/' . $params['module'];
            if (is_dir($file)) {
                if (DirectoryUtils::dirCopy(
                    $file.'/',
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/modules/' .basename($file).'/'
                )) {
                    $result = true;
                }
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webmodulecomponent.douninstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty module name',
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

        $moduleDestFolder = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/modules/'.basename($params['module']).'/';

        if (!file_exists($moduleDestFolder)) {
            DirectoryUtils::mkTree($moduleDestFolder, 0755);
        }

        if (!DirectoryUtils::dirCopy(
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/modules/' .basename($params['module']).'/',
            $moduleDestFolder
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

        $moduleDestFolder = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/modules/'.basename($params['module']).'/';

        if (!DirectoryUtils::dirCopy(
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/modules/' .basename($params['module']).'/',
            $moduleDestFolder
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

        $moduleDestFolder = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/modules/'.basename($params['module']).'/';

        if (is_dir($moduleDestFolder)) {
            return DirectoryUtils::unlinkTree($moduleDestFolder);
        } else {
            return false;
        }
    }

}
