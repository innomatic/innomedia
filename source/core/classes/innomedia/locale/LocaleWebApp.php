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
 * @copyright 2008-2014 Innomatic Company
 * @license   http://www.innomatic.io/license/   BSD License
 * @link      http://www.innomatic.io
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
 * @copyright 2008-2014 Innomatic Company
 * @license   http://www.innomatic.io/license/   BSD License
 * @link      http://www.innomatic.io
 * @since     Class available since Release 2.1.0
 */
class LocaleWebApp
{

    /**
     * Get path of file for configuration languages
     * @return string path file
     */
    protected static function getConfLanguagesFile()
    {
        $home = \Innomedia\Context::instance('\Innomedia\Context')->getHome();
        
        $confLanguagesFile = file_exists($home . 'core/conf/languages.local.yml') ?
            $home . 'core/conf/languages.local.yml' :
            $home . 'core/conf/languages.yml';

        // Check if the YAML file for the configuration languages exists
        if (! file_exists($confLanguagesFile)) {
            return false;
        }

        // Load the configuration YAML
        return yaml_parse_file($confLanguagesFile);
    }

    /**
     * Get list of local languages from db Root
     * @return array list languages
     */
    protected static function getRootLocaleLanguages()
    {
        $language_locale = new \Innomatic\Locale\LocaleCatalog(
            'innomatic::localization', 
            \Innomatic\Core\InnomaticContainer::instance(
                '\Innomatic\Core\InnomaticContainer'
            )->getLanguage()
        );

        $languageQuery = \Innomatic\Core\InnomaticContainer::instance(
            '\Innomatic\Core\InnomaticContainer'
        )->getDataAccess()->execute('SELECT * FROM locale_languages');

        while (!$languageQuery->eof) {
            $langshort = $languageQuery->getFields('langshort');
            $langname = $languageQuery->getFields('langname');
            $languages[$langshort] = $language_locale->getStr($langname);
            $languageQuery->moveNext();
        }

        return $languages;
    }


    /**
     * Get Default language
     * @return string default language
     */
    public static function getDefaultLanguage()
    {
        $conf_languages = self::getConfLanguagesFile();

        if (isset($conf_languages['default'])) {
            
            $lang = '__' . $conf_languages['default'];

        } else {
            $lang = '__it';
        }
        return $lang;
    }

    /**
     * Get list of languages available
     * @return array list of languages
     */
    public static function getListLanguagesAvailable()
    {

        $locale_languages = self::getRootLocaleLanguages();
        $conf_languages = self::getConfLanguagesFile();

        if (isset($conf_languages['available'])) {
            
            $languages = array();
            foreach ($conf_languages['available'] as $lang) {
                $languages['__'.$lang] = $locale_languages[$lang];
            }
        } else {
            $languages = array('__it' => 'Italiano');
            // $languages = array('__it' => 'Italiano', '__en' => 'Inglese');
        }

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

            $lang = ($key == 'innomedia_locale' ? '__' : '') . $session->get($key);
        } 
        return $lang;
    }

    /**
     * Get the type of language block
     * @param  array  $blockName block name
     * @param  string $scope     scope language
     * @return string if the block has the attribute nolocale equals true 
     *         returns 'nolocale', otherwise it returns the current language
     */
    public static function getLanguageOfBlock($blockName, $scope = "frontend")
    {
        $context = \Innomedia\Context::instance('\Innomedia\Context');
        list($module, $block) = explode('/', $blockName);
        if (\Innomedia\Block::isNoLocale($context, $module, $block)) {
            $lang = 'nolocale';
        } else {
            $lang = self::getCurrentLanguage($scope);
        }
        return $lang;
    }   

    /**
     * Get parameters to json decoding them according to the language
     * @param  array  $blockName block name
     * @param  array  $params    field params 
     * @param  string $scope     scope language
     * @return array param decoding by language
     */
    public static function getParamsDecodedByLocales($blockName, $params, $scope = "frontend")
    {
        $default_language = self::getDefaultLanguage();
        $context = \Innomedia\Context::instance('\Innomedia\Context');

        list($module, $block) = explode('/', $blockName);

        if (\Innomedia\Block::isNoLocale($context, $module, $block)) {

            if (array_key_exists('nolocale', $params)) {
                $lang = 'nolocale';
            } else {
                if (!self::isTranslatedParams($params)) {
                    return $params;
                } else {
                    $lang = $default_language;
                }
            }

        } else {

            $lang = self::getCurrentLanguage($scope);

            // control array depth, if depth 1 there isn't the language
            if (!self::isTranslatedParams($params)) {
                // retroactivity: the content without the language definition 
                //                is shown only for the default language.
                if ($lang != $default_language) 
                    return '';

                return $params;
            }
        }

        $params_for_lang = array_key_exists($lang, $params) ? $params[$lang] : '';

        return $params_for_lang;
    }   

    /**
     * Get parameters to json decoding them according to the language 
     * for update database
     * @param  array  $blockName  block name
     * @param  array  $params_db  field params
     * @param  array  $params_new field params
     * @param  string $scope      scope language
     * @return array param decoding by language
     */
    public static function getParamsDecodedByLocalesForUpdate($blockName, $params_db, $params_new, $scope = "backend")
    {
        $context = \Innomedia\Context::instance('\Innomedia\Context');
        list($module, $block) = explode('/', $blockName);

        if (\Innomedia\Block::isNoLocale($context, $module, $block)) {

            $params = array();
            $current_language = 'nolocale';

        } else {

            $default_languate = self::getDefaultLanguage();
            $current_language = self::getCurrentLanguage($scope);
            
            $json_params = json_decode($params_db, true);
            
            if (!self::isTranslatedParams($json_params)) {
                if ($current_language == $default_languate) {
                    $params = array();
                } else {
                    $params[$default_languate] = $json_params;
                }   
            } else {
                $params = $json_params;
            }
        }

        $params[$current_language] = $params_new;

        return $params;
    }

}