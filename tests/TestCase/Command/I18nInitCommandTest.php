<?php
declare(strict_types=1);

namespace ADmad\I18n\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * I18nInitCommand Test Case.
 */
class I18nInitCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = ['plugin.ADmad/I18n.I18nMessages'];

    protected Table $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
        $this->configApplication(
            'TestApp\Application',
            [PLUGIN_TESTS . 'test_app' . DS . 'config'],
        );

        $this->model = $this->getTableLocator()->get('I18nMessages');
    }

    public function testExecute()
    {
        $result = $this->model->find()
            ->where(['locale' => 'de_DE'])
            ->count();
        $this->assertSame(0, $result);

        $this->exec('i18n init de_DE');
        $this->assertExitSuccess();

        $result = $this->model->find()
            ->where(['locale' => 'de_DE'])
            ->count();
        $this->assertTrue($result > 0);
    }
}
