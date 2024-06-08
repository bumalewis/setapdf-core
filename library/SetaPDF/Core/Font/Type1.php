<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: Type1.php 845 2016-05-11 13:37:25Z jan.slabon $
 */

/**
 * Class for Type1 fonts
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Font_Type1
    extends SetaPDF_Core_Font_Simple
    implements SetaPDF_Core_Font_Glyph_Collection_CollectionInterface
{
    /**
     * The font name
     *
     * @var string
     */
    protected $_fontName;

    /**
     * The font family
     *
     * @var string
     */
    protected $_fontFamily;

    /**
     * The font bounding box
     *
     * @var array
     */
    protected $_fontBBox;

    /**
     * The italic angle
     *
     * @var float
     */
    protected $_italicAngle;

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
     * The maximum width of glyphs in the font
     *
     * @var integer|float
     */
    protected $_maxWidth;

    /**
     * The width to use for character codes whose widths are not specified in a font dictionary’s Widths array.
     *
     * @var integer|float
     */
    protected $_missingWidth;

    /**
     * Flag indicating if this font is bold.
     *
     * @var boolean
     */
    protected $_isBold = false;

    /**
     * Flag indicating if this font is italic.
     *
     * @var boolean
     */
    protected $_isItalic = false;

    /**
     * Flag indicating if this font is monospace.
     *
     * @var boolean
     */
    protected $_isMonospace = false;

    /**
     * Glyph widths
     *
     * @var array
     */
    protected $_widths;

    /**
     * The UTF-16BE unicode value for a substitute character
     *
     * @var null|string
     */
    protected $_substituteCharacter = null;

    /**
     * A cache of width values
     *
     * @var array
     */
    protected $_glyphsWidthCache = array();

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Type_IndirectObjectInterface|SetaPDF_Core_Type_Dictionary $indirectObjectOrDictionary
     * @throws SetaPDF_Core_Font_Exception
     */
    public function __construct($indirectObjectOrDictionary)
    {
        $dictionary = $indirectObjectOrDictionary->ensure();
        foreach (array('FirstChar', 'LastChar', 'Widths', 'FontDescriptor') AS $key) {
            if (!$dictionary->offsetExists($key)) {
                throw new SetaPDF_Core_Font_Exception(sprintf('Missing "%s" entry in font dictionary.', $key));
            }
        }

        parent::__construct($indirectObjectOrDictionary);
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
        $descriptor = $this->_dictionary->offsetGet('FontDescriptor')->ensure();

        if ($descriptor->offsetExists($name)) {
            return $descriptor->offsetGet($name)->ensure()->toPhp();
        }

        return $default;
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
     * @return string
     */
    public function getFontFamily()
    {
        if (null === $this->_fontFamily)
            $this->_fontFamily = $this->_getFontDescriptorValue('FontFamily');

        return $this->_fontFamily;
    }

    /**
     * Checks if the font is bold.
     *
     * @return boolean
     */
    public function isBold()
    {
        if (null === $this->_isBold) {
            $fontWeight = $this->_getFontDescriptorValue('FontWeight', 400);
            $this->_isBold = $fontWeight >= 700;
        }

        return $this->_isBold;
    }

    /**
     * Checks if the font is italic.
     *
     * @return boolean
     */
    public function isItalic()
    {
        if (null === $this->_isItalic)
            $this->_isItalic = $this->getItalicAngle() != 0;

        return $this->_isItalic;
    }

    /**
     * Checks if the font is monospace.
     *
     * @return boolean
     */
    public function isMonospace()
    {
        if (null === $this->_isMonospace) {
            $flags = $this->_getFontDescriptorValue('Flags', 0);
            $this->_isMonospace = ($flags & 1) == 1;
        }

        return $this->_isMonospace;
    }

    /**
     * Returns the font bounding box.
     *
     * @return array
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
        if (null === $this->_italicAngle)
            $this->_italicAngle = $this->_getFontDescriptorValue('ItalicAngle', 0);

        return $this->_italicAngle;
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
     */
    public function getDescent()
    {
        if (null === $this->_descent)
            $this->_descent = $this->_getFontDescriptorValue('Descent');

        return $this->_descent;
    }

    /**
     * Get the average glyph width.
     *
     * @param boolean $calculateIfUndefined
     * @return integer|float
     */
    public function getAvgWidth($calculateIfUndefined = false)
    {
        if (null === $this->_avgWidth) {
            $default = SetaPDF_Core_Font::getAvgWidth();
            $this->_avgWidth = $this->_getFontDescriptorValue('AvgWidth', $default);
            if ($calculateIfUndefined && $default === $this->_avgWidth) {
                return parent::getAvgWidth(true);
            }
        }

        return $this->_avgWidth;
    }

    /**
     * Get the max glyph width.
     *
     * @return integer|float
     */
    public function getMaxWidth()
    {
        if (null === $this->_maxWidth)
            $this->_maxWidth = $this->_getFontDescriptorValue('MaxWidth');

        return $this->_maxWidth;
    }

    /**
     * Get the missing glyph width.
     *
     * @return integer|float
     */
    public function getMissingWidth()
    {
        if (null === $this->_missingWidth) {
            $this->_missingWidth = $this->_getFontDescriptorValue('MissingWidth');
        }

        return $this->_missingWidth;
    }

    /**
     * Resolves the width values from the font descriptor and fills the {@link $_width}-array.
     */
    protected function _getWidths()
    {
        $firstChar = $this->_dictionary->offsetGet('FirstChar')->ensure()->toPhp();
        $lastChar = $this->_dictionary->offsetGet('LastChar')->ensure()->toPhp();
        $widths = $this->_dictionary->offsetGet('Widths')->ensure()->toPhp();
        $table = $this->_getEncodingTable();

        // Fallback to ToUnicode data
        $charCodeTable = $this->_getCharCodesTable();
        $cmapExists = $charCodeTable instanceof SetaPDF_Core_Font_Cmap;

        $this->_widths = array();
        $this->_widthsByCharCode = array();
        for ($i = $firstChar; $i <= $lastChar; $i++) {
            $char = chr($i);
            $width = $widths[$i - $firstChar];

            $this->_widthsByCharCode[$char] = $width;

            $utf16BeCodePoint = SetaPDF_Core_Encoding::toUtf16Be($table, $char, false, true);
            if (!isset($this->_widths[$utf16BeCodePoint])) {
                $this->_widths[$utf16BeCodePoint] = $width;
            }

            if ($cmapExists) {
                $utf16BeCodePoint = $charCodeTable->lookup($char);
                $this->_widths[$utf16BeCodePoint] = $width;
            }
        }
    }

    /**
     * Get the width of a glyph/character.
     *
     * @see SetaPDF_Core_Font::getGlyphWidth()
     * @param string $char
     * @param string $encoding The input encoding
     * @return float|int
     */
    public function getGlyphWidth($char, $encoding = 'UTF-16BE')
    {
        if (null === $this->_widths) {
            $this->_getWidths();
        }

        return parent::getGlyphWidth($char, $encoding);
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
            $this->_getWidths();
        }

        return parent::getGlyphWidthByCharCode($charCode);
    }

    /**
     * Get the base encoding of the font.
     *
     * If no BaseEncoding entry is available we use the
     * Standard encoding for now. This should be extended
     * to get the fonts build in encoding later.
     *
     * @return array
     */
    public function getBaseEncodingTable()
    {
        return SetaPDF_Core_Encoding_Standard::$table;
    }
}