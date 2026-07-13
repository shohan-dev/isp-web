<?php

use PHPUnit\Framework\TestCase;

/**
 * Item 4 — global IpbFilters auto-init contract.
 *
 * @internal
 */
final class ListFiltersAutoInitTest extends TestCase
{
    public function testListFiltersJsExposesAutoInit(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/saas/list-filters.js');

        $this->assertStringContainsString('autoInit', $js);
        $this->assertStringContainsString('init.dt.ipbFilters', $js);
        $this->assertStringContainsString('data-ipb-manual', $js);
        $this->assertStringContainsString('ipb-filter-select', $js);
    }

    public function testMainLayoutLoadsListFiltersAfterSaas(): void
    {
        $layout = (string) file_get_contents(APPPATH . 'Views/layout/main-layout.php');

        $saasPos = strpos($layout, 'saas.js');
        $filtersPos = strpos($layout, 'list-filters.js');
        $this->assertNotFalse($saasPos);
        $this->assertNotFalse($filtersPos);
        $this->assertLessThan($filtersPos, $saasPos);
    }
}
