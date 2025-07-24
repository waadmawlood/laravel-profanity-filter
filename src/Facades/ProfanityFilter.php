<?php

namespace Waad\ProfanityFilter\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Waad\ProfanityFilter\ProfanityFilter setLanguage(string $lang)
 * @method static \Waad\ProfanityFilter\ProfanityFilter setConfig(array $config)
 * @method static \Waad\ProfanityFilter\ProfanityFilter setCaseSensitive(bool $caseSensitive)
 * @method static \Waad\ProfanityFilter\ProfanityFilter setDetectLeetSpeak(bool $detectLeetSpeak)
 * @method static ?string getLanguage()
 * @method static bool hasProfanity(string $text)
 * @method static string filter(string $text)
 * @method static array getProfanityWords(string $text)
 * @method static \Waad\ProfanityFilter\ProfanityFilter importWordsFromFile(string $filePath, string $lang)
 *
 * @see \Waad\ProfanityFilter\ProfanityFilter
 */
class ProfanityFilter extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'profanity-filter';
    }
}
