# CakePHP I18n Messages plugin

[![Build Status](https://img.shields.io/travis/ADmad/cakephp-i18n-messages/master.svg?style=flat-square)](https://travis-ci.org/ADmad/cakephp-i18n-messages)
[![Coverage](https://img.shields.io/coveralls/ADmad/cakephp-i18n-messages/master.svg?style=flat-square)](https://coveralls.io/r/ADmad/cakephp-i18n-messages)
[![Total Downloads](https://img.shields.io/packagist/dt/ADmad/cakephp-i18n-messages.svg?style=flat-square)](https://packagist.org/packages/ADmad/cakephp-i18n-messages)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# Intro

This plugins allows retrieving translation messages stored in database instead
of po/mo files.

## Requirements

* CakePHP 3.0+

## Installation

`php composer.phar require admad/cakephp-i18n-messages:1.0.x-dev`

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
