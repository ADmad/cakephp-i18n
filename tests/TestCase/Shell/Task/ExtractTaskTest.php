<?php
namespace App\Test\TestCase\Shell\Task;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * ExtractTask Test Case.
 */
class ExtractTaskTest extends TestCase
{
    /**
     * fixtures.
     *
     * @var array
     */
    public $fixtures = ['plugin.ADmad/I18n.I18nMessages'];

    /**
     * setUp method.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $progress = $this->getMockBuilder('Cake\Shell\Helper\ProgressHelper')
            ->setConstructorArgs([$this->io])
            ->getMock();
        $this->io->method('helper')
            ->will($this->returnValue($progress));

        $this->Task = $this->getMockBuilder('ADmad\I18n\Shell\Task\ExtractTask')
            ->setMethods(['in', 'out', 'err', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->model = TableRegistry::get('I18nMessages');
        $this->model->deleteAll(['1 = 1']);

        Configure::write('I18n.languages', ['en_US', 'fr_FR']);
    }

    /**
     * testExecute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->Task->params['paths'] = PLUGIN_TESTS . 'test_app/src/Template/Pages';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['output'] = TMP;
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->main();

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
        $this->Task->params['paths'] = PLUGIN_TESTS . 'test_app/src/Template/Pages';
        $this->Task->params['output'] = TMP;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'yes';

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->main();

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
        $this->Task->interactive = false;

        $this->Task->params['paths'] = PLUGIN_TESTS . 'test_app/src/Template';
        $this->Task->params['output'] = TMP;
        $this->Task->params['exclude'] = 'Pages,Layout';
        $this->Task->params['extract-core'] = 'no';

        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));

        $this->Task->main();

        $result = $this->model->find()
            ->where(['refs LIKE' => '%extract.ctp%'])
            ->count();
        $this->assertTrue($result === 0);

        $result = $this->model->find()
            ->where(['refs LIKE' => '%cache_form.ctp%'])
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
        $this->Task->params['paths'] = PLUGIN_TESTS . 'test_app/src/Template';
        $this->Task->params['output'] = TMP;
        $this->Task->params['exclude'] = 'Pages,Layout';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['no-location'] = true;

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));

        $this->Task->main();

        $result = $this->model->find()
            ->where(['refs IS NOT' => null])
            ->count();
        $this->assertTrue($result === 0);
    }

    /**
     * test extract can read more than one path.
     *
     * @return void
     */
    public function testExtractMultiplePaths()
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] =
            PLUGIN_TESTS . 'test_app/src/Template/Pages,' .
            PLUGIN_TESTS . 'test_app/src/Template/Posts';

        $this->Task->params['output'] = TMP;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->never())->method('_stop');
        $this->Task->main();

        $result = $this->model->find()
            ->where(['refs LIKE' => '%extract.ctp%'])
            ->count();
        $this->assertTrue($result > 0);

        $result = $this->model->find()
            ->where(['refs LIKE' => '%cache_form.ctp%'])
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
        Configure::write('App.namespace', 'TestApp');
        $this->Task = $this->getMockBuilder('ADmad\I18n\Shell\Task\ExtractTask')
            ->setMethods(['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();
        $this->Task->expects($this->exactly(1))
            ->method('_isExtractingApp')
            ->will($this->returnValue(true));

        $this->Task->params['paths'] = PLUGIN_TESTS . 'test_app/src';
        $this->Task->params['output'] = TMP;
        $this->Task->params['exclude-plugins'] = true;

        $this->Task->main();

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
        if (method_exists($this, 'loadPlugins')) {
            $this->loadPlugins(['TestPlugin']);
        } else {
            Plugin::load('TestPlugin');
        }

        Configure::write('App.namespace', 'TestApp');

        $this->Task = $this->getMockBuilder('ADmad\I18n\Shell\Task\ExtractTask')
            ->setMethods(['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->Task->params['output'] = TMP;
        $this->Task->params['plugin'] = 'TestPlugin';

        $this->Task->main();

        $result = $this->model->find()
            ->where(['refs LIKE' => '%Pages%'])
            ->count();
        $this->assertTrue($result === 0);

        $result = $this->model->find()
            ->where(['refs LIKE' => '%translate.ctp%'])
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
        if (method_exists($this, 'loadPlugins')) {
            $this->loadPlugins(['Company/TestPluginThree']);
        } else {
            Plugin::load('Company/TestPluginThree');
        }

        Configure::write('App.namespace', 'TestApp');

        $this->Task = $this->getMockBuilder('ADmad\I18n\Shell\Task\ExtractTask')
            ->setMethods(['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->Task->params['output'] = TMP;
        $this->Task->params['plugin'] = 'Company/TestPluginThree';

        $this->Task->main();

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
        Configure::write('App.namespace', 'TestApp');
        $this->Task->interactive = false;

        $this->Task->params['paths'] = PLUGIN_TESTS . 'test_app/src/';
        $this->Task->params['output'] = TMP;
        $this->Task->params['extract-core'] = 'yes';

        $this->Task->main();

        $result = $this->model->find()
            ->where(['domain' => 'cake'])
            ->count();
        $this->assertTrue($result > 0);
    }
}
