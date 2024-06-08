<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: Type0.php 850 2016-05-26 08:52:43Z jan.slabon $
 */

/**
 * Class for Type0 fonts
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Font_Type0 extends SetaPDF_Core_Font
    implements SetaPDF_Core_Font_Glyph_Collection_CollectionInterface
{
    /**
     * The font name
     *
     * @var string
     */
    protected $_fontName;

    /**
     * @var SetaPDF_Core_Font_Cmap
     */
    protected $_toUnicodeTable;

    /**
     * @var SetaPDF_Core_Font_Cmap
     */
    protected $_encodingTable;

    /**
     * The font bounding box
     *
     * @var array
     */
    protected $_fontBBox;

    /**
     * The distance from baseline of highest ascender (Typographic ascent)
     *
     * @return float
     */
    protected $_ascent;

    /**
     * The distance from baseline of lowest descender (Typographic descent)
     *
     * @return float
     */
    protected $_descent;

    /**
     * Cache for width values
     *
     * @var array
     */
    protected $_widths = array();

    /**
     * Cache array for the splitCharCodes method.
     *
     * @var array
     */
    protected $_splitCharCodesCache = array();

    /**
     * The average width of glyphs in the font.
     *
     * @var integer|float
     */
    protected $_avgWidth = null;

    /**
     * Get the descandant font dictionary.
     *
     * In PDF there's only a single descendant font.
     *
     * @return SetaPDF_Core_Type_Dictionary
     */
    protected function _getDescendantFontDictionary()
    {
        $dictionary = $this->_dictionary->getValue('DescendantFonts')->ensure();
        return $dictionary->offsetGet(0)->ensure();
    }

    /**
     * Helper method to get a specific value from the font descriptor of the font.
     *
     * @param string $name
     * @param mixed $default A default value returned if the $name doesn't exists
     * @return bool|mixed|null
     */
    protected function _getFontDescriptorValue($name, $default = null)
    {
        $dictionary = $this->_getDescendantFontDictionary();

        $descriptor = $dictionary->offsetGet('FontDescriptor')->ensure();

        if ($descriptor->offsetExists($name)) {
            return $descriptor->offsetGet($name)->ensure()->toPhp();
        }

        return $default;
    }

    /**
     * Get the char codes table of this font.
     *
     * @return mixed|void
     * @throws SetaPDF_Exception_NotImplemented
     * @internal
     */
    protected function _getCharCodesTable()
    {
        if (null === $this->_toUnicodeTable) {
            if ($this->_dictionary->offsetExists('ToUnicode')) {
                $toUnicodeStream = $this->_dictionary->getValue('ToUnicode')->ensure();

                if ($toUnicodeStream instanceof SetaPDF_Core_Type_Stream) {
                    $stream = $toUnicodeStream->getStream();
                    $this->_toUnicodeTable = SetaPDF_Core_Font_Cmap::create(new SetaPDF_Core_Reader_String($stream));
                    return $this->_toUnicodeTable;
                }
            }
        } else {
            return $this->_toUnicodeTable;
        }

        $map = $this->_getEncodingTable();
        if ($map instanceof SetaPDF_Core_Font_Cmap) {
            $this->_toUnicodeTable = $map;
            return $this->_toUnicodeTable;
        }

        return $map;
    }

    /**
     * Get the CMaps table for this font.
     *
     * @return array|SetaPDF_Core_Font_Cmap
     */
    protected function _getEncodingTable()
    {
        if (isset($this->_encodingTable)) {
            return $this->_encodingTable;
        }

        /**
         * If the font is a composite font that uses one of the predefined CMaps listed in Table 118 (except
         * Identity–H and Identity–V) or whose descendant CIDFont uses the Adobe-GB1, Adobe-CNS1, Adobe-Japan1,
         * or Adobe-Korea1 character collection:
         */
        $encodingCidMap = false;
        $encoding = null;
        if ($this->_dictionary->offsetExists('Encoding')) {
            $encoding = $this->_dictionary->getValue('Encoding')->ensure();
            try {
                if ($encoding instanceof SetaPDF_Core_Type_Name) {
                    $encodingCidMap = SetaPDF_Core_Font_Cmap::createNamed($encoding->getValue());
                } else if ($encoding instanceof SetaPDF_Core_Type_Stream) {
                    $stream         = $encoding->getStream();
                    $encodingCidMap = SetaPDF_Core_Font_Cmap::create(new SetaPDF_Core_Reader_String($stream));
                }
            } catch (InvalidArgumentException $e) {}
        }

        $descendantFonts = $this->_dictionary->getValue('DescendantFonts');
        if ($descendantFonts) {
            $descendantFonts = $descendantFonts->ensure();
            $fontObject      = $descendantFonts[0]->ensure();

            $cidInfo = $fontObject->getValue('CIDSystemInfo');
            $cidInfo = $cidInfo->ensure();

            $cidName = $cidInfo->getValue('Registry')->getValue()
                . '-'
                . $cidInfo->getValue('Ordering')->getValue()
                . '-UCS2';

            try {
                $map = SetaPDF_Core_Font_Cmap::createNamed($cidName);
                if ($encodingCidMap) {
                    $map->setCidMap($encodingCidMap);
                }

                $this->_encodingTable = $map;
                return $map;

            } catch (InvalidArgumentException $e) {
                // no cid map was found
            }
        }

        // fallback if no encoding/ToUnicode is defined
        // TODO: Check for collisions
        if ($encoding !== null) {
            $this->_encodingTable = new SetaPDF_Core_Font_Cmap();
            $this->_encodingTable->addRangeMapping(0, 65536, "0000", 2);
            if ($encodingCidMap) {
                $this->_encodingTable->copyCidRangeMapping($encodingCidMap);
            }

            return $this->_encodingTable;
        }

        return array();
    }

    /**
     * Get the font name.
     *
     * @return string
     */
    public function getFontName()
    {
        if (null === $this->_fontName)
            $this->_fontName = $this->_dictionary->offsetGet('BaseFont')->ensure()->getValue();

        return $this->_fontName;
    }

    /**
     * Get the font family.
     *
     * @return string|null
     */
    public function getFontFamily()
    {
        return $this->_getFontDescriptorValue('FontFamily', null);
    }

    /**
     * Checks if the font is bold.
     *
     * @return boolean
     */
    public function isBold()
    {
        $fontWeight = $this->_getFontDescriptorValue('FontWeight', 400);
        return $fontWeight >= 700;
    }

    /**
     * Checks if the font is italic.
     *
     * @return boolean
     */
    public function isItalic()
    {
        return $this->getItalicAngle() != 0;
    }

    /**
     * Checks if the font is monospace.
     *
     * @return boolean
     */
    public function isMonospace()
    {
        $flags = $this->_getFontDescriptorValue('Flags', 0);
        return ($flags & 1) == 1;
    }

    /**
     * Returns the font bounding box.
     *
     * @return array
     * @throws SetaPDF_Exception_NotImplemented
     */
    public function getFontBBox()
    {
        if (null === $this->_fontBBox) {
            $fontBBox = $this->_getFontDescriptorValue('FontBBox');
            $this->_fontBBox = array(
                'llx' => $fontBBox[0],
                'lly' => $fontBBox[1],
                'urx' => $fontBBox[2],
                'ury' => $fontBBox[3]
            );
        }

        return $this->_fontBBox;
    }

    /**
     * Returns the italic angle.
     *
     * @return float
     */
    public function getItalicAngle()
    {
        return $this->_getFontDescriptorValue('ItalicAngle', 0);
    }

    /**
     * Returns the distance from baseline of highest ascender (Typographic ascent).
     *
     * @return float
     */
    public function getAscent()
    {
        if (null === $this->_ascent)
            $this->_ascent = $this->_getFontDescriptorValue('Ascent');

        return $this->_ascent;
    }

    /**
     * Returns the distance from baseline of lowest descender (Typographic descent).
     *
     * @return float
     * @throws SetaPDF_Exception_NotImplemented
     * @internal
     */
    public function getDescent()
    {
        if (null === $this->_descent)
            $this->_descent = $this->_getFontDescriptorValue('Descent');

        return $this->_descent;
    }

    /**
     * Get the missing glyph width.
     *
     * @return integer|float
     */
    public function getMissingWidth()
    {
        $dictionary = $this->_getDescendantFontDictionary();
        if ($dictionary->offsetExists('DW')) {
            return $dictionary->getValue('DW')->ensure()->getValue();
        }

        return 1000; // default value of DW
    }

    /**
     * Get the average glyph width.
     *
     * @param boolean $calculateIfUndefined
     * @return integer|float
     */
    public function getAvgWidth($calculateIfUndefined = false)
    {
        $default = parent::getAvgWidth();
        $avgWidth = $this->_getFontDescriptorValue('AvgWidth', $default);

        if ($calculateIfUndefined && $default === $avgWidth) {
            if (isset($this->_avgWidth)) {
                return $this->_avgWidth;
            }

            $dictionary = $this->_getDescendantFontDictionary();
            $w = $dictionary->getValue('W');
            $allWidths = array();

            if (null !== $w) {
                $w = $w->ensure()->toPhp();
                for ($i = 0, $c = count($w); $i < $c;) {
                    if (is_array($w[$i + 1])) {
                        $i++; // simulate $start
                        $widths = $w[$i++];
                        foreach ($widths AS $width) {
                            $allWidths[] = $width;
                        }
                    } else {
                        $i += 2; // simulate $start and $end
                        $allWidths[] = $w[$i++];
                    }
                }
            }

            $this->_avgWidth = $avgWidth = array_sum($allWidths) / count($allWidths);
        }

        return $avgWidth;
    }

    /**
     * Get the max glyph width.
     *
     * @return integer|float
     */
    public function getMaxWidth()
    {
        return $this->_getFontDescriptorValue('MaxWidth');
    }

    /**
     * Get the width of a glyph/character.
     *
     * @param string $char
     * @param string $encoding The input encoding
     * @return float|int
     * @throws SetaPDF_Exception_NotImplemented
     * @internal
     */
    public function getGlyphWidth($char, $encoding = 'UTF-16BE')
    {
        if ($encoding !== 'UTF-16BE')
            $char = SetaPDF_Core_Encoding::convert($char, $encoding, 'UTF-16BE');
        
        if (isset($this->_widths[$char])) {
            return $this->_widths[$char];
        }

        $table = $this->_getCharCodesTable();

        if ($table instanceof SetaPDF_Core_Font_Cmap) {
            if ($table->getCidMap()) {
                $src = $table->reverseLookup($char);
                if ($src === false) {
                    $this->_widths[$char] = $this->getMissingWidth();
                    return $this->_widths[$char];
                }

                $src = SetaPDF_Core_Encoding::utf16BeToUnicodePoint($src);
                $src = (string)$table->getCidMap()->reverseCidLoopkup($src);
                $cid = $table->getCidMap()->lookupCid($src);

            } else {
                $cid = $table->lookupCid($char);
                if (!$cid) {
                    $cid = $table->reverseLookup($char);
                }
            }

            if ($cid !== null) {
                if (is_string($cid)) {
                    if (strlen($cid) === 1) {
                        $cid = ord($cid);
                    } else {
                        $cid = SetaPDF_Core_Encoding::utf16BeToUnicodePoint($cid);
                    }
                }

                $dictionary = $this->_getDescendantFontDictionary();
                $w          = $dictionary->getValue('W');
                if (null !== $w) {
                    $w = $w->ensure()->toPhp();
                    for ($i = 0, $c = count($w); $i < $c;) {
                        if (is_array($w[$i + 1])) {
                            $start  = $w[$i++];
                            $widths = $w[$i++];
                            if ($cid >= $start && $cid < $start + count($widths)) {
                                $this->_widths[$char] = $widths[$cid - $start];
                                return $this->_widths[$char];
                            }
                        } else {
                            $start = $w[$i++];
                            $end   = $w[$i++];
                            $width = $w[$i++];
                            if ($cid >= $start && $cid <= $end) {
                                $this->_widths[$char] = $width;
                                return $this->_widths[$char];
                            }
                        }
                    }
                }
            }
        }

        return $this->getMissingWidth();
    }

    /**
     * Get the width of a glpyh by its char code.
     *
     * @param string $charCode
     * @return float|int
     */
    public function getGlyphWidthByCharCode($charCode)
    {
        if (null === $this->_widthsByCharCode) {
            $this->_widthsByCharCode = array();
        }

        if (isset($this->_widthsByCharCode[$charCode])) {
            return $this->_widthsByCharCode[$charCode];
        }

        $table = $this->_getCharCodesTable();

        if ($table instanceof SetaPDF_Core_Font_Cmap) {
            if ($table->getCidMap()) {
                $cid = $table->getCidMap()->lookupCid($charCode);

            } else {
                $cid = $table->lookupCid($charCode);
                if (!$cid) {
                    $cid = $charCode;
                }
            }

            if ($cid !== null) {
                if (is_string($cid)) {
                    if (strlen($cid) === 1) {
                        $cid = ord($cid);
                    } else {
                        $cid = SetaPDF_Core_Encoding::utf16BeToUnicodePoint($cid);
                    }
                }

                $dictionary = $this->_getDescendantFontDictionary();
                $w          = $dictionary->getValue('W');
                if (null !== $w) {
                    $w = $w->ensure();
                    if (!($w instanceof SetaPDF_Core_Type_Array)) {
                        return $this->getMissingWidth();
                    }

                    for ($i = 0, $c = count($w); $i < $c;) {
                        if ($w[$i + 1]->ensure() instanceof SetaPDF_Core_Type_Array) {
                            $start  = $w[$i++]->ensure()->getValue();
                            $widths = $w[$i++]->ensure();
                            if ($cid >= $start && $cid < $start + count($widths)) {
                                $this->_widthsByCharCode[$charCode] = $widths[$cid - $start]->ensure()->getValue();
                                return $this->_widthsByCharCode[$charCode];
                            }
                        } else {
                            $start = $w[$i++]->ensure()->getValue();
                            $end   = $w[$i++]->ensure()->getValue();
                            $width = $w[$i++]->ensure()->getValue();
                            if ($cid >= $start && $cid <= $end) {
                                $this->_widthsByCharCode[$charCode] = $width;
                                return $this->_widthsByCharCode[$charCode];
                            }
                        }
                    }
                }
            }
        }

        return $this->getMissingWidth();
    }

    /**
     * Get the width of glyphs by their char codes.
     *
     * @param string $charCodes
     * @return float|int
     */
    public function getGlyphsWidthByCharCodes($charCodes)
    {
        $cacheKey = '_' . $charCodes;
        if (isset($this->_glyphsWidthCache[$cacheKey])) {
            return $this->_glyphsWidthCache[$cacheKey];
        }

        $width = 0;

        $charsCodes = $this->splitCharCodes($charCodes);

        foreach ($charsCodes AS $charCode) {
            $width += $this->getGlyphWidthByCharCode($charCode);
        }

        $this->_glyphsWidthCache = array($cacheKey => $width);

        return $width;
    }

    /**
     * Converts char codes from the font specific encoding to another encoding.
     *
     * @param string $charCodes The char codes in the font specific encoding.
     * @param string $encoding The resulting encoding
     * @param bool $normalize Specifies if unknown mappings (e.g. to points in the private unicode area) should be
     *                        mapped to meaningful values.
     * @param bool $asArray
     * @return string|array
     */
    public function getCharsByCharCodes($charCodes, $encoding = 'UTF-8', $normalize = false, $asArray = true)
    {
        $chars = array();
        foreach ($this->splitCharCodes($charCodes) AS $charCode) {
            $chars[] = $this->getCharByCharCode($charCode, $encoding, $normalize);
        }

        if ($asArray) {
            return $chars;
        }

        return join('', $chars);
    }

    /**
     * Split a string of char codes into single char codes.
     *
     * @param string $charCodes
     * @return array
     */
    public function splitCharCodes($charCodes)
    {
        $strings = array();
        $table = $this->_getCharCodesTable();

        for ($i = 0, $len = strlen($charCodes); $i < $len; $i++) {
            $string = $charCodes[$i];

            if (!isset($this->_splitCharCodesCache[$string])) {
                $this->_splitCharCodesCache[$string] = SetaPDF_Core_Encoding::toUtf16Be($table, $string, true, true);
            }

            $text = $this->_splitCharCodesCache[$string];

            if ('' === $text && $i < ($len - 1)) {
                $string .= $charCodes[++$i];
            }

            $strings[] = $string;
        }

        return $strings;
    }
}