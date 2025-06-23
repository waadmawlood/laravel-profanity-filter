<?php

namespace Waad\ProfanityFilter\Tests;

use Waad\ProfanityFilter\Facades\ProfanityFilter;

class ArabicProfanityFilterTest extends TestCase
{
    /** @test */
    public function test_detects_profanity_in_arabic()
    {
        ProfanityFilter::setLanguage('ar');
        $this->assertTrue(ProfanityFilter::hasProfanity('هذا اختبار كلب'));
        $this->assertFalse(ProfanityFilter::hasProfanity('هذا اختبار نظيف'));
        $this->assertTrue(ProfanityFilter::hasProfanity('هذا اختبار سكس'));
        $this->assertTrue(ProfanityFilter::hasProfanity('هذا اختبار سكسي'));
    }

    /** @test */
    public function test_filters_arabic_profanity()
    {
        ProfanityFilter::setLanguage('ar');
        $this->assertSame('هذا اختبار ***', ProfanityFilter::filter('هذا اختبار كلب'));
        $this->assertSame('هذا اختبار *****ة', ProfanityFilter::filter('هذا اختبار شرموطة'));
    }

    /** @test */
    public function test_returns_arabic_profanity_words()
    {
        ProfanityFilter::setLanguage('ar');
        $words = ProfanityFilter::getProfanityWords('هذا اختبار كلب حمار');
        $this->assertIsArray($words);
        $this->assertContains('كلب', $words);
        $this->assertContains('حمار', $words);
        $this->assertGreaterThanOrEqual(2, count($words));
    }

    /** @test */
    public function test_uses_custom_arabic_words()
    {
        ProfanityFilter::setLanguage('ar');
        config(['profanity-filter.custom_words.ar' => ['مخصص']]);
        ProfanityFilter::setConfig(config('profanity-filter'));

        $this->assertTrue(ProfanityFilter::hasProfanity('هذا اختبار مخصص'));
        $this->assertSame('هذا اختبار ****', ProfanityFilter::filter('هذا اختبار مخصص'));
    }

    /** @test */
    public function test_detects_arabic_profanity_with_separators_and_substitutions()
    {
        ProfanityFilter::setLanguage('ar');
        $variants = [
            'هذا اختبار ك-ل-ب',
            'هذا اختبار ك.ل.ب',
            'هذا اختبار ك..ل.ب',
            'هذا اختبار کلب', // Persian ک
        ];

        foreach ($variants as $variant) {
            $this->assertTrue(
                ProfanityFilter::hasProfanity($variant),
                "Failed asserting profanity detected for: $variant"
            );
        }

        $this->assertSame('هذا اختبار *-*-*', ProfanityFilter::filter('هذا اختبار ك-ل-ب'));
    }
}
