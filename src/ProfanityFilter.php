<?php

namespace Waad\ProfanityFilter;

class ProfanityFilter
{
    protected array $config;

    protected ?string $language = null;

    protected bool $caseSensitive;

    protected bool $detectLeetSpeak;

    protected array $wordLists = [];

    public function __construct()
    {
        $this->config = config('profanity-filter');
        $this->caseSensitive = $this->config['case_sensitive'] ?? false;
        $this->detectLeetSpeak = $this->config['detect_leet_speak'] ?? true;
        $this->loadWordLists();
    }

    protected function loadWordLists(): void
    {
        $this->wordLists = [];
        foreach ($this->config['supported_languages'] as $lang) {
            $this->wordLists[$lang] = $this->loadWordsForLanguage($lang);
        }
    }

    protected function loadWordsForLanguage(string $lang): array
    {
        $words = config('profanity-words.'.$lang, []);
        if (! is_array($words)) {
            $words = [];
        }
        if (! empty($this->config['custom_words'][$lang])) {
            $words = array_merge($words, $this->config['custom_words'][$lang]);
        }

        return array_unique($words);
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;
        $this->loadWordLists();

        return $this;
    }

    public function setLanguage(string $lang): self
    {
        if (! in_array($lang, $this->config['supported_languages'], true)) {
            throw new \InvalidArgumentException("Language '{$lang}' is not supported.");
        }
        $this->language = $lang;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setCaseSensitive(bool $caseSensitive): self
    {
        $this->caseSensitive = $caseSensitive;

        return $this;
    }

    public function setDetectLeetSpeak(bool $detectLeetSpeak): self
    {
        $this->detectLeetSpeak = $detectLeetSpeak;

        return $this;
    }

    public function hasProfanity(string $text): bool
    {
        $langs = $this->language ? [$this->language => $this->wordLists[$this->language]] : $this->wordLists;
        $normalizedText = $this->detectLeetSpeak ? $this->normalizeLeetSpeak($text) : $text;

        foreach ($langs as $lang => $words) {
            $noBoundaries = $this->isLanguageMatchWithoutBoundaries($lang);
            foreach ($words as $word) {
                $pattern = $noBoundaries
                    ? '/'.preg_quote($word, '/').'/u'.($this->caseSensitive ? '' : 'i')
                    : ($this->caseSensitive
                        ? '/\b'.preg_quote($word, '/').'\b/u'
                        : '/\b'.preg_quote($word, '/').'\b/ui');
                if (preg_match($pattern, $normalizedText) || preg_match($pattern, $text)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function filter(string $text): string
    {
        $replacementChar = $this->config['replacement_character'] ?? '*';

        if (! $this->language) {
            $maxCount = 0;
            $filteredText = $text;
            foreach ($this->wordLists as $lang => $words) {
                $candidate = $this->filterWithLanguage($text, $lang, $this->detectLeetSpeak, $words);
                $count = mb_substr_count($candidate, $replacementChar);
                if ($count > $maxCount) {
                    $filteredText = $candidate;
                    $maxCount = $count;
                }
            }

            return $filteredText;
        }

        return $this->filterWithLanguage($text, $this->language, $this->detectLeetSpeak, $this->wordLists[$this->language]);
    }

    protected function filterWithLanguage(string $text, string $lang, bool $checkLeetSpeak, array $words): string
    {
        $replacementChar = $this->config['replacement_character'] ?? '*';
        $caseInsensitive = ! $this->caseSensitive;

        // If leet speak detection is disabled, do NOT use substitutions in pattern building
        $substitutions = ($checkLeetSpeak && ! empty($this->config['substitutions'])) ? $this->config['substitutions'] : [];

        foreach ($words as $word) {
            if ($this->isLanguageMatchWithoutBoundaries($lang)) {
                $pattern = $this->buildFlexiblePattern($word, $substitutions, false, $caseInsensitive);
                if (preg_match_all($pattern, $text, $matches)) {
                    foreach ($matches[0] as $found) {
                        $text = str_replace($found, $this->maskWord($found, $replacementChar), $text);
                    }
                }
            } else {
                $pattern = $this->buildFlexiblePattern($word, $substitutions, true, $caseInsensitive);
                if (preg_match_all($pattern, $text, $matches)) {
                    foreach ($matches[0] as $found) {
                        $text = str_replace($found, $this->maskWord($found, $replacementChar), $text);
                    }
                } else {
                    // Only use plain pattern if leet speak is off, so normalizedText is just $text
                    $plainPattern = $caseInsensitive
                        ? '/\b'.preg_quote($word, '/').'\b/ui'
                        : '/\b'.preg_quote($word, '/').'\b/u';
                    $normalizedText = $checkLeetSpeak ? $this->normalizeLeetSpeak($text) : $text;
                    if (preg_match_all($plainPattern, $normalizedText, $matches)) {
                        foreach ($matches[0] as $found) {
                            $replacePattern = '/\b'.preg_quote($found, '/').'\b/u'.($caseInsensitive ? 'i' : '');
                            $text = preg_replace($replacePattern, str_repeat($replacementChar, mb_strlen($found, 'UTF-8')), $text);
                        }
                    }
                }
                if ($caseInsensitive) {
                    $normWord = $this->normalizeAccents($word);
                    $normPattern = '/\b'.preg_quote($normWord, '/').'\b/ui';
                    if (preg_match_all($normPattern, $this->normalizeAccents($text), $matches, PREG_OFFSET_CAPTURE)) {
                        foreach ($matches[0] as $match) {
                            $matchPos = $match[1];
                            $matchLen = mb_strlen($match[0], 'UTF-8');
                            $wordsArr = preg_split('/\b/u', $text);
                            $curPos = 0;
                            foreach ($wordsArr as $origWord) {
                                $wLen = mb_strlen($origWord, 'UTF-8');
                                $nextPos = $curPos + $wLen;
                                if ($curPos <= $matchPos && $matchPos < $nextPos) {
                                    if (preg_match('/\p{L}/u', $origWord)) {
                                        $text = str_replace($origWord, str_repeat($replacementChar, $wLen), $text);
                                    }
                                    break;
                                }
                                $curPos = $nextPos;
                            }
                        }
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Build a flexible regex pattern for a word, optionally with leet/substitution support and word boundaries.
     */
    protected function buildFlexiblePattern(string $word, array $substitutions, bool $withBoundaries, bool $caseInsensitive): string
    {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        $pattern = '';
        foreach ($chars as $i => $char) {
            if (! empty($substitutions[$char])) {
                $subs = array_unique(array_merge([$char], $substitutions[$char]));
                $pattern .= '['.implode('', array_map(fn ($c) => preg_quote($c, '/'), $subs)).']';
            } else {
                $pattern .= preg_quote($char, '/');
            }
            if ($i < count($chars) - 1) {
                $pattern .= '[^\p{L}\p{N}]*';
            }
        }
        $regex = ($withBoundaries ? '/\b' : '/').$pattern.($withBoundaries ? '\b' : '').'/u'.($caseInsensitive ? 'i' : '');

        return $regex;
    }

    protected function maskWord(string $word, string $replacementChar): string
    {
        $masked = '';
        $len = mb_strlen($word, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($word, $i, 1, 'UTF-8');
            $masked .= preg_match('/\p{L}|\p{N}/u', $char) ? $replacementChar : $char;
        }

        return $masked;
    }

    protected function findOriginalWord(string $text, int $position, int $length): ?string
    {
        $words = preg_split('/\b/', $text);
        $curPos = 0;
        foreach ($words as $word) {
            $wLen = strlen($word);
            if ($curPos <= $position && $position < $curPos + $wLen) {
                return $word;
            }
            $curPos += $wLen;
        }

        return null;
    }

    protected function normalizeLeetSpeak(string $text): string
    {
        if (! empty($this->config['substitutions']) && is_array($this->config['substitutions'])) {
            foreach ($this->config['substitutions'] as $letter => $replacements) {
                $text = str_replace($replacements, $letter, $text);
            }
        }
        if (! empty($this->config['separators']) && is_array($this->config['separators'])) {
            $pattern = '/['.implode('', array_map(fn ($s) => preg_quote($s, '/'), $this->config['separators'])).']+/';
            $text = preg_replace($pattern, '', $text);
        } else {
            $text = preg_replace('/[\s\.\-_,;:!?]+/', ' ', $text);
        }

        return $text;
    }

    protected function normalizeAccents(string $text): string
    {
        static $from = ['é', 'è', 'ê', 'ë', 'à', 'ç', 'ù', 'û', 'ü', 'î', 'ï', 'ô', 'œ', 'Œ', 'œ', 'Œ'];
        static $to = ['e', 'e', 'e', 'e', 'a', 'c', 'u', 'u', 'u', 'i', 'i', 'o', 'oe', 'OE', 'oe', 'OE'];

        return str_replace($from, $to, $text);
    }

    public function getProfanityWords(string $text): array
    {
        $found = [];
        $langs = $this->language ? [$this->language => $this->wordLists[$this->language]] : $this->wordLists;
        foreach ($langs as $lang => $words) {
            $found = array_merge($found, $this->getProfanityWordsForLanguage($text, $lang, $this->detectLeetSpeak, $words));
        }

        return array_unique($found);
    }

    protected function getProfanityWordsForLanguage(string $text, string $lang, bool $checkLeetSpeak, array $words): array
    {
        $found = [];
        $normalizedText = $checkLeetSpeak ? $this->normalizeLeetSpeak($text) : $text;
        $noBoundaries = $this->isLanguageMatchWithoutBoundaries($lang);
        foreach ($words as $word) {
            $pattern = $noBoundaries
                ? '/'.preg_quote($word, '/').'/u'.($this->caseSensitive ? '' : 'i')
                : ($this->caseSensitive
                    ? '/\b'.preg_quote($word, '/').'\b/u'
                    : '/\b'.preg_quote($word, '/').'\b/ui');
            if (preg_match($pattern, $normalizedText)) {
                $found[] = $word;
            }
        }

        return $found;
    }

    private function isLanguageMatchWithoutBoundaries(string $lang): bool
    {
        return in_array($lang, $this->config['languages_match_without_boundaries'] ?? [], true);
    }
}
