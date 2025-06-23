<?php

namespace Waad\ProfanityFilter\Tests;

use Waad\ProfanityFilter\Facades\ProfanityFilter;

class FrenchProfanityFilterTest extends TestCase
{
    /** @test */
    public function test_detects_profanity_in_french()
    {
        ProfanityFilter::setLanguage('fr');
        $this->assertTrue(ProfanityFilter::hasProfanity("C'est un test merde"));
        $this->assertFalse(ProfanityFilter::hasProfanity("C'est un test propre"));
    }

    /** @test */
    public function test_filters_french_profanity()
    {
        ProfanityFilter::setLanguage('fr');
        $this->assertSame("C'est un test *****", ProfanityFilter::filter("C'est un test merde"));
    }

    /** @test */
    public function test_returns_french_profanity_words()
    {
        ProfanityFilter::setLanguage('fr');
        $words = ProfanityFilter::getProfanityWords("C'est un test merde putain");
        $this->assertIsArray($words);
        $this->assertContains('merde', $words);
        $this->assertContains('putain', $words);
        $this->assertGreaterThanOrEqual(2, count($words));
    }

    /** @test */
    public function test_uses_custom_french_words()
    {
        ProfanityFilter::setLanguage('fr');
        config(['profanity-filter.custom_words.fr' => ['personnalisé']]);
        ProfanityFilter::setConfig(config('profanity-filter'));

        $this->assertTrue(ProfanityFilter::hasProfanity("C'est un test personnalisé"));
        $this->assertSame("C'est un test ************", ProfanityFilter::filter("C'est un test personnalisé"));
        $this->assertSame("C'est un test ********-****", ProfanityFilter::filter("C'est un test personna-lisé"));
        $this->assertSame("C'est un test *.***.$-********", ProfanityFilter::filter("C'est un test p.e*r.$-onnalisé"));
    }

    /** @test */
    public function test_detects_french_profanity_with_separators()
    {
        ProfanityFilter::setLanguage('fr');
        $this->assertTrue(ProfanityFilter::hasProfanity("C'est un test m-e-r-d-e"));
        $this->assertTrue(ProfanityFilter::hasProfanity("C'est un test p.u.t.a.i.n"));

        $filtered = ProfanityFilter::filter("C'est un test m-e-r-d-e");
        $this->assertSame("C'est un test *-*-*-*-*", $filtered);
    }
}
