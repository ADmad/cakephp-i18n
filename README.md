# CakePHP plugin for I18n related tools.

[![Build Status](https://img.shields.io/travis/ADmad/cakephp-i18n/master.svg?style=flat-square)](https://travis-ci.org/ADmad/cakephp-i18n)
[![Coverage](https://img.shields.io/coveralls/ADmad/cakephp-i18n/master.svg?style=flat-square)](https://coveralls.io/r/ADmad/cakephp-i18n)
[![Total Downloads](https://img.shields.io/packagist/dt/ADmad/cakephp-i18n.svg?style=flat-square)](https://packagist.org/packages/ADmad/cakephp-i18n)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# Intro

This plugins provides:

- Class for retrieving translation messages stored in database instead of po/mo files.
- Validation class auto translating validation message.

## Requirements

* CakePHP 3.0+

## Installation

```
composer require admad/cakephp-i18n:1.0.x-dev
```

## Usage

Create database table using sql file provided in `config` folder.

Add code similar to what's shown below in your app's `config/bootstrap.php`:

```php
// Load the plugin.
Plugin::load('ADmad/I18n');

// Configure I18n to use DbMessagesLoader for default domain. You need to do
// this for each domain separately.
I18n::config('default', function ($domain, $locale) {
	return new \ADmad\I18n\I18n\DbMessagesLoader(
		$domain,
		$locale
	);
});
```
