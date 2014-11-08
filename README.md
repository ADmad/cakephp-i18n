# I18nMessages plugin

This plugins allows retrieving translation messages stored in database instead
of po/mo files.

## Requirements

* CakePHP 3.0+

## Usage

Add code similar to what's shown below in your app's `config/bootstrap.php`:

```php
// Load the plugin. Instead of using 'autoload' you can use composer's autoloader too.
Plugin::load($plugin, ['autoload' => true]);

// Configure I18n to use DbMessagesLoader for default domain. You need to do
// this for each domain separately.
I18n::config('default', function ($domain, $locale) {
	return new \I18nMessages\I18n\DbMessagesLoader(
		$domain,
		$locale
	);
});
```

## TODO

* Add tests.
* Add support for plural string and context. Currently only supports singular
  strings.
