<?php

namespace Lucinda\Internationalization;

/**
 * Detects locale based on contents of internationalization tag:
 *
 * <internationalization locale="en_US" method="session"/>
 */
final class LocaleDetector
{
    public const PARAMETER_NAME = "locale";
    private ?string $detectedLocale = null;

    /**
     * Determines method to detect locale then performs locale detection by matching XML with client request/headers
     *
     * @param array<string,string>  $requestParameters
     * @param array<string,string>  $sessionParameters
     * @param array<string,string>  $requestHeaders
     * @param LocaleDetectionMethod $detectionMethod
     */
    public function __construct(
        array $requestParameters,
        array $sessionParameters,
        array $requestHeaders,
        LocaleDetectionMethod $detectionMethod
    ) {
        $this->setDetectedLocale($requestParameters, $sessionParameters, $requestHeaders, $detectionMethod);
    }

    /**
     * Sets detected locale based on detection method, client request as well as <session> XML tag (if detection
     * method is session)
     *
     * @param array<string,string>  $requestParameters
     * @param array<string,string>  $sessionParameters
     * @param array<string,string>  $requestHeaders
     * @param LocaleDetectionMethod $detectionMethod
     */
    private function setDetectedLocale(
        array $requestParameters,
        array $sessionParameters,
        array $requestHeaders,
        LocaleDetectionMethod $detectionMethod
    ): void {
        switch ($detectionMethod) {
        case LocaleDetectionMethod::HEADER:
            $this->detectedLocale = $this->detectByHeaders($requestHeaders);
            break;
        case LocaleDetectionMethod::REQUEST:
            $this->detectedLocale = $this->detectByRequestParameters($requestParameters);
            break;
        case LocaleDetectionMethod::SESSION:
            $this->detectedLocale = $this->detectBySessionParameters($sessionParameters, $requestParameters);
            break;
        }
    }

    /**
     * Detects locale by Accept-Language HTTP request header
     *
     * @param  array<string,string> $requestHeaders
     * @return string|null
     */
    private function detectByHeaders(array $requestHeaders): ?string
    {
        $headers = array_change_key_case($requestHeaders, CASE_LOWER);
        if (!isset($headers["accept-language"])) {
            return null;
        }

        $locales = [];
        foreach (explode(",", $headers["accept-language"]) as $position => $language) {
            $parts = array_map("trim", explode(";", $language));
            $locale = $this->normalizeLocale($parts[0]);
            if ($locale === null) {
                continue;
            }

            $quality = 1.0;
            if (isset($parts[1]) && preg_match("/^q=([0-9.]+)$/", $parts[1], $matches)) {
                $quality = (float) $matches[1];
            }
            $locales[] = ["locale"=>$locale, "quality"=>$quality, "position"=>$position];
        }

        if (!$locales) {
            return null;
        }

        usort(
            $locales,
            fn (array $first, array $second): int => $second["quality"] <=> $first["quality"] ?: $first["position"] <=> $second["position"]
        );

        return $locales[0]["locale"];
    }

    /**
     * Detects locale by value of GET request parameter "locale"
     *
     * @param  array<string,string> $requestParameters
     * @return string|null
     */
    private function detectByRequestParameters(array $requestParameters): ?string
    {
        if (isset($requestParameters[self::PARAMETER_NAME])) {
            return $this->normalizeLocale($requestParameters[self::PARAMETER_NAME]);
        }
        return null;
    }

    /**
     * Detects locale by value of SESSION & GET request parameter "locale"
     *
     * @param  array<string,string> $sessionParameters
     * @param  array<string,string> $requestParameters
     * @return string|null
     */
    private function detectBySessionParameters(array $sessionParameters, array $requestParameters): ?string
    {
        $detectedLocale = null;
        if (isset($sessionParameters[self::PARAMETER_NAME])) {
            $detectedLocale = $this->normalizeLocale($sessionParameters[self::PARAMETER_NAME]);
        }
        if (isset($requestParameters[self::PARAMETER_NAME])) {
            $detectedLocale = $this->normalizeLocale($requestParameters[self::PARAMETER_NAME]);
        }
        return $detectedLocale;
    }

    /**
     * Normalizes locale tags to ll or ll_CC.
     *
     * @param  string $locale
     * @return string|null
     */
    private function normalizeLocale(string $locale): ?string
    {
        $locale = str_replace("-", "_", trim($locale));
        if (preg_match("/^([a-z]{2})(?:_([a-z]{2}))?$/i", $locale, $matches)) {
            return strtolower($matches[1]).(!empty($matches[2]) ? "_".strtoupper($matches[2]) : "");
        }
        return null;
    }

    /**
     * Gets detected locale
     *
     * @return string|NULL
     */
    public function getDetectedLocale(): ?string
    {
        return $this->detectedLocale;
    }
}
