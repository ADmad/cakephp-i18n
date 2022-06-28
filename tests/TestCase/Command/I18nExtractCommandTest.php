<?php
declare(strict_types=1);

namespace ADmad\I18n\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use TestPlugin\Plugin;

/**
 * I18nExtractCommand Test Case.
 */
class I18nExtractCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = ['plugin.ADmad/I18n.I18nMessages'];

    public function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
        $this->configApplication(
            'TestApp\Application',
            [PLUGIN_TESTS . 'test_app' . DS . 'config']
        );

        $this->model = $this->getTableLocator()->get('I18nMessages');
        $this->model->deleteAll(['1 = 1']);

        Configure::write('I18n.languages', ['en_US', 'fr_FR']);
    }

    public function testExecute()
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--merge=no ' .
            '--paths=' . PLUGIN_TESTS . 'test_app/templates/Pages'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['domain' => 'default'])
            ->count();
        $this->assertTrue($result > 0);

        $result = $this->model->find()
            ->where(['domain' => 'domain'])
            ->count();
        $this->assertTrue($result > 0);

        $result = $this->model->find()
            ->where(['domain' => 'cake'])
            ->count();
        $this->assertTrue($result === 0);

        $result = $this->model->find()
            ->where(['singular' => 'You have %d new message.'])
            ->enableHydration(false)
            ->first();

        $this->assertTrue((bool)$result);

        $result = $this->model->find()
            ->where(['singular' => 'letter'])
            ->enableHydration(false)
            ->first();
        $this->assertEquals('mail', $result['context']);

        $result = $this->model->find()
            ->where([
                'domain' => 'domain',
                'singular' => 'You have %d new message (domain).',
            ])
            ->enableHydration(false)
            ->first();
        $this->assertEquals('You have %d new message (domain).', $result['singular']);
        $this->assertEquals('You have %d new messages (domain).', $result['plural']);
    }

    /**
     * testExecute with merging on method.
     *
     * @return void
     */
    public function testExecuteMerge()
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--merge=yes ' .
            '--paths=' . PLUGIN_TESTS . 'test_app/templates/Pages'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['domain' => 'default'])
            ->count();
        $this->assertTrue($result > 0);

        $result = $this->model->find()
            ->where(['domain' => 'domain'])
            ->count();
        $this->assertTrue($result === 0);
    }

    /**
     * test exclusions.
     *
     * @return void
     */
    public function testExtractWithExclude()
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--exclude=Pages,layout ' .
            '--paths=' . PLUGIN_TESTS . 'test_app/templates'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['refs LIKE' => '%extract.php%'])
            ->count();
        $this->assertSame(0, $result);

        $result = $this->model->find()
            ->where(['refs LIKE' => '%cache_form.php%'])
            ->count();
        $this->assertTrue($result > 0);
    }

    /**
     * testExtractWithoutLocations method.
     *
     * @return void
     */
    public function testExtractWithoutLocations()
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--exclude=Pages,layout ' .
            '--no-location ' .
            '--paths=' . PLUGIN_TESTS . 'test_app/templates'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['refs IS NOT' => null])
            ->count();
        $this->assertSame(0, $result);
    }

    /**
     * test extract can read more than one path.
     *
     * @return void
     */
    public function testExtractMultiplePaths()
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--paths=' . PLUGIN_TESTS . 'test_app/templates/Pages,' . PLUGIN_TESTS . 'test_app/templates/Posts'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['refs LIKE' => '%extract.php%'])
            ->count();
        $this->assertTrue($result > 0);

        $result = $this->model->find()
            ->where(['refs LIKE' => '%cache_form.php%'])
            ->count();
        $this->assertTrue($result > 0);
    }

    /**
     * Tests that it is possible to exclude plugin paths by enabling the param option for the ExtractTask.
     *
     * @return void
     */
    public function testExtractExcludePlugins()
    {
        $this->exec(
            'i18n extract ' .
            '--exclude-plugins ' .
            '--paths=' . PLUGIN_TESTS . 'test_app/src --extract-core=no'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['refs LIKE' => '%TestPlugin%'])
            ->count();
        $this->assertTrue($result === 0);
    }

    /**
     * Test that is possible to extract messages from a single plugin.
     *
     * @return void
     */
    public function testExtractPlugin()
    {
        $plugin = new Plugin();
        $this->loadPlugins([$plugin]);

        $this->exec(
            'i18n extract ' .
            '--plugin=TestPlugin --extract-core=no'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['refs LIKE' => '%Pages%'])
            ->count();
        $this->assertTrue($result === 0);

        $result = $this->model->find()
            ->where(['refs LIKE' => '%translate.php%'])
            ->count();
        $this->assertTrue($result > 0);
    }

    /**
     * Test that is possible to extract messages from a vendored plugin.
     *
     * @return void
     */
    public function testExtractVendoredPlugin()
    {
        // phpcs:ignore
        $plugin = new \Company\TestPluginThree\Plugin();
        $this->loadPlugins([$plugin]);

        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--plugin=Company/TestPluginThree'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['domain' => 'default'])
            ->count();
        $this->assertTrue($result === 0);

        $result = $this->model->find()
            ->where(['domain' => 'company/test_plugin_three'])
            ->count();
        $this->assertTrue($result > 0);
    }

    /**
     *  Test that the extract shell scans the core libs.
     *
     * @return void
     */
    public function testExtractCore()
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=yes ' .
            '--paths=' . PLUGIN_TESTS . 'test_app/src'
        );
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['domain' => 'cake'])
            ->count();
        $this->assertTrue($result > 0);
    }
}
