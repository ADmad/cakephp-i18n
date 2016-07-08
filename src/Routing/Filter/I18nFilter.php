<?php
namespace I18n\Routing\Filter;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Network\Request;
use Cake\Routing\DispatcherFilter;

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
     *
     * @var array
     */
    protected $_defaultConfig = [
        'detectLanguage' => false,
    ];

    /**
     * Set appropirate locale and lang to I18n::locale() and App.language config
     * respectively based on "lang" request param.
     *
     * {@inheritdoc}
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data['request'];

        if (empty($request->url)) {
            $response = $event->data['response'];
            $event->stopPropagation();

            $statusCode = 301;
            $lang = Configure::read('I18n.defaultLanguage');
            if ($this->config('detectLanguage')) {
                $statusCode = 302;
                $lang = $this->detectLanguage($request);
            }

            $response->statusCode($statusCode);
            $response->header('Location', $request->webroot . $lang);

            return $response;
        }

        $langs = Configure::read('I18n.languages');
        $lang = $request->param('lang');
        if (isset($langs[$lang])) {
            I18n::locale($langs[$lang]['locale']);
            Configure::write('App.language', $lang);
        }
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
            $lang = Configure::read('I18n.defaultLanguage');
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
            array_keys(Configure::read('I18n.languages'))
        );
        if (!empty($acceptedLangs)) {
            $lang = reset($acceptedLangs);
        }

        return $lang;
    }
}
