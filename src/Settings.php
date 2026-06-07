<?php

namespace Lucinda\Internationalization;

/**
 * Encapsulates internationalization settings required in order to write to or read from translation files.
 */
final class Settings
{
    private const LOCALE_PATTERN = "/^[a-z]{2}(_[A-Z]{2})?$/";

    private string $domain = "messages";
    private string $folder = "locale";
    private string $extension = "json";

    private string $preferredLocale;
    private string $defaultLocale;
    private LocaleDetectionMethod $detectionMethod;

    /**
     * Saves preferred and default locales.
     *
     * @param  \SimpleXMLElement $xml
     * @throws ConfigurationException
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->setLocalizationMethod($xml);
        $this->setDefaultLocale($xml);
        $this->setDomain($xml);
        $this->setFolder($xml);
        $this->setExtension($xml);
    }

    /**
     * Sets localization method based on "method" attribute of <internationalization> tag.
     *
     * @param  \SimpleXMLElement $xml Internationalization tag content.
     * @throws ConfigurationException If XML is improperly configured.
     */
    private function setLocalizationMethod(\SimpleXMLElement $xml): void
    {
        $detectionMethod = (string) $xml["method"];
        if (!$detectionMethod) {
            throw new ConfigurationException("Attribute 'method' is mandatory for 'internationalization' tag");
        }
        $detectionMethod = strtolower($detectionMethod);
        if ($case = LocaleDetectionMethod::tryFrom($detectionMethod)) {
            $this->detectionMethod = $case;
        } else {
            throw new ConfigurationException("Invalid detection method: ".$detectionMethod);
        }
    }

    /**
     * Gets localization method
     *
     * @return LocaleDetectionMethod
     */
    public function getLocalizationMethod(): LocaleDetectionMethod
    {
        return $this->detectionMethod;
    }

    /**
     * Sets default locale based on "locale" attribute of <internationalization> tag.
     *
     * @param  \SimpleXMLElement $xml Internationalization tag content.
     * @throws ConfigurationException If XML is improperly configured.
     */
    private function setDefaultLocale(\SimpleXMLElement $xml): void
    {
        $defaultLocale =  (string) $xml["locale"];
        if (!$defaultLocale) {
            throw new ConfigurationException("Attribute 'locale' is mandatory for 'internationalization' tag");
        }
        $this->validateLocale($defaultLocale);
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Gets default locale to be used when translating
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Sets domain based on "domain" attribute of <internationalization> tag.
     *
     * @param \SimpleXMLElement $xml
     */
    private function setDomain(\SimpleXMLElement $xml): void
    {
        $domain = (string) $xml["domain"];
        if ($domain) {
            $this->domain = $domain;
        }
    }

    /**
     * Gets name  of translation file (by default: messages)
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Sets folder in which translations are located based on "folder" attribute of <internationalization> tag.
     *
     * @param \SimpleXMLElement $xml
     */
    private function setFolder(\SimpleXMLElement $xml): void
    {
        $folder = (string) $xml["folder"];
        if ($folder) {
            $this->folder = $folder;
        }
    }

    /**
     * Gets folder in which translations are located (by default: locale)
     *
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * Sets extension of translation files based on "extension" attribute of <internationalization> tag.
     *
     * @param \SimpleXMLElement $xml
     */
    private function setExtension(\SimpleXMLElement $xml): void
    {
        $extension = (string) $xml["extension"];
        if ($extension) {
            $this->extension = $extension;
        }
    }

    /**
     * Gets extension of translation file (aka "domain")
     *
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Sets preferred locale to be used when translating
     *
     * @param string $locale Country and language ISO codes (2) concatenated by _
     * @throws ConfigurationException If locale is invalid.
     */
    public function setPreferredLocale(string $locale): void
    {
        $this->validateLocale($locale);
        $this->preferredLocale = $locale;
    }

    /**
     * Gets preferred locale to be used when translating
     *
     * @return string
     */
    public function getPreferredLocale(): string
    {
        return $this->preferredLocale;
    }

    /**
     * Gets fallback locales in lookup order.
     *
     * @return string[]
     */
    public function getLocaleFallbacks(): array
    {
        $locales = [$this->preferredLocale];
        if (str_contains($this->preferredLocale, "_")) {
            $locales[] = substr($this->preferredLocale, 0, 2);
        }
        $locales[] = $this->defaultLocale;

        return array_values(array_unique($locales));
    }

    /**
     * Checks locale format.
     *
     * @param  string $locale
     * @throws ConfigurationException If locale is invalid.
     */
    private function validateLocale(string $locale): void
    {
        if (!preg_match(self::LOCALE_PATTERN, $locale)) {
            throw new ConfigurationException("Invalid locale: ".$locale);
        }
    }
}
