<?php

namespace Test\Lucinda\Internationalization;

use Lucinda\Internationalization\Settings;
use Lucinda\Internationalization\Reader;
use Lucinda\UnitTest\Validator\Arrays;
use Lucinda\UnitTest\Validator\Booleans;
use Lucinda\UnitTest\Validator\Strings;

class ReaderTest
{
    public function getTranslation()
    {
        $results = [];
        $folder = $this->createFixtures();
        $settings = new Settings(simplexml_load_string('<internationalization method="request" locale="en_US" folder="'.$folder.'"/>'));

        $settings->setPreferredLocale("fr_FR");
        $reader = new Reader($settings);
        $results[] = (new Strings($reader->getTranslation("hello", $settings->getDomain())))->assertEquals("Bonjour", "custom locale");
        $results[] = (new Strings($reader->getTranslation("bye", $settings->getDomain())))->assertEquals("Goodbye", "default fallback");
        $results[] = (new Strings($reader->getTranslation("greeting", null, ["name"=>"John"])))->assertEquals("Bonjour John", "interpolation");


        $settings->setPreferredLocale($settings->getDefaultLocale());
        $reader = new Reader($settings);
        $results[] = (new Strings($reader->getTranslation("hello", $settings->getDomain())))->assertEquals("Hello", "default locale");

        return $results;
    }

    public function getPluralTranslation()
    {
        $folder = $this->createFixtures();
        $settings = new Settings(simplexml_load_string('<internationalization method="request" locale="en_US" folder="'.$folder.'"/>'));
        $settings->setPreferredLocale("en_US");
        $reader = new Reader($settings);

        return [
            (new Strings($reader->getPluralTranslation("items", 1)))->assertEquals("1 item", "singular pluralization"),
            (new Strings($reader->getPluralTranslation("items", 2)))->assertEquals("2 items", "plural pluralization")
        ];
    }

    public function hasTranslation()
    {
        $folder = $this->createFixtures();
        $settings = new Settings(simplexml_load_string('<internationalization method="request" locale="en_US" folder="'.$folder.'"/>'));
        $settings->setPreferredLocale("fr_FR");
        $reader = new Reader($settings);

        return [
            (new Booleans($reader->hasTranslation("hello")))->assertTrue("existing translation"),
            (new Booleans($reader->hasTranslation("missing")))->assertFalse("missing translation")
        ];
    }

    public function getTranslations()
    {
        $folder = $this->createFixtures();
        $settings = new Settings(simplexml_load_string('<internationalization method="request" locale="en_US" folder="'.$folder.'"/>'));
        $settings->setPreferredLocale("fr_FR");
        $reader = new Reader($settings);
        $translations = $reader->getTranslations();

        return [
            (new Arrays($translations))->assertContainsKey("hello", "preferred translations include hello"),
            (new Strings($translations["hello"] ?? ""))->assertEquals("Bonjour", "preferred translations override default"),
            (new Arrays($translations))->assertContainsKey("bye", "default translations include bye"),
            (new Strings($translations["bye"] ?? ""))->assertEquals("Goodbye", "default translations are included")
        ];
    }

    private function createFixtures(): string
    {
        $folder = sys_get_temp_dir()."/lucinda_i18n_reader";
        $this->writeTranslations(
            $folder,
            "en_US",
            [
                "hello"=>"Hello",
                "bye"=>"Goodbye",
                "greeting"=>"Hello {name}",
                "items.one"=>"{count} item",
                "items.other"=>"{count} items"
            ]
        );
        $this->writeTranslations(
            $folder,
            "fr_FR",
            [
                "hello"=>"Bonjour",
                "greeting"=>"Bonjour {name}"
            ]
        );
        return $folder;
    }

    /**
     * @param array<string,string> $translations
     */
    private function writeTranslations(string $folder, string $locale, array $translations): void
    {
        $localeFolder = $folder.DIRECTORY_SEPARATOR.$locale;
        if (!file_exists($localeFolder)) {
            mkdir($localeFolder, 0755, true);
        }
        file_put_contents($localeFolder.DIRECTORY_SEPARATOR."messages.json", json_encode($translations));
    }
}
