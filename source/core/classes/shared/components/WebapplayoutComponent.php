<?php
/**
 * Innomatic
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright 2014 Innomatic Company
 * @license   http://www.innomatic.io/license/   BSD License
 * @link      http://www.innomatic.io
 * @since     2.0.0
 */
namespace Shared\Components;

use \Innomatic\Io\Filesystem;
use \Innomatic\Core;

/**
 * Webapp Layout file component handler.
 */
class WebapplayoutComponent extends \Innomatic\Application\ApplicationComponent
{
    public function __construct($rootda, $domainida, $appname, $name, $basedir)
    {
        parent::__construct($rootda, $domainida, $appname, $name, $basedir);
    }

    public static function getType()
    {
        return 'webapplayout';
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
        if (strlen($params['file'])) {
            $file = $this->basedir . '/core/layouts/' . basename($params['file']);

            if (!file_exists(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/')) {
                DirectoryUtils::mkTree(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/', 0755);
            }

            if (copy(
                $file,
                InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/' .basename($file)
            )) {
                $result = true;
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webappconfiguration.doinstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty file name',
                \Innomatic\Logging\Logger::ERROR
            );
        }
        return $result;
    }

    public function doUninstallAction($params)
    {
        $result = false;
        if (strlen($params['file'])) {
            if (is_dir(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/' . basename($params['file']))) {
                DirectoryUtils::unlinkTree(
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().
                    'core/applications/'.$this->appname.'/layouts/'.basename($params['file'])
                );
                $result = true;
            } else {
                $result = true;
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webappconfiguration.douninstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty file name',
                \Innomatic\Logging\Logger::ERROR
            );
        }
        return $result;
    }

    public function doUpdateAction($params)
    {
        $result = false;

        if (strlen($params['file'])) {
            if (!file_exists(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/')) {
                DirectoryUtils::mkTree(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/', 0755);
            }

           if (file_exists(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/' . basename($params['file']))) {
                unlink(
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().
                    'core/applications/' . $this->appname . '/layouts/' . basename($params['file'])
                );
            }

            $file = $this->basedir . '/core/layouts/' . basename($params['file']);
            if (file_exists($file)) {
                if (copy(
                    $file,
                    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/' .basename($file)
                )) {
                    $result = true;
                }
            }
        } else {
            $this->mLog->logEvent(
                'innomedia.webappconfiguration.douninstallaction',
                'In application ' . $this->appname . ', component ' . $params['name'] . ': Empty file name',
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

        $fileDestName = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/layouts/'.basename($params['file']);

        if (!file_exists(RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/layouts/')) {
            DirectoryUtils::mkTree(RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/layouts/', 0755);
        }

        if (!copy(
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/' .basename($params['file']),
            $fileDestName
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

        $fileDestName = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/layouts/'.basename($params['file']);

        if (!file_exists(RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/layouts/')) {
            DirectoryUtils::mkTree(RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/layouts/', 0755);
        }

        if (!copy(
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/applications/' . $this->appname . '/layouts/' .basename($params['file']),
            $fileDestName
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

        $fileDestName = RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().$domain.'/core/layouts/'.basename($params['file']);

        if (file_exists($fileDestName)) {
            return unlink($fileDestName);
        } else {
            return false;
        }
    }

}
