<?php
declare(strict_types=1);

namespace ADmad\I18n\Test\Validation;

use ADmad\I18n\I18n\DbMessagesLoader;
use ADmad\I18n\Validation\Validator;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * Tests for Validator.
 */
class ValidatorTest extends TestCase
{
    protected array $fixtures = ['plugin.ADmad/I18n.I18nMessages'];

    protected Validator $validator;

    public function setUp(): void
    {
        if (version_compare(Configure::version(), '5.1.0', '<')) {
            Cache::clear('_cake_core_');
        } else {
            Cache::clear('_cake_translations_');
        }

        I18n::config('validation', function ($domain, $locale) {
            $loader = new DbMessagesLoader(
                $domain,
                $locale,
            );

            return $loader();
        });

        I18n::config('validation_non_default', function ($domain, $locale) {
            $loader = new DbMessagesLoader(
                $domain,
                $locale,
            );

            return $loader();
        });

        $I18nMessages = $this->getTableLocator()->get('I18nMessages');
        $messages = [
            [
                'domain' => 'validation',
                'locale' => I18n::getLocale(),
                'context' => '',
                'singular' => 'email',
                'plural' => '',
                'value_0' => 'Enter a valid email',
                'value_1' => '',
            ],
            [
                'domain' => 'validation',
                'locale' => I18n::getLocale(),
                'context' => '',
                'singular' => 'comparison',
                'plural' => '',
                'value_0' => 'This value must be {0} than {1}',
                'value_1' => '',
            ],
            [
                'domain' => 'validation',
                'locale' => I18n::getLocale(),
                'context' => '',
                'singular' => '<',
                'plural' => '',
                'value_0' => 'less',
                'value_1' => '',
            ],
            [
                'domain' => 'validation_non_default',
                'locale' => I18n::getLocale(),
                'context' => '',
                'singular' => 'email',
                'plural' => '',
                'value_0' => 'Message from validation_non_default',
                'value_1' => '',
            ],
            [
                'domain' => 'validation',
                'locale' => I18n::getLocale(),
                'context' => '',
                'singular' => 'mimeType',
                'plural' => '',
                'value_0' => 'Valid mime types: {0}',
                'value_1' => '',
            ],
        ];
        foreach ($messages as $row) {
            $I18nMessages->save($I18nMessages->newEntity($row));
        }

        $this->validator = new Validator();

        $this->validator
            ->add('email', 'email', ['rule' => 'email'])
            ->add('field', 'comparison', ['rule' => ['comparison', '<', 50]])
            ->add('file', 'mimeType', ['rule' => ['mimeType', ['image/jpeg, image/png']]]);
    }

    /**
     * [testErrors description].
     *
     * @return void
     */
    public function testErrors()
    {
        $file = new UploadedFile(__FILE__, 1, UPLOAD_ERR_OK, 'foo.txt', 'text/plain');

        $errors = $this->validator->validate([
            'email' => 'foo',
            'field' => '100',
            'file' => $file,
        ]);

        $expected = [
            'email' => ['email' => 'Enter a valid email'],
            'field' => ['comparison' => 'This value must be less than 50'],
            'file' => ['mimeType' => 'Valid mime types: image/jpeg, image/png'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * [testNonDefaultDomain description].
     *
     * @return void
     */
    public function testNonDefaultDomain()
    {
        $this->validator->validationDomain('validation_non_default');
        $this->assertEquals('validation_non_default', $this->validator->validationDomain());

        $errors = $this->validator->validate([
            'email' => 'foo',
        ]);

        $expected = [
            'email' => ['email' => 'Message from validation_non_default'],
        ];
        $this->assertEquals($expected, $errors);
    }
}
