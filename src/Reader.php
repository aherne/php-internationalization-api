<?php

namespace Lucinda\Internationalization;

/**
 * Reads translations from JSON files located based on Settings info, each translation being a relationship between
 * an identifying key and a value that stores the translation itself
 */
class Reader
{
    private Settings $settings;
    /**
     * @var array<string,array<string,string>>
     */
    private array $translations = array();

    /**
     * Injects client-specific internationalization settings to use in finding translations later on.
     *
     * @param Settings $settings Holds user-defined internationalization settings.
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Reads all translations from domain file.
     *
     * @param  string|null $domain Translation type (eg: house) reflecting into a file on disk.
     * @throws DomainNotFoundException If no translation file was found
     * @throws TranslationInvalidException If translation file found is not convertible to JSON
     */
    private function setTranslations(?string $domain = null): void
    {
        $fileName = $this->getFileName($this->settings->getPreferredLocale(), $domain);
        if (!file_exists($fileName)) {
            $fileName = $this->getFileName($this->settings->getDefaultLocale(), $domain);
            if (!file_exists($fileName)) {
                throw new DomainNotFoundException($domain);
            }
        }
        $translations = json_decode(file_get_contents($fileName), true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new TranslationInvalidException(json_last_error_msg());
        }
        $this->translations[$domain] = $translations;
    }

    /**
     * Gets absolute location of file containing translations by domain and locale
     *
     * @param  string $locale
     * @param  string $domain
     * @return string
     */
    private function getFileName(string $locale, string $domain): string
    {
        return $this->settings->getFolder().DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.
            $domain.".".$this->settings->getExtension();
    }

    /**
     * Gets a single translation from domain file.
     *
     * @param  string      $key    Keyword by which translated value is accessible.
     * @param  string|null $domain Translation type (eg: house) reflecting into a file on disk. If not supplied, default domain is used.
     * @throws DomainNotFoundException If no translation file was found
     * @throws TranslationInvalidException If translation file found is not convertible to JSON
     * @return string
     */
    public function getTranslation(string $key, ?string $domain=null): string
    {
        if (!$domain) {
            $domain = $this->settings->getDomain();
        }
        if (!isset($this->translations[$domain])) {
            $this->setTranslations($domain);
        }
        return ($this->translations[$domain][$key] ?? $key);
    }
}
