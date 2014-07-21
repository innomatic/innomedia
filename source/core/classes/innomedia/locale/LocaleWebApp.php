<?php
/**
 * Innomatic
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  1999-2014 Innoteam Srl
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 5.0
*/
namespace Innomedia\Locale;

use \Innomatic\Desktop\Controller\DesktopFrontController;

class LocaleWebApp
{

    public static function getParamsDecodedByLocales($params, $scope = "frontend")
    {
        // control array depth, if depth 1 there isn't the language
        if (!self::isTranslatedParams($params)) {
            return $params;
        }

        $lang = self::getCurrentLanguage($scope);
        $parameters = array_key_exists($lang, $params) ? $params[$lang] : '';

        return $parameters;
    }   


    public static function getCurrentLanguage($scope = 'frontend')
    {
        $lang = self::getDefaultLanguage();

        if ($scope == 'backend') {
            $key = 'innomedia_lang_for_edit_context';
        } elseif ($scope == 'frontend') {
            $key = 'innomedia_locale';
        } else {
            return $lang;
        }

        $session = DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session;
        if ($session->isValid($key)) {
            $lang = $session->get($key);
        } 

        return $lang;
    }


    public static function getDefaultLanguage()
    {
        // @TODO dynamic load language
        $lang = '__it';
        return $lang;
    }

    public static function getListLanguageAvailable()
    {
        // @TODO dynamic load language
        $languages = array('__it' => 'Italiano', '__en' => 'Inglese');
        return $languages;
    }

    public static function isTranslatedParams($params)
    {
        // select first element of json
        foreach ($params as $key => $param) break;
        
        // control array depth, if depth 1 there isn't the language
        if (!is_array($param)) {
            return false;
        }

        return true;
    }

}