<?php

namespace Lucinda\Internationalization;

/**
 * Reads translations from JSON files located based on Settings info, each translation being a relationship between
 * an identifying key and a value that stores the translation itself
 */
final class Reader
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
     * @param  string $domain Translation type (eg: house) reflecting into a file on disk.
     * @throws DomainNotFoundException If no translation file was found
     * @throws TranslationInvalidException If translation file found is not convertible to JSON
     */
    private function setTranslations(string $domain): void
    {
        $translations = [];
        $found = false;
        $locales = array_reverse($this->settings->getLocaleFallbacks());
        foreach ($locales as $locale) {
            $fileName = $this->getFileName($locale, $domain);
            if (!file_exists($fileName)) {
                continue;
            }
            $found = true;
            $translations = array_merge($translations, $this->readFile($fileName));
        }

        if (!$found) {
            throw new DomainNotFoundException($domain);
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
     * Reads and validates a translation file.
     *
     * @param  string $fileName
     * @return array<string,string>
     * @throws TranslationInvalidException If translation file found is not convertible to a translation map.
     */
    private function readFile(string $fileName): array
    {
        $contents = file_get_contents($fileName);
        if ($contents === false) {
            throw new TranslationInvalidException("Unable to read translation file: ".$fileName);
        }
        $translations = json_decode($contents);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new TranslationInvalidException(json_last_error_msg());
        }
        if (!is_object($translations)) {
            throw new TranslationInvalidException("Translation file must contain a JSON object");
        }
        $translations = get_object_vars($translations);
        foreach ($translations as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new TranslationInvalidException("Translation file must contain string keys and string values");
            }
        }
        return $translations;
    }

    /**
     * Gets a single translation from domain file.
     *
     * @param  string      $key    Keyword by which translated value is accessible.
     * @param  string|null          $domain     Translation type (eg: house) reflecting into a file on disk. If not supplied, default domain is used.
     * @param  array<string,string> $parameters Values used to replace {placeholders}.
     * @throws DomainNotFoundException If no translation file was found
     * @throws TranslationInvalidException If translation file found is not convertible to JSON
     * @return string
     */
    public function getTranslation(string $key, ?string $domain=null, array $parameters=[]): string
    {
        $domain = $this->getDomain($domain);
        $translation = ($this->getTranslations($domain)[$key] ?? $key);
        return $this->interpolate($translation, $parameters);
    }

    /**
     * Gets plural translation based on count.
     *
     * @param  string               $key
     * @param  integer              $count
     * @param  string|null          $domain
     * @param  array<string,string> $parameters
     * @throws DomainNotFoundException If no translation file was found
     * @throws TranslationInvalidException If translation file found is not convertible to JSON
     * @return string
     */
    public function getPluralTranslation(string $key, int $count, ?string $domain=null, array $parameters=[]): string
    {
        $parameters["count"] = (string) $count;
        $suffix = abs($count) == 1 ? "one" : "other";
        return $this->getTranslation($key.".".$suffix, $domain, $parameters);
    }

    /**
     * Checks if translation exists.
     *
     * @param  string      $key
     * @param  string|null $domain
     * @throws DomainNotFoundException If no translation file was found
     * @throws TranslationInvalidException If translation file found is not convertible to JSON
     * @return bool
     */
    public function hasTranslation(string $key, ?string $domain=null): bool
    {
        return array_key_exists($key, $this->getTranslations($domain));
    }

    /**
     * Gets all translations for a domain.
     *
     * @param  string|null $domain
     * @throws DomainNotFoundException If no translation file was found
     * @throws TranslationInvalidException If translation file found is not convertible to JSON
     * @return array<string,string>
     */
    public function getTranslations(?string $domain=null): array
    {
        $domain = $this->getDomain($domain);
        if (!isset($this->translations[$domain])) {
            $this->setTranslations($domain);
        }
        return $this->translations[$domain];
    }

    /**
     * Gets explicit domain or default domain.
     *
     * @param  string|null $domain
     * @return string
     */
    private function getDomain(?string $domain): string
    {
        return ($domain ?: $this->settings->getDomain());
    }

    /**
     * Replaces placeholders in translation body.
     *
     * @param  string               $translation
     * @param  array<string,string> $parameters
     * @return string
     */
    private function interpolate(string $translation, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $translation = str_replace("{".$key."}", $value, $translation);
        }
        return $translation;
    }
}
