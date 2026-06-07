<?php

namespace Test\Lucinda\Internationalization;

use Lucinda\Internationalization\LocaleDetectionMethod;
use Lucinda\Internationalization\Settings;
use Lucinda\UnitTest\Validator\Arrays;
use Lucinda\UnitTest\Validator\Booleans;
use Lucinda\UnitTest\Validator\Strings;

class SettingsTest
{
    private $object;

    public function __construct()
    {
        $this->object = new Settings(
            \simplexml_load_string(
                '
        <internationalization method="header" locale="en_US" domain="messages" folder="locales" extension="json"/>
        '
            )
        );
    }

    public function getLocalizationMethod()
    {
        return (new Booleans($this->object->getLocalizationMethod() == LocaleDetectionMethod::HEADER))->assertTrue();
    }


    public function getDefaultLocale()
    {
        return (new Strings($this->object->getDefaultLocale()))->assertEquals("en_US");
    }


    public function getDomain()
    {
        return (new Strings($this->object->getDomain()))->assertEquals("messages");
    }


    public function getFolder()
    {
        return (new Strings($this->object->getFolder()))->assertEquals("locales");
    }


    public function getExtension()
    {
        return (new Strings($this->object->getExtension()))->assertEquals("json");
    }


    public function setPreferredLocale()
    {
        $this->object->setPreferredLocale("fr_FR");
        return (new Booleans(true))->assertTrue();
    }


    public function getPreferredLocale()
    {
        return (new Strings($this->object->getPreferredLocale()))->assertEquals("fr_FR");
    }

    public function getLocaleFallbacks()
    {
        $this->object->setPreferredLocale("fr_CA");
        return (new Arrays($this->object->getLocaleFallbacks()))->assertEquals(["fr_CA", "fr", "en_US"]);
    }
}
