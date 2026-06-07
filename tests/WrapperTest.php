<?php

namespace Test\Lucinda\Internationalization;

use Lucinda\Internationalization\Wrapper;
use Lucinda\UnitTest\Validator\Strings;

class WrapperTest
{
    private $object;

    public function __construct()
    {
        $folder = sys_get_temp_dir()."/lucinda_i18n_wrapper";
        $this->writeTranslations($folder, "en_US", ["hello"=>"Hello", "test"=>"test"]);
        $this->writeTranslations($folder, "fr", ["hello"=>"Salut"]);
        $this->object = new Wrapper(
            simplexml_load_string(
                '
<xml>
    <internationalization method="request" locale="en_US" folder="'.$folder.'"/>
</xml>
        '
            ),
            ["locale"=>"fr_CA"],
            []
        );
    }

    public function getReader()
    {
        return (new Strings($this->object->getReader()->getTranslation("hello")))->assertEquals("Salut");
    }


    public function getWriter()
    {
        $writer = $this->object->getWriter();
        $writer->setTranslation("test", "me");
        $writer->save();
        return (new Strings($this->object->getReader()->getTranslation("test")))->assertEquals("me");
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
