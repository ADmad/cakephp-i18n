[![Build Status](https://travis-ci.org/ADmad/cakephp-i18n-messages.png?branch=master)](https://travis-ci.org/ADmad/cakephp-i18n-messages)

# Intro

This plugins allows retrieving translation messages stored in database instead
of po/mo files.

## Requirements

* CakePHP 3.0+

## Installation

`php composer.phar require admad/cakephp-i18n-messages:*`

## Usage

Create database table using sql file provided in `config` folder.

Add code similar to what's shown below in your app's `config/bootstrap.php`:

```php
// Load the plugin. Instead of using 'autoload' you can use composer's autoloader too.
Plugin::load('I18nMessages', ['autoload' => true]);

// Configure I18n to use DbMessagesLoader for default domain. You need to do
// this for each domain separately.
I18n::config('default', function ($domain, $locale) {
	return new \I18nMessages\I18n\DbMessagesLoader(
		$domain,
		$locale
	);
});
```
