<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style\Options;


/**
 * Class GlobalOptionsTrait
 * @package Seboettg\CiteProc\Style
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class CitationOptions
{

    /**
     * If set to “true” (“false” is the default), names that would otherwise be hidden as a result of et-al abbreviation
     * are added one by one to all members of a set of ambiguous cites, until no more cites in the set can be
     * disambiguated by adding names.
     *
     * @var string
     */
    private $disambiguateAddNames = false;

    /**
     * If set to “true” (“false” is the default), ambiguous names (names that are identical in their “short” or
     * initialized “long” form, but differ when initials are added or the full given name is shown) are expanded. Name
     * expansion can be configured with givenname-disambiguation-rule.
     *
     * @var string
     */
    private $disambiguateAddGivenname = false;

    /**
     * Specifies a) whether the purpose of name expansion is limited to disambiguating cites, or has the additional goal
     * of disambiguating names (only in the latter case are ambiguous names in unambiguous cites expanded, e.g.
     * from “(Doe 1950; Doe 2000)” to “(Jane Doe 1950; John Doe 2000)”), b) whether name expansion targets all, or just
     * the first name of each cite, and c) the method by which each name is expanded.
     *
     * @var string
     */
    private $givennameDisambiguationRule = "";

    /**
     * If set to “true” (“false” is the default), an alphabetic year-suffix is added to ambiguous cites
     * (e.g. “Doe 2007, Doe 2007” becomes “Doe 2007a, Doe 2007b”) and to their corresponding bibliographic entries.
     * The assignment of the year-suffixes follows the order of the bibliographies entries, and additional letters are
     * used once “z” is reached (“z”, “aa”, “ab”, …, “az”, “ba”, etc.). By default the year-suffix is appended to the
     * cite, and to the first year rendered through cs:date in the bibliographic entry, but its location can be
     * controlled by explicitly rendering the “year-suffix” variable using cs:text. If “year-suffix” is rendered through
     * cs:text in the scope of cs:citation, it is suppressed for cs:bibliography, unless it is also rendered through
     * cs:text in the scope of cs:bibliography, and vice versa.
     *
     * @var string
     */
    private $disambiguateAddYearSuffix = false;

    /**
     * CitationOptions constructor.
     * @param \SimpleXMLElement $node
     */
    public function __construct(\SimpleXMLElement $node)
    {
        /** @var \SimpleXMLElement $attribute */
        foreach ($node->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case 'disambiguate-add-names':
                    $this->disambiguateAddNames = (string)$attribute;
                    break;
                case 'disambiguate-add-givenname':
                    $this->disambiguateAddGivenname = (string)$attribute;
                    break;
                case 'givenname-disambiguation-rule':
                    $this->givennameDisambiguationRule = (string)$attribute;
                    break;
                case 'disambiguate-add-year-suffix':
                    $this->disambiguateAddYearSuffix = (string)$attribute;
                    break;
            }
        }
    }

    /**
     * @return string
     */
    public function getDisambiguateAddNames()
    {
        return $this->disambiguateAddNames;
    }

    /**
     * @param string $disambiguateAddNames
     */
    public function setDisambiguateAddNames($disambiguateAddNames)
    {
        $this->disambiguateAddNames = $disambiguateAddNames;
    }

    /**
     * @return string
     */
    public function getDisambiguateAddGivenname()
    {
        return $this->disambiguateAddGivenname;
    }

    /**
     * @param string $disambiguateAddGivenname
     */
    public function setDisambiguateAddGivenname($disambiguateAddGivenname)
    {
        $this->disambiguateAddGivenname = $disambiguateAddGivenname;
    }

    /**
     * @return string
     */
    public function getGivennameDisambiguationRule()
    {
        return $this->givennameDisambiguationRule;
    }

    /**
     * @param string $givennameDisambiguationRule
     */
    public function setGivennameDisambiguationRule($givennameDisambiguationRule)
    {
        $this->givennameDisambiguationRule = $givennameDisambiguationRule;
    }

    /**
     * @return string
     */
    public function getDisambiguateAddYearSuffix()
    {
        return $this->disambiguateAddYearSuffix;
    }

    /**
     * @param string $disambiguateAddYearSuffix
     */
    public function setDisambiguateAddYearSuffix($disambiguateAddYearSuffix)
    {
        $this->disambiguateAddYearSuffix = $disambiguateAddYearSuffix;
    }


    public function isDisambiguationActivated()
    {
        return ($this->disambiguateAddNames || $this->disambiguateAddGivenname || $this->disambiguateAddYearSuffix);
    }
}