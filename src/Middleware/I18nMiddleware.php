<?php
namespace ADmad\I18n\Middleware;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\I18n\I18n;
use Cake\Network\Request;
use Cake\Utility\Hash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

class I18nMiddleware
{
    use InstanceConfigTrait;

    /**
     * Default config.
     *
     * ### Valid keys
     *
     * - `detectLanguage`: If `true` will attempt to get browser locale and
     *   redirect to similar language available in app when going to site root.
     *   Default `false`.
     * - `defaultLanguage`: Default language for app. Default `en_US`.
     * - `languages`: Languages available in app. Default `[]`.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'detectLanguage' => false,
        'defaultLanguage' => 'en_US',
        'languages' => [],
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

        $this->config($config);
    }

    /**
     * Sets appropriate locale and lang to I18n::locale() and App.language config
     * respectively based on "lang" request param.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     *
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $url = $request->getUri()->getPath();

        if ($url === '' || $url === '/') {
            $statusCode = 301;
            $lang = $this->_config['defaultLanguage'];
            if ($this->_config['detectLanguage']) {
                $statusCode = 302;
                $lang = $this->detectLanguage($request, $lang);
            }

            $response = new RedirectResponse(
                $request->getAttribute('webroot') . $lang,
                $statusCode
            );

            return $response;
        }

        $langs = $this->_config['languages'];
        $requestParams = $request->getAttribute('params');
        $lang = isset($requestParams['lang']) ? $requestParams['lang'] : $this->_config['defaultLanguage'];
        if (isset($langs[$lang])) {
            I18n::locale($langs[$lang]['locale']);
        } else {
            I18n::locale($lang);
        }

        Configure::write('App.language', $lang);

        return $next($request, $response);
    }

    /**
     * Get languages accepted by browser and return the one matching one of
     * those in config var `I18n.languages`.
     *
     * You should set config var `I18n.languages` to an array containing
     * languages available in your app.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param string|null $default Default language to return if no match is found.
     *
     * @return string
     */
    public function detectLanguage(ServerRequestInterface $request, $default = null)
    {
        if (empty($default)) {
            $lang = $this->_config['defaultLanguage'];
        } else {
            $lang = $default;
        }

        $cakeRequest = new Request();
        $browserLangs = $cakeRequest->acceptLanguage();
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
