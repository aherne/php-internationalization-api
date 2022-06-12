<?php

namespace Lucinda\Internationalization;

/**
 * Writes translations to JSON files located based on Settings info, each translation being a relationship between
 * an identifying key and a value that stores the translation itself
 */
class Writer
{
    /**
     * @var array<string,string>
     */
    private array $translations = array();
    private string $file;

    /**
     * Sets up writer based on user-defined internationalization settings.
     *
     * @param  Settings $settings
     * @throws TranslationInvalidException
     */
    public function __construct(Settings $settings)
    {
        $this->readFile($settings);
    }

    /**
     * Gets existing translations from JSON file located based on Settings info. Creates folder that will store
     * translations, if former doesn't exist.
     *
     * @param  Settings $settings
     * @throws TranslationInvalidException
     */
    private function readFile(Settings $settings): void
    {
        $this->file = $settings->getFolder().DIRECTORY_SEPARATOR.
            $settings->getPreferredLocale().DIRECTORY_SEPARATOR.
            $settings->getDomain().".".$settings->getExtension();

        if (file_exists($this->file)) {
            $translations = json_decode(file_get_contents($this->file), true);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new TranslationInvalidException(json_last_error_msg());
            }
            $this->translations = $translations;
        } else {
            $folder = dirname($this->file);
            if (!file_exists($folder)) {
                mkdir(dirname($this->file), 0755, true);
            }
        }
    }

    /**
     * Adds or edits a translation
     *
     * @param string $key   Locale unspecific unique identifier of translated text.
     * @param string $value Body of translation itself.
     */
    public function setTranslation(string $key, string $value): void
    {
        $this->translations[$key] = $value;
    }

    /**
     * Removes a translation
     *
     * @param string $key Locale unspecific unique identifier of translated text.
     */
    public function unsetTranslation(string $key): void
    {
        unset($this->translations[$key]);
    }

    /**
     * Persists changes to translation file.
     */
    public function save(): void
    {
        file_put_contents($this->file, json_encode($this->translations));
    }
}
