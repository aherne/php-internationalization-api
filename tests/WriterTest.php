<?php

namespace Test\Lucinda\Internationalization;

use Lucinda\Internationalization\Writer;
use Lucinda\Internationalization\Settings;
use Lucinda\UnitTest\Validator\Arrays;
use Lucinda\UnitTest\Validator\Strings;

class WriterTest
{
    private $settings;

    public function __construct()
    {
        $folder = sys_get_temp_dir()."/lucinda_i18n_writer";
        $localeFolder = $folder.DIRECTORY_SEPARATOR."en_US";
        if (!file_exists($localeFolder)) {
            mkdir($localeFolder, 0755, true);
        }
        file_put_contents($localeFolder.DIRECTORY_SEPARATOR."messages.json", json_encode(["hello"=>"Hello"]));

        $settings = new Settings(simplexml_load_string('<internationalization method="request" locale="en_US" folder="'.$folder.'"/>'));
        $settings->setPreferredLocale($settings->getDefaultLocale());
        $this->settings = $settings;
    }

    public function setTranslation()
    {
        $writer = new Writer($this->settings);
        $writer->setTranslation("test", "me");
        $writer->save();

        $contents = json_decode(file_get_contents($this->getFile()), true);
        return [
            (new Arrays($contents))->assertContainsKey("test"),
            (new Strings($contents["test"] ?? ""))->assertEquals("me")
        ];
    }


    public function unsetTranslation()
    {
        $writer = new Writer($this->settings);
        $writer->unsetTranslation("test");
        $writer->save();

        $contents = json_decode(file_get_contents($this->getFile()), true);
        return (new Arrays($contents))->assertNotContainsKey("test");
    }


    public function save()
    {
        return $this->setTranslation();
    }

    private function getFile(): string
    {
        return $this->settings->getFolder().DIRECTORY_SEPARATOR.
            $this->settings->getPreferredLocale().DIRECTORY_SEPARATOR.
            $this->settings->getDomain().".".$this->settings->getExtension();
    }
}
