<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake([
            'https://finans.truncgil.com/*' => Http::response([
                'USD'        => ['Satış' => '34.50', 'Değişim' => '+0.2%'],
                'EUR'        => ['Satış' => '37.40', 'Değişim' => '+0.1%'],
                'gram-altin' => ['Satış' => '2.980', 'Değişim' => '-0.1%'],
                'XU100'      => ['Satış' => '9.920', 'Değişim' => '+0.8%'],
            ], 200),
        ]);
    }
}
