<?php
declare(strict_types=1);

namespace ADmad\I18n\Middleware;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\Utility\Hash;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class I18nMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;

    /**
     * Default config.
     *
     * ### Valid keys
     *
     * - `detectLanguage`: If `true` will attempt to get browser locale and
     *   redirect to similar language available in app when going to site root.
     *   Default `true`.
     * - `defaultLanguage`: Default language for app. Default `en_US`.
     * - `languages`: Languages available in app. Default `[]`.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'detectLanguage' => true,
        'defaultLanguage' => 'en_US',
        'languages' => [],
    ];

    /**
     * Closure for deciding whether or not to skip the token check for particular request.
     *
     * CSRF protection token check will be skipped if the callback returns `true`.
     *
     * @var \Closure|null
     */
    protected $_whitelistCallback;

    /**
     * Constructor.
     *
     * @param array $config Settings for the filter.
     */
    public function __construct(array $config = [])
    {
        if (isset($config['languages'])) {
            $config['languages'] = Hash::normalize($config['languages']);
        }

        $this->setConfig($config);
    }

    /**
     * Sets appropriate locale and lang to I18n::locale() and App.language config
     * respectively based on "lang" request param.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     *
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            $this->_whitelistCallback !== null
            && call_user_func($this->_whitelistCallback, $request) === true
        ) {
            return $handler->handle($request);
        }

        $config = $this->getConfig();
        $url = $request->getUri()->getPath();

        if ($url === '/') {
            $statusCode = 301;
            $lang = $config['defaultLanguage'];
            if ($config['detectLanguage']) {
                $statusCode = 302;
                /** @psalm-suppress ArgumentTypeCoercion */
                $lang = $this->detectLanguage($request, $lang);
            }

            $response = new RedirectResponse(
                $request->getAttribute('webroot') . $lang,
                $statusCode
            );

            return $response;
        }

        $langs = $config['languages'];
        $requestParams = $request->getAttribute('params');
        $lang = $requestParams['lang'] ?? $config['defaultLanguage'];
        if (isset($langs[$lang])) {
            I18n::setLocale($langs[$lang]['locale']);
        } else {
            I18n::setLocale($lang);
        }

        Configure::write('App.language', $lang);

        return $handler->handle($request);
    }

    /**
     * Set callback for allowing to skip token check for particular request.
     *
     * The callback will receive request instance as argument and must return
     * `true` if you want to skip token check for the current request.
     *
     * @param \Closure $callback A callback.
     * @return $this
     */
    public function whitelistCallback(Closure $callback)
    {
        $this->_whitelistCallback = $callback;

        return $this;
    }

    /**
     * Get languages accepted by browser and return the one matching one of
     * those in config var `I18n.languages`.
     *
     * You should set config var `I18n.languages` to an array containing
     * languages available in your app.
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @param string|null $default Default language to return if no match is found.
     *
     * @return string
     */
    public function detectLanguage(ServerRequest $request, ?string $default = null)
    {
        if (empty($default)) {
            $lang = $this->_config['defaultLanguage'];
        } else {
            $lang = $default;
        }

        /** @var array $browserLangs */
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
