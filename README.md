# Internationalization API

Small PHP internationalization package for detecting a user's locale, reading translations from JSON files, and writing translation files back to disk.

The package is intentionally compact:

- XML configuration for locale detection and translation storage
- request, session, or `Accept-Language` locale detection
- locale fallback chain: preferred locale, language-only locale, default locale
- JSON translation dictionaries grouped by locale and domain
- lookup helpers for single translations, plural translations, existence checks, and full-domain reads
- writer support for adding, removing, and saving translations

## Installation

```console
composer require lucinda/internationalization
```

Requirements:

- PHP 8.1+
- `ext-SimpleXML`

## Runtime Flow

Create a `Wrapper` from XML, request parameters, and request headers:

```php
require __DIR__."/vendor/autoload.php";

$xml = simplexml_load_file("configuration.xml");
$headers = function_exists("getallheaders") ? getallheaders() : [];

$wrapper = new Lucinda\Internationalization\Wrapper($xml, $_GET, $headers);
$reader = $wrapper->getReader();

echo $reader->getTranslation("homepage.title");
```

`Wrapper` compiles the configured settings, detects a preferred locale, verifies that a supported locale folder exists, then exposes:

- `getReader()`: returns a `Reader` for translation lookup
- `getWriter()`: returns a `Writer` for editing the active locale's default domain file

## Configuration

The root XML must contain one `internationalization` tag:

```xml
<xml>
    <internationalization
        method="request"
        locale="en_US"
        folder="locale"
        domain="messages"
        extension="json"/>
</xml>
```

Attributes:

| Attribute | Required | Default | Description |
| --- | --- | --- | --- |
| `method` | yes | none | Locale detection method: `header`, `request`, or `session` |
| `locale` | yes | none | Default locale, such as `en_US` |
| `folder` | no | `locale` | Base folder containing locale subfolders |
| `domain` | no | `messages` | Default translation file name without extension |
| `extension` | no | `json` | Translation file extension |

Locale values must use `ll` or `ll_CC` format, for example `en`, `fr`, `en_US`, or `fr_FR`.

## Locale Detection

Supported detection methods are defined by `LocaleDetectionMethod`:

| Method | Source |
| --- | --- |
| `header` | `Accept-Language` request header |
| `request` | `locale` request parameter |
| `session` | `$_SESSION["locale"]`, overridden by `locale` request parameter |

Header detection parses quality weights and normalizes language tags:

```text
Accept-Language: fr-FR;q=0.5,en-us;q=0.9,en;q=0.3
```

The detected locale is `en_US`.

When using `method="session"`, a PHP session must already be started. After detection, the chosen supported locale is written back to `$_SESSION["locale"]`.

## Locale Fallbacks

After detection, the package checks supported locale folders in this order:

```text
preferred locale -> language-only locale -> default locale
```

For example, if the request asks for `fr_CA` and `folder="locale"`, lookup order is:

```text
locale/fr_CA
locale/fr
locale/en_US
```

`Wrapper` selects the first locale folder that exists. If none exist, it throws `ConfigurationException`.

`Reader` also merges translation files in fallback order for each domain, so missing keys in the preferred locale can still come from the default locale.

## Translation Files

Translations are stored as JSON objects:

```text
{folder}/{locale}/{domain}.{extension}
```

Example:

```text
locale/en_US/messages.json
locale/fr_FR/messages.json
locale/en_US/admin.json
```

JSON files must be objects with string keys and string values:

```json
{
    "homepage.title": "Welcome",
    "homepage.greeting": "Hello {name}",
    "cart.items.one": "{count} item",
    "cart.items.other": "{count} items"
}
```

Invalid JSON, lists, nested objects, numeric values, or other non-string values throw `TranslationInvalidException`.

## Reading Translations

Get one translation:

```php
$title = $reader->getTranslation("homepage.title");
```

Use a custom domain:

```php
$label = $reader->getTranslation("users.create", "admin");
```

If a key does not exist, the key itself is returned.

Interpolate placeholders:

```php
echo $reader->getTranslation("homepage.greeting", null, ["name"=>"Maria"]);
```

For this translation:

```json
{
    "homepage.greeting": "Hello {name}"
}
```

the output is:

```text
Hello Maria
```

Read plural forms:

```php
echo $reader->getPluralTranslation("cart.items", 1);
echo $reader->getPluralTranslation("cart.items", 3);
```

Plural lookup uses `.one` when `abs($count) == 1`, otherwise `.other`. The `count` placeholder is added automatically.

Check for a translation:

```php
if ($reader->hasTranslation("homepage.title")) {
    // key exists in the merged fallback translations
}
```

Read every translation in a domain:

```php
$translations = $reader->getTranslations();
$admin = $reader->getTranslations("admin");
```

## Writing Translations

`Writer` edits the active preferred locale and default domain configured in `Settings`.

```php
$writer = $wrapper->getWriter();
$writer->setTranslation("homepage.title", "Welcome");
$writer->setTranslation("homepage.greeting", "Hello {name}");
$writer->unsetTranslation("old.key");
$writer->save();
```

If the locale folder does not exist, `Writer` tries to create it. Files are saved as pretty-printed Unicode-safe JSON.

## Exceptions

| Exception | Meaning |
| --- | --- |
| `ConfigurationException` | XML is missing or invalid, locale is invalid, session mode is used without a started session, or no supported locale folder exists |
| `DomainNotFoundException` | No translation file exists for the requested domain across locale fallbacks |
| `TranslationInvalidException` | Translation JSON cannot be decoded or is not a string-to-string JSON object |
| `TranslationFileException` | Translation folders/files cannot be read, created, or written |

## Testing

This repository uses `lucinda/unit-testing`.

```console
php test.php
```

Tests live in `tests/`.

