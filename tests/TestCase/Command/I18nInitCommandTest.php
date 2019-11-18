<?php
declare(strict_types=1);

namespace ADmad\I18n\Test\TestCase\Command;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * I18nInitCommand Test Case.
 */
class I18nInitCommandTest extends ConsoleIntegrationTestCase
{
    protected $fixtures = ['plugin.ADmad/I18n.I18nMessages'];

    public function setUp(): void
    {
        parent::setUp();

        $this->useCommandRunner();
        $this->setAppNamespace();

        $this->model = TableRegistry::getTableLocator()->get('I18nMessages');
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
