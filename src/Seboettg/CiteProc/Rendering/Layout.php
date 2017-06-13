<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Context;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Rendering\Date\Date;
use Seboettg\CiteProc\Rendering\Name\Name;
use Seboettg\CiteProc\Rendering\Name\Names;
use Seboettg\CiteProc\Style\Citation;
use Seboettg\CiteProc\Style\StyleElement;
use Seboettg\CiteProc\Styles\AffixesTrait;
use Seboettg\CiteProc\Styles\ConsecutivePunctuationCharacterTrait;
use Seboettg\CiteProc\Styles\FormattingTrait;
use Seboettg\CiteProc\Styles\DelimiterTrait;
use Seboettg\CiteProc\Util\Factory;
use Seboettg\CiteProc\Util\NameHelper;
use Seboettg\CiteProc\Util\StringHelper;
use Seboettg\Collection\ArrayList;


/**
 * Class Layout
 * @package Seboettg\CiteProc\Rendering
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Layout implements Rendering
{

    private static $numberOfCitedItems = 0;

    use AffixesTrait,
        FormattingTrait,
        DelimiterTrait,
        ConsecutivePunctuationCharacterTrait;

    /**
     * @var ArrayList
     */
    private $children;

    /**
     * When used within cs:citation, the delimiter attribute may be used to specify a delimiter for cites within a
     * citation.
     * @var string
     */
    private $delimiter = "";


    private $parent;

    /**
     * @param \Seboettg\CiteProc\Style\StyleElement $parent
     */
    public function __construct($node, $parent)
    {
        $this->parent = $parent;
        self::$numberOfCitedItems = 0;
        $this->children = new ArrayList();
        foreach ($node->children() as $child) {
            $this->children->append(Factory::create($child, $this));
        }
        $this->initDelimiterAttributes($node);
        $this->initAffixesAttributes($node);
        $this->initFormattingAttributes($node);
    }

    /**
     * @param array|DataList $data
     * @param array|ArrayList $citationItems
     * @return string|array
     */
    public function render($data, $citationItems = [])
    {
        $ret = "";
        $sorting = CiteProc::getContext()->getSorting();
        if (!empty($sorting)) {
            CiteProc::getContext()->setRenderingState("sorting");
            $sorting->sort($data);
            CiteProc::getContext()->setRenderingState("rendering");
        }

        if (CiteProc::getContext()->isModeBibliography()) {
            if ($data instanceof DataList) {
                foreach ($data as $citationNumber => $item) {
                    ++self::$numberOfCitedItems;
                    CiteProc::getContext()->getResults()->append($this->wrapBibEntry($this->renderSingle($item, $citationNumber)));
                }
                $ret .= implode($this->delimiter, CiteProc::getContext()->getResults()->toArray());
            } else {
                $ret .= $this->wrapBibEntry($this->renderSingle($data, null));
            }
            $ret = StringHelper::clearApostrophes($ret);
            return "<div class=\"csl-bib-body\">" . $ret . "\n</div>";

        } else if (CiteProc::getContext()->isModeCitation()) {
            if ($data instanceof DataList) {
                if ($citationItems->count() > 0) { //is there a filter for specific citations?
                    if ($this->isGroupedCitations($citationItems)) { //if citation items grouped?
                        return $this->renderGroupedCitations($data, $citationItems);
                    } else {
                        $data = $this->filterCitationItems($data, $citationItems);
                        $ret = $this->renderCitations($data);
                    }

                } else {
                    $ret = $this->renderCitations($data);
                }

            } else {
                $ret .= $this->renderSingle($data, null);
            }
        }
        $ret = StringHelper::clearApostrophes($ret);
        return $this->addAffixes($ret);
    }

    /**
     * @param $data
     * @param int|null $citationNumber
     * @return string
     */
    private function renderSingle($data, $citationNumber = null)
    {

        $bibliographyOptions = CiteProc::getContext()->getBibliographySpecificOptions();
        $inMargin = [];
        $margin = [];
        foreach ($this->children as $key => $child) {
            $rendered = $child->render($data, $citationNumber);
            $this->getChildsAffixesAndDelimiter($child);
            if (CiteProc::getContext()->isModeBibliography() && $bibliographyOptions->getSecondFieldAlign() === "flush") {

                if ($key === 0 && !empty($rendered)) {
                    $inMargin[] = $rendered;
                } else {
                    $margin[] = $rendered;
                }
            } else {
                $inMargin[] = $rendered;
            }
        }


        if (!empty($inMargin) && !empty($margin) && CiteProc::getContext()->isModeBibliography()) {
            $leftMargin = $this->removeConsecutiveChars($this->htmlentities($this->format(implode("", $inMargin))));
            $rightInline = $this->removeConsecutiveChars($this->htmlentities($this->format(implode("", $margin))) . $this->suffix);
            $res  = '<div class="csl-left-margin">' . $leftMargin . '</div>';
            $res .= '<div class="csl-right-inline">' . $rightInline . '</div>';
            return $res;
        } else if (!empty($inMargin)) {
            $res = $this->format(implode("", $inMargin));
            return $this->htmlentities($this->removeConsecutiveChars($res));
        }
        return "";
    }

    /**
     * @return int
     */
    public static function getNumberOfCitedItems()
    {
        return self::$numberOfCitedItems;
    }

    /**
     * @param string $value
     * @return string
     */
    private function wrapBibEntry($value)
    {
        return "\n  <div class=\"csl-entry\">" . $this->addAffixes($value) . "</div>";
    }

    /**
     * @param string $text
     * @return string
     */
    private function htmlentities($text)
    {
        $text = preg_replace("/(.*)&([^#38;|amp;].*)/u", "$1&#38;$2", $text);
        return $text;
    }

    /**
     * @param $data
     * @param $ret
     * @return string
     */
    private function renderCitations($data)
    {
        CiteProc::getContext()->getResults()->replace([]);
        foreach ($data as $citationNumber => $item) {
            CiteProc::getContext()->getResults()->append($this->renderSingle($item, $citationNumber));
        }

        if (CiteProc::getContext()->getCitationSpecificOptions()->isDisambiguationActivated() &&
            $this->containsAmbiguousEntries()) {
            $this->disambiguate($data);
        }

        return implode($this->delimiter, CiteProc::getContext()->getResults()->toArray());
    }

    /**
     * @param DataList $data
     * @param ArrayList $citationItems
     * @return mixed
     */
    private function filterCitationItems($data, $citationItems)
    {
        $arr = $data->toArray();

        $arr_ = array_filter($arr, function($dataItem) use ($citationItems) {
            foreach ($citationItems as $citationItem) {
                if ($dataItem->id === $citationItem->id) {
                    return true;
                }
            }
            return false;
        });

        return $data->replace($arr_);
    }

    /**
     * @param ArrayList $citationItems
     * @return bool
     */
    private function isGroupedCitations(ArrayList $citationItems)
    {
        $firstItem = array_values($citationItems->toArray())[0];
        if (is_array($firstItem)) {
            return true;
        }
        return false;
    }

    /**
     * @param DataList $data
     * @param ArrayList $citationItems
     * @return array|string
     */
    private function renderGroupedCitations($data, $citationItems)
    {
        $group = [];
        foreach ($citationItems as $citationItemGroup) {
            $data_ = $this->filterCitationItems(clone $data, $citationItemGroup);
            CiteProc::getContext()->setCitationItems($data_);
            $group[] = $this->addAffixes(StringHelper::clearApostrophes($this->renderCitations($data_, "")));
        }
        if (CiteProc::getContext()->isCitationsAsArray()) {
            return $group;
        }
        return implode("\n", $group);
    }

    /**
     * returns true if result contains duplicated values, otherwise false
     * @return bool
     */
    private function containsAmbiguousEntries()
    {
        $countedValues = array_count_values(CiteProc::getContext()->getResults()->toArray());

        foreach ($countedValues as $value) {
            if ($value > 1) {
                return true;
            }
        }

        return false;
    }

    private function disambiguate($data)
    {
        if (CiteProc::getContext()->getCitationSpecificOptions()->getDisambiguateAddNames()) {
            $this->disambiguateAddNames($data);
        }
    }

    private function disambiguateAddNames($data)
    {
        do {
            /** @var Name $name */
            $name = $this->traverseUntilName($this->parent);
            if (!is_null($name) && !empty($etAlUseFirst = $name->getEtAlUseFirst())) {
                $name->setEtAlUseFirst(++$etAlUseFirst);
            } else {
                break; // break the loop
            }
            $this->renderCitations($data);
        } while($etAlUseFirst <= NameHelper::maxAuthors($data) && $this->containsAmbiguousEntries());
    }

    /**
     * @param $element
     * @return null|Name
     */
    private function traverseUntilName(&$element)
    {
        if ($element instanceof StyleElement) {
            $children = [$element->getLayout()];
        } elseif ($element instanceof ArrayList) {
            $children = $element;
        } elseif ($element instanceof Name) {
            return $element;
        } elseif (in_array(get_class($element), Rendering::RENDERING_LEAFS)) {
            return null;
        }



        foreach ($children as &$child) {
            if ($child instanceof Name) {
                return $child;
            } elseif ($child instanceof Text) {
                if ($child->rendersMacro()) {
                    $macro = CiteProc::getContext()->getMacro($child->getVariable());
                    return $this->traverseUntilName($macro);
                }
                continue;
            } elseif ($child instanceof Date || $child instanceof Label || $child instanceof Number) {
                continue;
            } elseif ($child instanceof StyleElement) {
                return $this->traverseUntilName($child->getLayout());
            } else {
                $newChildren = $child->getChildren();
                return $this->traverseUntilName($newChildren);
            }
        }
        return null;
    }

    /**
     * @return ArrayList
     */
    public function getChildren()
    {
        return $this->children;
    }
}