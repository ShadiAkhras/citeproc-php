<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style;

use PHPUnit\Framework\TestCase;
use Seboettg\CiteProc\TestSuiteTestCaseTrait;

/**
 * Class CitationTest
 * @package Seboettg\CiteProc\Style
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class CitationTest extends TestCase
{

    use TestSuiteTestCaseTrait;

    public function testDisambiguateAddNames()
    {
        $this->_testRenderTestSuite("disambiguate_AddNames");
    }

    public function testDisambiguateAllNames()
    {
        $this->_testRenderTestSuite("disambiguate_AllNames");
    }
}
