<?php
declare(strict_types=1);

namespace ADmad\I18n\Test\TestCase\Shell;

use ADmad\I18n\Shell\I18nShell;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * I18nShell test.
 */
class I18nShellTest extends TestCase
{
    /**
     * fixtures.
     *
     * @var array
     */
    public $fixtures = ['plugin.ADmad/I18n.I18nMessages'];

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $this->shell = new I18nShell($this->io);

        $this->model = TableRegistry::get('I18nMessages');
    }

    /**
     * Tests that init() creates the PO files from POT files.
     *
     * @return void
     */
    public function testInit()
    {
        $result = $this->model->find()
            ->where(['locale' => 'de_DE'])
            ->count();
        $this->assertSame(0, $result);

        $this->shell->getIo()->expects($this->at(0))
            ->method('ask')
            ->will($this->returnValue('de_DE'));

        $this->shell->params['verbose'] = true;
        $this->shell->init();

        $result = $this->model->find()
            ->where(['locale' => 'de_DE'])
            ->count();
        $this->assertTrue($result > 0);
    }

    /**
     * Test that the option parser is shaped right.
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $this->shell->loadTasks();
        $parser = $this->shell->getOptionParser();
        $this->assertArrayHasKey('init', $parser->subcommands());
        $this->assertArrayHasKey('extract', $parser->subcommands());
    }
}
