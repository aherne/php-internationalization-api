<?php

namespace Test\Lucinda\Internationalization;

use Lucinda\Internationalization\LocaleDetectionMethod;
use Lucinda\Internationalization\LocaleDetector;
use Lucinda\UnitTest\Validator\Strings;

class LocaleDetectorTest
{
    public function getDetectedLocale()
    {
        $results = [];

        $detector = new LocaleDetector([], [], ["Accept-Language"=>"fr-FR;q=0.5,en-us;q=0.9,en;q=0.3"], LocaleDetectionMethod::HEADER);
        $results[] = (new Strings($detector->getDetectedLocale()))->assertEquals("en_US", "header-based detection");

        $detector = new LocaleDetector(["locale"=>"en-us"], [], [], LocaleDetectionMethod::REQUEST);
        $results[] = (new Strings($detector->getDetectedLocale()))->assertEquals("en_US", "request-based detection");

        session_start();
        $detector = new LocaleDetector([], ["locale"=>"en-us"], [], LocaleDetectionMethod::SESSION);
        $results[] = (new Strings($detector->getDetectedLocale()))->assertEquals("en_US", "session-based detection");
        session_destroy();

        return $results;
    }
}
