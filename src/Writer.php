<?php

namespace Lucinda\Internationalization;

/**
 * Writes translations to JSON files located based on Settings info, each translation being a relationship between
 * an identifying key and a value that stores the translation itself
 */
final class Writer
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
     * @throws TranslationFileException
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
     * @throws TranslationFileException
     */
    private function readFile(Settings $settings): void
    {
        $this->file = $settings->getFolder().DIRECTORY_SEPARATOR.
            $settings->getPreferredLocale().DIRECTORY_SEPARATOR.
            $settings->getDomain().".".$settings->getExtension();

        if (file_exists($this->file)) {
            $contents = file_get_contents($this->file);
            if ($contents === false) {
                throw new TranslationFileException("Unable to read translation file: ".$this->file);
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
            $this->translations = $translations;
        } else {
            $folder = dirname($this->file);
            if (!file_exists($folder) && !mkdir($folder, 0755, true)) {
                throw new TranslationFileException("Unable to create translation folder: ".$folder);
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
     *
     * @throws TranslationFileException If translation file cannot be written.
     */
    public function save(): void
    {
        $json = json_encode($this->translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($this->file, $json, LOCK_EX) === false) {
            throw new TranslationFileException("Unable to write translation file: ".$this->file);
        }
    }
}
