<?php
declare(strict_types=1);
namespace ADmad\I18n\Test\TestCase\Middleware;

use ADmad\I18n\Middleware\I18nMiddleware;
use Cake\Http\ServerRequestFactory;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Locale;
use TestApp\Http\TestRequestHandler;

/**
 * I18nMiddleware test.
 */
class I18nMiddlewareTest extends TestCase
{
    protected $server;

    protected $locale;

    protected $config;

    protected $request;

    protected $handler;

    /**
     * setup.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->server = $_SERVER;

        $this->locale = Locale::getDefault();

        $this->config = [
            'defaultLanguage' => 'fr',
            'languages' => ['fr', 'en'],
        ];

        $_SERVER['REQUEST_URI'] = '/';
        $this->request = ServerRequestFactory::fromGlobals();
        $this->request = $this->request->withAttribute('webroot', '/');
        $this->handler = new TestRequestHandler();
    }

    /**
     * Resets the default locale.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Locale::setDefault($this->locale);
        $_SERVER = $this->server;
    }

    /**
     * testRedirectionFromSiteRoot.
     *
     * @return void
     */
    public function testRedirectionFromSiteRoot()
    {
        $middleware = new I18nMiddleware($this->config + ['detectLanguage' => false]);
        $response = $middleware->process($this->request, $this->handler);

        $headers = $response->getHeaders();
        $this->assertEquals('/fr', $headers['location'][0]);
        $this->assertEquals(301, $response->getStatusCode());

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,es;q=0.6,da;q=0.4';
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('webroot', '/');
        $middleware = new I18nMiddleware($this->config);
        $response = $middleware->process($request, $this->handler);

        $headers = $response->getHeaders();
        $this->assertEquals('/en', $headers['location'][0]);
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * testLocaleSetting.
     *
     * @return void
     */
    public function testLocaleSetting()
    {
        $_SERVER['REQUEST_URI'] = '/fr';
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('params', ['lang' => 'fr']);
        $middleware = new I18nMiddleware($this->config);
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('fr', I18n::getLocale());
    }
}
