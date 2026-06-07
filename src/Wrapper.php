<?php

namespace Lucinda\Internationalization;

/**
 * Reads internationalization tag in order to detect locale settings to be used by reader/writer
 */
final class Wrapper
{
    private Settings $settings;

    /**
     * Sets up Reader instance to use later on in automatic translation based on XML and client headers
     *
     * @param  \SimpleXMLElement    $xml               XML root containing 'internationalization' tag
     * @param  array<string,string> $requestParameters
     * @param  array<string,string> $requestHeaders
     * @throws ConfigurationException If XML is improperly configured or session is not started
     */
    public function __construct(\SimpleXMLElement $xml, array $requestParameters, array $requestHeaders)
    {
        // parses XML
        $xml = $xml->internationalization;
        if (empty($xml)) {
            throw new ConfigurationException("Tag 'internationalization' missing");
        }

        // identifies settings based on XML and sets preferred locale
        $settings = new Settings($xml);
        if ($settings->getLocalizationMethod()==LocaleDetectionMethod::SESSION && session_id() == "") {
            throw new ConfigurationException("Session must be already started!");
        }

        // sets preferred locale
        $this->setPreferredLocale(
            $settings,
            $requestParameters,
            $_SESSION ?? [],
            $requestHeaders,
        );

        // saves locale in session
        if ($settings->getLocalizationMethod() == LocaleDetectionMethod::SESSION) {
            $_SESSION[LocaleDetector::PARAMETER_NAME] = $settings->getPreferredLocale();
        }

        // saves settings to be used by Writer
        $this->settings = $settings;
    }

    /**
     * Sets preferred internationalization locale
     *
     * @param  Settings             $settings
     * @param  array<string,string> $requestParameters
     * @param  array<string,string> $sessionParameters
     * @param  array<string,string> $requestHeaders
     * @return void
     * @throws ConfigurationException
     */
    private function setPreferredLocale(
        Settings $settings,
        array $requestParameters,
        array $sessionParameters,
        array $requestHeaders
    ): void {
        $localeDetector = new LocaleDetector(
            $requestParameters,
            $sessionParameters,
            $requestHeaders,
            $settings->getLocalizationMethod()
        );
        $preferredLocale = $localeDetector->getDetectedLocale();
        $settings->setPreferredLocale($preferredLocale!==null ? $preferredLocale : $settings->getDefaultLocale());

        foreach ($settings->getLocaleFallbacks() as $locale) {
            if (file_exists($settings->getFolder().DIRECTORY_SEPARATOR.$locale)) {
                $settings->setPreferredLocale($locale);
                return;
            }
        }

        throw new ConfigurationException("Translations not set for default locale");
    }

    /**
     * Gets translations reader instance
     *
     * @return Reader
     */
    public function getReader(): Reader
    {
        return new Reader($this->settings);
    }

    /**
     * Gets translations writer instance
     *
     * @return Writer
     * @throws TranslationInvalidException
     */
    public function getWriter(): Writer
    {
        return new Writer($this->settings);
    }
}
