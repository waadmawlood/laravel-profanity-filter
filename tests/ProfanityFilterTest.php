<?php

namespace Waad\ProfanityFilter\Tests;

use Waad\ProfanityFilter\Facades\ProfanityFilter;

class ProfanityFilterTest extends TestCase
{
    /** @test */
    public function test_detects_and_filters_profanity_in_multiple_languages()
    {
        // English
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a damn test'));
        $this->assertFalse(ProfanityFilter::hasProfanity('This is a clean test'));
        $this->assertEquals('This is a **** test', ProfanityFilter::filter('This is a damn test'));

        // French
        $this->assertTrue(ProfanityFilter::hasProfanity("C'est un test merde"));
        $this->assertFalse(ProfanityFilter::hasProfanity("C'est un test propre"));
        $this->assertEquals("C'est un test *****", ProfanityFilter::filter("C'est un test merde"));

        // Arabic
        $this->assertTrue(ProfanityFilter::hasProfanity('هذا اختبار كلب'));
        $this->assertFalse(ProfanityFilter::hasProfanity('هذا اختبار نظيف'));
        $this->assertEquals('هذا اختبار ***', ProfanityFilter::filter('هذا اختبار كلب'));
    }

    /** @test */
    public function test_gets_profanity_words()
    {
        $words = ProfanityFilter::getProfanityWords('This damn shit test');
        $this->assertEqualsCanonicalizing(['damn', 'shit'], $words);

        $words = ProfanityFilter::getProfanityWords("C'est un test merde putain");
        $this->assertEqualsCanonicalizing(['merde', 'putain'], $words);

        $arabicWords = ProfanityFilter::getProfanityWords('هذا اختبار كلب حمار');
        $this->assertEqualsCanonicalizing(['كلب', 'حمار'], $arabicWords);
    }

    /** @test */
    public function test_respects_case_sensitivity_setting_true()
    {
        ProfanityFilter::setCaseSensitive(true);
        $this->assertTrue(ProfanityFilter::hasProfanity('This is damn'));
        $this->assertFalse(ProfanityFilter::hasProfanity('This is DAMN'));
        $this->assertFalse(ProfanityFilter::hasProfanity('This is daMn'));
    }

    /** @test */
    public function test_respects_case_sensitivity_setting_false()
    {
        ProfanityFilter::setCaseSensitive(false);
        $this->assertTrue(ProfanityFilter::hasProfanity('This is damn'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is DAMN'));
        $this->assertTrue(ProfanityFilter::hasProfanity('This is daMn'));
    }

    /** @test */
    public function test_handles_custom_words_and_leet_speak()
    {
        config(['profanity-filter.custom_words.en' => ['custom']]);
        $this->assertTrue(ProfanityFilter::hasProfanity('This is a custom test'));
        $this->assertEquals('This is a ****** test', ProfanityFilter::filter('This is a custom test'));
        $this->assertEquals('This is a ****** test', ProfanityFilter::filter('This is a Custom test'));
        $this->assertEquals('This is a ***$*** test', ProfanityFilter::filter('This is a Cus$tom test'));

        // Leet speak detection
        $text = 'This is a d@mn test';
        ProfanityFilter::setDetectLeetSpeak(true);
        $this->assertTrue(ProfanityFilter::hasProfanity($text));
        $this->assertEquals('This is a *@** test', ProfanityFilter::filter($text));

        ProfanityFilter::setDetectLeetSpeak(false);
        $this->assertFalse(ProfanityFilter::hasProfanity($text));
        $this->assertEquals($text, ProfanityFilter::filter($text));

        $text = 'This is a 8reasts test';
        ProfanityFilter::setDetectLeetSpeak(true);
        $this->assertTrue(ProfanityFilter::hasProfanity($text));
        $this->assertEquals('This is a ******* test', ProfanityFilter::filter($text));

        ProfanityFilter::setDetectLeetSpeak(false);
        $this->assertFalse(ProfanityFilter::hasProfanity($text));
        $this->assertEquals($text, ProfanityFilter::filter($text));
    }

    /** @test */
    public function test_detects_profanity_with_separators_and_substitutions()
    {
        $string = 'This is a d-a-m-n test';
        $this->assertTrue(ProfanityFilter::hasProfanity($string));
        $this->assertEquals('This is a *-*-*-* test', ProfanityFilter::filter($string));

        $string = 'هذا اختبار ك-ل-ب';
        $this->assertTrue(ProfanityFilter::hasProfanity($string));
        $this->assertEquals('هذا اختبار *-*-*', ProfanityFilter::filter($string));

        // Character substitution (e.g. Persian ک for Arabic ك)
        $string = 'هذا اختبار کلب';
        $this->assertTrue(ProfanityFilter::hasProfanity($string));
        $this->assertEquals('هذا اختبار ***', ProfanityFilter::filter($string));
    }

    /** @test */
    public function test_try_all_methods()
    {
        $text = 'This is a test string with some profanity like f@ck and sh!t.';
        ProfanityFilter::setLanguage('en')
            ->setDetectLeetSpeak(true)
            ->setCaseSensitive(true);

        $this->assertTrue(ProfanityFilter::hasProfanity($text));
        $this->assertEquals('This is a test string with some profanity like *@** and **!*.', ProfanityFilter::filter($text));
    }
}
