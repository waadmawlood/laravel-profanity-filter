<?php

namespace Waad\ProfanityFilter\Tests;

use Waad\ProfanityFilter\Facades\ProfanityFilter;

class EnglishProfanityFilterTest extends TestCase
{
    /** @test */
    public function test_detects_profanity_in_english()
    {
        ProfanityFilter::setLanguage('en');
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a damn test'));
        $this->assertFalse(ProfanityFilter::hasProfanity('This is a clean test'));
    }

    /** @test */
    public function test_filters_profanity()
    {
        ProfanityFilter::setLanguage('en');
        $this->assertSame('This is a **** test', ProfanityFilter::filter('This is a damn test'));
    }

    /** @test */
    public function test_returns_profanity_words()
    {
        ProfanityFilter::setLanguage('en');
        $words = ProfanityFilter::getProfanityWords('This damn shit test');
        $this->assertIsArray($words);
        $this->assertContains('damn', $words);
        $this->assertContains('shit', $words);
        $this->assertGreaterThanOrEqual(2, count($words));
    }

    /** @test */
    public function test_respects_case_sensitivity_setting()
    {
        ProfanityFilter::setLanguage('en');

        ProfanityFilter::setCaseSensitive(true);
        $this->assertTrue(ProfanityFilter::hasProfanity('This is damn'));
        $this->assertFalse(ProfanityFilter::hasProfanity('This is DAMN'));

        ProfanityFilter::setCaseSensitive(false);
        $this->assertTrue(ProfanityFilter::hasProfanity('This is damn'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is DAMN'));
    }

    /** @test */
    public function test_uses_custom_words()
    {
        config(['profanity-filter.custom_words.en' => ['custom']]);
        ProfanityFilter::setConfig(config('profanity-filter'));
        ProfanityFilter::setLanguage('en');

        $this->assertTrue(ProfanityFilter::hasProfanity('This is a custom test'));
        $this->assertSame('This is a ****** test', ProfanityFilter::filter('This is a custom test'));
    }

    /** @test */
    public function test_detects_leet_speak()
    {
        ProfanityFilter::setLanguage('en');
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a d@mn test'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a sh1t test'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a f-u-c-k test'));

        $filtered = ProfanityFilter::filter('This is a d@mn test');
        // The filter may replace leet chars with *, so check for pattern
        $this->assertMatchesRegularExpression('/This is a .{4} test/', $filtered);
    }

    /** @test */
    public function test_can_disable_leet_speak_detection()
    {
        ProfanityFilter::setLanguage('en');
        // Disable leet speak detection
        ProfanityFilter::setDetectLeetSpeak(false);
        $this->assertFalse(ProfanityFilter::hasProfanity('This is a d@mn test'));
        $this->assertFalse(ProfanityFilter::hasProfanity('This is a sh1t test'));
    }

    /** @test */
    public function test_detects_profanity_with_separators()
    {
        ProfanityFilter::setLanguage('en');
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a d-a-m-n test'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a s.h.i.t test'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a f#u#c#k test'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a b*i*t*c*h test'));

        $filtered = ProfanityFilter::filter('This is a d-a-m-n test');
        // The filter should replace each letter with *, keeping separators
        $this->assertSame('This is a *-*-*-* test', $filtered);
    }
}
