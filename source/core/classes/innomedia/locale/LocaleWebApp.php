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
 * @package   LocaleWebApp
 * @author    Amanda Accalai <amanda.accalai@innoteam.it>
 *            Paolo Guanciarossa <paolo.guangiarossa@innoteam.it>
 * @copyright 2008-2014 Innoteam Srl
 * @license   http://www.innomatic.org/license/   BSD License
 * @link      http://www.innomatic.org
 * @since     Class available since Release 2.1.0
 */
namespace Innomedia\Locale;

/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category  Class
 * @package   LocaleWebApp
 * @author    Amanda Accalai <amanda.accalai@innoteam.it>
 *            Paolo Guanciarossa <paolo.guangiarossa@innoteam.it>
 * @copyright 2008-2014 Innoteam Srl
 * @license   http://www.innomatic.org/license/   BSD License
 * @link      http://www.innomatic.org
 * @since     Class available since Release 2.1.0
 */
class LocaleWebApp
{

    /**
     * Get parameters to json decoding them according to the language
     * @param  array  $params field params 
     * @param  string $scope  scope language
     * @return array          param decoding by language
     */
    public static function getParamsDecodedByLocales($params, $scope = "frontend")
    {
        $lang = self::getCurrentLanguage($scope);

        // control array depth, if depth 1 there isn't the language
        if (!self::isTranslatedParams($params)) {
            // retroactivity: the content without the language definition 
            //                is shown only for the default language.
            if ($lang != self::getDefaultLanguage()) 
                return '';

            return $params;
        }

        $params_for_lang = array_key_exists($lang, $params) ? $params[$lang] : '';

        return $params_for_lang;
    }   

    /**
     * Get current languae by scope
     * @param  string $scope scope language
     * @return string        current language
     */
    public static function getCurrentLanguage($scope = 'frontend')
    {
        $lang = self::getDefaultLanguage();

        // @TODO use WuiSessonKey when in backoffice context
        if ($scope == 'backend') {
            $key = 'innomedia_lang_for_edit_context';
        } elseif ($scope == 'frontend') {
            $key = 'innomedia_locale';
        } else {
            return $lang;
        }

        // @TODO use WebAppSession when in frontend context
        // $session = \Innomedia\Context::instance('\Innomedia\Context')->getSession();
        $session = \Innomatic\Desktop\Controller\DesktopFrontController::instance(
            '\Innomatic\Desktop\Controller\DesktopFrontController'
        )->session;

        if ($session->isValid($key)) {
            $lang = $session->get($key);
        } 

        return $lang;
    }

    /**
     * Get Default language
     * @return string default language
     */
    public static function getDefaultLanguage()
    {
        // @TODO dynamic load language
        $lang = '__it';
        return $lang;
    }

    /**
     * Get list of languages available
     * @return array list of languages
     */
    public static function getListLanguagesAvailable()
    {
        // @TODO dynamic load language
        $languages = array('__it' => 'Italiano', '__en' => 'Inglese');
        return $languages;
    }

    /**
     * Check structure of json/array 
     * @param  array $params field params
     * @return boolean if json is one array return true else return false
     */
    public static function isTranslatedParams($params)
    {

        $languages = self::getListLanguagesAvailable();
        foreach ($languages as $key => $value) {
            if (!empty($params[$key]))
                return true;
        }

        return false;
    }

}