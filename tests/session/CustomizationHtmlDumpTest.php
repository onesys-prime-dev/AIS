<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CustomizationHtmlDumpTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testDumpCustomizationHtml(): void
    {
        auth()->loginById(18);
        session()->set('company_id', 1);

        $result = $this->get('settings/customization');
        $html = (string) $result->response()->getBody();

        file_put_contents('C:/temp/repro/customization.html', $html);

        $this->assertTrue(true);
    }
}
