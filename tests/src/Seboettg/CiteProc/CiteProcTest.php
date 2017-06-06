<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc;

use PHPUnit\Framework\TestCase;


class CiteProcTest extends TestCase
{

    use TestSuiteTestCaseTrait;

    /**
     * @var array
     */
    private $dataObj;

    /**
     * @var CiteProc
     */
    private $citeProc;

    public function setUp()
    {
        //parent::setU
    }

    public function testFullStyleBibliography1()
    {
        $this->_testRenderTestSuite("fullstyles_APABibliography");
    }

    public function testFullStyleMultipleAuthors()
    {
        $this->_testRenderTestSuite("fullstyles_APA_MultipleAuthors");
    }

    public function testFullStyleDINBibliography()
    {
        $this->_testRenderTestSuite("fullstyles_DINBibliography");
    }

    public function testFullStyleInternationalJournalOfHumanoidRobotics()
    {
        $this->_testRenderTestSuite("fullstyles_InternationalJournalOfHumanoid");
    }

    public function testFullStyleNorthWestUniversityHarvard()
    {
        $this->_testRenderTestSuite("fullstyles_NorthWestUniversityHarvard");
    }

    public function testRenderCitationNumber()
    {
        $this->_testRenderTestSuite("text_renderCitationNumber");
    }

    public function testRenderCitationNumberResultAsArray()
    {
        $style = StyleSheet::loadStyleSheet("elsevier-vancouver");
        $citeProc = new CiteProc($style);
        $result = $citeProc->render(json_decode("
        [
            {
                \"id\": \"ITEM-1\",
                \"title\": \"Book 1\",
                \"type\": \"book\"
            },
            {
                \"id\": \"ITEM-2\",
                \"title\": \"Book 2\",
                \"type\": \"book\"
            },
            {
                \"id\": \"ITEM-3\",
                \"title\": \"Book 3\",
                \"type\": \"book\"
            }
        ]"), "citation", json_decode("
        [
            [
                {
                    \"id\": \"ITEM-1\"
                }, 
                {
                    \"id\": \"ITEM-3\"
                }
            ],
            [
                {
                    \"id\": \"ITEM-2\"
                }
            ]
        ]"), true);

        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals("[1,3]", $result[0]);
        $this->assertEquals("[2]", $result[1]);

    }

    public function testRenderCssStyle()
    {
        $style = StyleSheet::loadStyleSheet("international-journal-of-humanoid-robotics");
        $citeProc = new CiteProc($style);
        $cssStyles = $citeProc->renderCssStyles();

        $this->assertTrue(strpos($cssStyles, "csl-left-margin") !== false);
        $this->assertTrue(strpos($cssStyles, "csl-right-inline") !== false);
    }

    public function testRenderCssStyleHangingIndent()
    {
        $style = StyleSheet::loadStyleSheet("din-1505-2");
        $citeProc = new CiteProc($style);
        $cssStyles = $citeProc->renderCssStyles();
        $this->assertTrue(strpos($cssStyles, "csl-entry") !== false);
        $this->assertTrue(strpos($cssStyles, "text-indent: 45px") !== false);
    }

    public function testRenderCssStyleLineAndEntrySpacing()
    {
        $style = StyleSheet::loadStyleSheet("harvard-north-west-university");
        $citeProc = new CiteProc($style);
        $cssStyles = $citeProc->renderCssStyles();
        $this->assertTrue(strpos($cssStyles, "csl-entry") !== false);
        $this->assertTrue(strpos($cssStyles, "line-height: 1em") !== false);
        $this->assertTrue(strpos($cssStyles, "margin-bottom: 2em") !== false);
    }

    public function testGetInfo()
    {
        $style = StyleSheet::loadStyleSheet("harvard-north-west-university");
        $citeProc = new CiteProc($style);
        $citeProc->init();
        $info = CiteProc::getContext()->getInfo();
        $this->assertEquals("Hermien Wolff", $info->getAuthors()[0]->name);
        $this->assertEquals("North-West University - Harvard", $info->getTitle());
    }
}
