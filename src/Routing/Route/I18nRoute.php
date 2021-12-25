<?php
declare(strict_types=1);

namespace ADmad\I18n\Routing\Route;

use Cake\Core\Configure;
use Cake\Routing\Route\DashedRoute;
use Cake\Utility\Hash;

class I18nRoute extends DashedRoute
{
    /**
     * List of available languages.
     *
     * @var array|null
     */
    protected static $_availableLangs;

    /**
     * Constructor for a Route.
     *
     * @param string $template Template string with parameter placeholders
     * @param array $defaults Array of defaults for the route.
     * @param array $options Array of parameters and additional options for the Route
     * @return void
     */
    public function __construct(string $template, array $defaults = [], array $options = [])
    {
        if (strpos($template, '{lang}') === false) {
            $template = '/{lang}' . $template;
        }
        if ($template === '/{lang}/') {
            $template = '/{lang}';
        }

        $options['inflect'] = 'dasherize';
        $options['persist'][] = 'lang';

        if (!array_key_exists('lang', $options)) {
            if (self::$_availableLangs === null) {
                self::$_availableLangs = array_keys(
                    Hash::normalize((array)Configure::read('I18n.languages'))
                );
            }

            if (self::$_availableLangs) {
                $options['lang'] = implode('|', self::$_availableLangs);
            }
        }

        parent::__construct($template, $defaults, $options);
    }

    /**
     * Apply persistent parameters to a URL array. Persistent parameters are a
     * special key used during route creation to force route parameters to
     * persist when omitted from a URL array.
     *
     * If `lang` isn't found in persisted parameter use the 1st lang for available
     * langs list.
     *
     * @param array $url The array to apply persistent parameters to.
     * @param array $params An array of persistent values to replace persistent ones.
     * @return array An array with persistent parameters applied.
     */
    protected function _persistParams(array $url, array $params): array
    {
        $url = parent::_persistParams($url, $params);

        if (!isset($url['lang']) && isset($this->options['_name']) && self::$_availableLangs) {
            $url['lang'] = current(self::$_availableLangs);
        }

        return $url;
    }
}
