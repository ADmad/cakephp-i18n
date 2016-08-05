<?php
namespace ADmad\I18n\Routing\Filter;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Network\Request;
use Cake\Routing\DispatcherFilter;
use Cake\Utility\Hash;

class I18nFilter extends DispatcherFilter
{
    /**
     * Default config.
     *
     * ### Valid keys
     *
     * - `detectLanguage`: If `true` will attempt to get browser locale and
     * redirect to similar language available in app when going to site root.
     * Default `false`.
     * - `defaultLanguage`: Default language for app. Default `en_US`.
     * - `languages`: Languages available in app. Default `[]`.
     * - `redirectToLang`: Whether the filter should redirect requests without lang to the url with lang. Default `false`.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'detectLanguage' => false,
        'defaultLanguage' => 'en_US',
        'languages' => [],
        'redirectToLang' => false,
    ];

    /**
     * Constructor.
     *
     * @param array $config Settings for the filter.
     */
    public function __construct($config = [])
    {
        if (isset($config['languages'])) {
            $config['languages'] = Hash::normalize($config['languages']);
        }

        parent::__construct($config);
    }

    /**
     * Callback for Dispatcher.beforeDispatch event.
     *
     * Sets appropriate locale and lang to I18n::locale() and App.language config
     * respectively based on "lang" request param.
     *
     * @param \Cake\Event\Event $event Event object.
     *
     * @return \Cake\Network\Response|null
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data['request'];

        if (is_callable($this->_config['redirectToLang'])) {
            $redirectToLang = $this->_config['redirectToLang']($request, $event);
        } else {
            $redirectToLang = $this->_config['redirectToLang'];
        }

        if (empty($request->url) || ($redirectToLang && !$request->param('lang'))) {
            $event->stopPropagation();
            $statusCode = 301;
            $lang = $this->_config['defaultLanguage'];
            if ($this->_config['detectLanguage']) {
                $statusCode = 302;
                $lang = $this->detectLanguage($request, $lang);
            }

            $location = $request->webroot . $lang . (!empty($request->url) ? '/' . $request->url : '');
            $response = $event->data['response'];
            $response->statusCode($statusCode);
            $response->header('Location', $location);

            return $response;
        }

        $langs = $this->_config['languages'];
        $lang = $request->param('lang') ?: $this->_config['defaultLanguage'];
        if (isset($langs[$lang])) {
            I18n::locale($langs[$lang]['locale']);
        } else {
            I18n::locale($lang);
        }

        Configure::write('App.language', $lang);
    }

    /**
     * Get languages accepted by browser and return the one matching one of
     * those in config var `I18n.languages`.
     *
     * You should set config var `I18n.languages` to an array containing
     * languages available in your app.
     *
     * @param \Cake\Network\Request $request Request instance.
     * @param string|null $default Default language to return if no match is found.
     *
     * @return string
     */
    public function detectLanguage(Request $request, $default = null)
    {
        if (empty($default)) {
            $lang = $this->_config['defaultLanguage'];
        } else {
            $lang = $default;
        }

        $browserLangs = $request->acceptLanguage();
        foreach ($browserLangs as $k => $langKey) {
            if (strpos($langKey, '-') !== false) {
                $browserLangs[$k] = substr($langKey, 0, 2);
            }
        }
        $acceptedLangs = array_intersect(
            $browserLangs,
            array_keys($this->_config['languages'])
        );
        if (!empty($acceptedLangs)) {
            $lang = reset($acceptedLangs);
        }

        return $lang;
    }
}
