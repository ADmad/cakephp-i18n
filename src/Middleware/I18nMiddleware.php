<?php
declare(strict_types=1);

namespace ADmad\I18n\Middleware;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\Utility\Hash;
use Closure;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
     * - `defaultLanguage`: Default language for app. Defaults to value of `I18n::getDefaultLocale()`
     * - `languages`: Languages available in app. Default `[]`.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'detectLanguage' => true,
        'defaultLanguage' => null,
        'languages' => [],
    ];

    /**
     * Closure for deciding whether or not to ignore particular request.
     *
     * Request will not be processd if the callback returns `true`.
     *
     * @var \Closure|null
     */
    protected ?Closure $_ignoreRequestCallback = null;

    /**
     * Constructor.
     *
     * @param array $config Settings for the filter.
     */
    public function __construct(array $config = [])
    {
        $this->_defaultConfig['defaultLanguage'] = I18n::getDefaultLocale();
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
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            $this->_ignoreRequestCallback !== null
            && call_user_func($this->_ignoreRequestCallback, $request) === true
        ) {
            return $handler->handle($request);
        }

        $config = $this->getConfig();

        /** @var \Cake\Http\ServerRequest $request */
        if ($request->getPath() === '/') {
            $statusCode = 301;
            $lang = $config['defaultLanguage'];
            if ($config['detectLanguage']) {
                $statusCode = 302;
                $lang = $this->detectLanguage($request, $lang);
            }

            return new RedirectResponse(
                $request->getAttribute('webroot') . $lang,
                $statusCode
            );
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
     * Set closure for deciding whether or not to ignore particular request.
     *
     * Request will not be processd if the callback returns `true`.
     *
     * @param \Closure $callback A callback.
     * @return $this
     */
    public function ignoreRequestCallback(Closure $callback)
    {
        $this->_ignoreRequestCallback = $callback;

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
     * @return string
     */
    public function detectLanguage(ServerRequest $request, ?string $default = null): string
    {
        if (!$default) {
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
            /** @var string $lang */
            $lang = reset($acceptedLangs);
        }

        return $lang;
    }
}
