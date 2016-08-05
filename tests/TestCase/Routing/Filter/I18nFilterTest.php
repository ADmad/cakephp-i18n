<?php
namespace ADmad\I18n\Test\TestCase\Routing\Filter;

use ADmad\I18n\Routing\Filter\I18nFilter;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Locale;

/**
 * I18nFilter filter test.
 */
class I18nFilterTest extends TestCase
{
    /**
     * setup.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->locale = Locale::getDefault();

        $this->filter = new I18nFilter([
            'defaultLanguage' => 'fr',
            'languages' => ['fr', 'en'],
        ]);
        $this->request = new Request();
        $this->request->webroot = '/';
        $this->response = new Response();
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
    }

    /**
     * testRedirectionFromSiteRoot.
     *
     * @return void
     */
    public function testRedirectionFromSiteRoot()
    {
        $event = new Event(__CLASS__, $this, [
            'request' => $this->request,
            'response' => $this->response,
        ]);
        $result = $this->filter->beforeDispatch($event);

        $this->assertTrue($result instanceof Response);
        $this->assertEquals('/fr', $this->response->location());
        $this->assertEquals(301, $this->response->statusCode());

        $request = new Request([
            'environment' => ['HTTP_ACCEPT_LANGUAGE' => 'en_US,en;q=0.8,es;q=0.6,da;q=0.4'],
        ]);
        $request->webroot = '/';
        $this->filter->config('detectLanguage', true);
        $event = new Event(__CLASS__, $this, [
            'request' => $request,
            'response' => $this->response,
        ]);
        $result = $this->filter->beforeDispatch($event);

        $this->assertTrue($result instanceof Response);
        $this->assertEquals('/en', $this->response->location());
        $this->assertEquals(302, $this->response->statusCode());
    }

    /**
     * testRedirectToLang.
     *
     * @return void
     */
    public function testRedirectToLang()
    {
        $this->filter->config('redirectToLang', true);
        $event = new Event(__CLASS__, $this, [
            'request' => $this->request,
            'response' => $this->response,
        ]);
        $this->request->webroot = '/';
        $this->request->url = 'sample/page';
        $result = $this->filter->beforeDispatch($event);

        $this->assertTrue($result instanceof Response);
        $this->assertEquals('/fr/sample/page', $this->response->location());
        $this->assertEquals(301, $this->response->statusCode());
    }

    /**
     * testRedirectToLangCallback.
     *
     * @return void
     */
    public function testRedirectToLangCallback()
    {
        $this->filter->config('redirectToLang', function ($request, $event) {
            return true;
        });
        $event = new Event(__CLASS__, $this, [
            'request' => $this->request,
            'response' => $this->response,
        ]);
        $this->request->webroot = '/';
        $this->request->url = 'sample/page';
        $result = $this->filter->beforeDispatch($event);

        $this->assertTrue($result instanceof Response);
        $this->assertEquals('/fr/sample/page', $this->response->location());
        $this->assertEquals(301, $this->response->statusCode());
    }

    /**
     * testNoRedirectToLang.
     *
     * @return void
     */
    public function testNoRedirectToLang()
    {
        $this->filter->config('redirectToLang', false);
        $event = new Event(__CLASS__, $this, [
            'request' => $this->request,
            'response' => $this->response,
        ]);
        $this->request->webroot = '/';
        $this->request->url = 'sample/page';
        $result = $this->filter->beforeDispatch($event);

        $this->assertNull($result);
        $this->assertNull($this->response->location());
        $this->assertEquals(200, $this->response->statusCode());
    }

    /**
     * testLocaleSetting.
     *
     * @return void
     */
    public function testLocaleSetting()
    {
        $request = new Request('/fr');
        $request->params['lang'] = 'fr';
        $event = new Event(__CLASS__, $this, [
            'request' => $request,
            'response' => $this->response,
        ]);
        $result = $this->filter->beforeDispatch($event);
        $this->assertNull($result);
        $this->assertEquals('fr', I18n::locale());
    }
}
