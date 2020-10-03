# CakePHP plugin for I18n related tools.

[![Build Status](https://img.shields.io/travis/ADmad/cakephp-i18n/master.svg?style=flat-square)](https://travis-ci.org/ADmad/cakephp-i18n)
[![Coverage Status](https://img.shields.io/codecov/c/github/ADmad/cakephp-i18n.svg?style=flat-square)](https://codecov.io/github/ADmad/cakephp-i18n)
[![Total Downloads](https://img.shields.io/packagist/dt/ADmad/cakephp-i18n.svg?style=flat-square)](https://packagist.org/packages/ADmad/cakephp-i18n)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# Intro

This plugins provides:

- Route class for generating and matching urls with language prefix.
- Middleware which sets locale using `I18n::setLocale()`
  based on language prefix in URL and also provides redirection to appropriate
  URL with language prefix when accessing site root.
- Class for retrieving translation messages stored in database instead of po/mo files.
- Validation class for auto translating validation message.
- A widget to generate select box with list of timezone identifiers.

## Installation

```
composer require admad/cakephp-i18n
```

## Usage

Load the plugin by running command:

```
bin/cake plugin load ADmad/I18n
```

The plugin contains multiple classes useful for internationalization. You can pick
and chose the ones you require.

### I18nRoute

The `I18nRoutes` helps generating language prefixed routes of style
`/:lang/:controller/:action`.

For e.g. you can add routes to your `routes.php` similar to the ones shown below:

```php
$routes->scope('/', function ($routes) {
    $routes->connect(
        '/:controller',
        ['action' => 'index'],
        ['routeClass' => 'ADmad/I18n.I18nRoute']
    );
    $routes->connect(
        '/:controller/:action/*',
        [],
        ['routeClass' => 'ADmad/I18n.I18nRoute']
    );
});
```

Fragment `/:lang` will be auto prefixed to the routes which allows matching
URLs like `/en/posts`, `/en/posts/add` etc. The `:lang` element is persisted so
that when generating URLs if you don't provide the `lang` key in URL array it
will be automatically added based on current URL.

When connecting the routes you can use `lang` key in options to provide regular
expression to match only languages which your app supports. Or your can set
config value `I18n.languages` which the route class will use to auto generate
regex for `lang` element matching:

```php
Configure::write('I18n.languages', ['en', 'fr', 'de']);
```

Note: `I18nRoute` extends core's `DashedRoute` so the URL fragments will be
inflected accordingly.

### I18nMiddleware

While not necessary, one would generally use the `I18nMiddleware` too when using
language prefixed routes with the help of `I18nRoute`.

You can setup the `I18nMiddleware` in your `src/Application::middleware()` as
shown:

```php
$middlware->add(new \ADmad\I18n\Middleware\I18nMiddleware([
    // If `true` will attempt to get matching languges in "languages" list based
    // on browser locale and redirect to that when going to site root.
    'detectLanguage' => true,
    // Default language for app. If language detection is disabled or no
    // matching language is found redirect to this language
    'defaultLanguage' => 'en',
    // Languages available in app. The keys should match the language prefix used
    // in URLs. Based on the language the locale will be also set.
    'languages' => [
        'en' => ['locale' => 'en_US'],
        'fr' => ['locale' => 'fr_FR']
    ],
]));
```

The keys of `languages` array are the language prefixes you use in your URL.

To ensure that the `lang` router param is available, you must add this middleware
*after* adding CakePHP's default routing middleware (i.e. after `->add(new RoutingMiddleware($this))`).

The middleware does basically two things:

1. When accessing site root `/` it redirects the user to a language prefixed URL,
   for e.g. `/en`. The langauge is redirects to depends on the configuration keys
   `detectLanguage` and `defaultLanguage` shown above.

   Now in order to prevent CakePHP from complaining about missing route for `/`,
   you must connect a route for `/` to a controller action. That controller action
   will never be actually called as the middleware will intercept and redirect
   the request.

   For e.g. `$routes->connect('/', ['controller' => 'Foo']);`

2. When accesing any URL with language prefix    it set's the app's locale based
   on the prefix. For that it checks the value of `lang` route element in current
   request's params. This route element would be available if the matched route
   has been connects using the `I18nRoute`.

   Using the array provided for the `languages` key it sets the `App.language`
   config to language prefix through `Configure::write()` and the value of `locale`
   is for `I18n::setLocale()` call.

### DbMessagesLoader

By default CakePHP using `.po` files to store static string translations. If
for whatever reason you don't want to use `.po` files you can use the `DbMessagesLoader`
class to store the translation messaged in database instead. Personally I belive
having the messages in a table instead of `.po` files make it much easier to
make a web interface for managing translations.

To use this class first create database table using sql file provided in the
plugin's `config` folder.

Add code similar to what's shown below in your app's `config/bootstrap.php`:

```php
// NOTE: This is should be done below Cache config setup.

// Configure I18n to use DbMessagesLoader for default domain. You need to do
// this for each domain separately.
I18n::config('default', function ($domain, $locale) {
    $loader = new \ADmad\I18n\I18n\DbMessagesLoader(
        $domain,
        $locale
    );
    
    return $loader();
});
```

You can use `admad/i18n extract` command to extract the translation message from your
code files and populate the translations table. Updating the db records with
translations for each language is upto you.

```
bin/cake admad/i18n extract
```

Now you can use the translation funtions like `__()` etc. as you normally would.
The `I18n` class will fetch the required translations from the database instead
of `.po` files.

### TimezoneWidget

In your `AppView::initialize()` configure the `FormHelper` to use `TimezoneWidget`.

```php
// src/View/AppView.php
public function initialize(): void
{
    $this->loadHelper('Form', [
        'widgets' => [
            'timezone' => ['ADmad/I18n.Timezone']
        ]
    ]);
}
```

You can generate a select box with timezone identifiers like:

```php
// Generates select box with list of all timezone identifiers grouped by regions.
$this->Form->control('fieldname', ['type' => 'timezone']);

// Generates select box with list of timezone identifiers for specified regions.
$this->Form->control('fieldname', [
    'type' => 'timezone',
    'options' => [
        'Asia' => DateTimeZone::ASIA,
        'Europe' => DateTimeZone::EUROPE
    ]
]);
```

As shown in example above note that unlike normal select box, `options` is now
an associative array of valid timezone regions where the key will be used as
`optgroup` in the select box.
