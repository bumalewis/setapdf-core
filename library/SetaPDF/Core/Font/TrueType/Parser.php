<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: Parser.php 855 2016-06-07 09:19:02Z jan.slabon $
 */

/**
 * Parser class for TTF/OTF files
 *
 * Based on the OpenType specification 1.6: {@link http://www.microsoft.com/typography/otspec/}
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Font_TrueType_Parser
{
    /**
     * The reader instance
     *
     * @var SetaPDF_Core_Reader_Binary
     */
    protected $_reader;

    /**
     * Position of tables in the TTF file
     *
     * @var array
     */
    protected $_tablePositions = array();

    /* Data from the Font Header (head) */

    /**
     * Units per em
     *
     * @var float
     */
    protected $_unitsPerEm = null;

    /**
     * @var float
     */
    protected $_xMin = null;

    /**
     * @var float
     */
    protected $_yMin = null;

    /**
     * @var float
     */
    protected $_xMax = null;

    /**
     * @var float
     */
    protected $_yMax = null;

    /**
     * MacStyle
     *
     * Bit 0: Bold (if set to 1);
     *
     * Bit 1: Italic (if set to 1)
     *
     * Bit 2: Underline (if set to 1)
     *
     * Bit 3: Outline (if set to 1)
     *
     * Bit 4: Shadow (if set to 1)
     *
     * Bit 5: Condensed (if set to 1)
     *
     * Bit 6: Extended (if set to 1)
     *
     * Bits 7-15: Reserved (set to 0).
     *
     * @var integer
     */
    protected $_macStyle = null;

  /* Data from the Horizontal Header (hhea) */

    /**
     * Typographic ascent. (Distance from baseline of highest ascender)
     *
     * @var float
     */
    protected $_ascender = null;

    /**
     * ypographic descent. (Distance from baseline of lowest descender)
     *
     * @var float
     */
    protected $_descender = null;

    /**
     * Number of hMetric entries in 'hmtx' table
     *
     * @var integer
     */
    protected $_numberOfHMetrics = null;

  /* Data from the Maximum Profile (maxp) */

    /**
     * The number of glyphs in the font.
     *
     * @var integer
     */
    protected $_numGlyphs = null;

  /* Data from the Horizontal Metrics (hmtx) */

    /**
     * The glyph widths
     *
     * @var array
     */
    protected $_widths = null;

  /* Data from the Naming Table (name) */

    /**
     * Data from the Naming Table
     *
     * @var array
     */
    protected $_names = null;

  /* Data from the Character To Glyph Index Mapping Table (cmap) */

    /**
     * Data from the Character To Glyph Index Mapping Table
     *
     * @var array
     */
    protected $_charsToGlyphs = null;

  /* Data form the OS/2 and Windows Metrics Table */

    /**
     * The weight class.
     *
     * @var integer
     */
    protected $_usWeightClass = null;

    /**
     * Type flags
     *
     * @var integer
     */
    protected $_fsType = null;

    /**
     * Font selection flags.
     *
     * @var integer
     */
    protected $_fsSelection = null;

    /**
     * sxHeight
     *
     * @var float
     */
    protected $_xHeight = null;

    /**
     * sCapHeight
     *
     * @var float
     */
    protected $_capHeight = null;

  /* Data from the PostScript table (post) */

    /**
     * @var float
     */
    protected $_italicAngle = null;

    /**
     * @var float
     */
    protected $_underlinePosition = null;

    /**
     * @var float
     */
    protected $_underlineThickness = null;

    /**
     * @var boolean
     */
    protected $_isFixedPitch = null;

    /**
     * @var string
     */
    protected $_familyClass = null;

    /**
     * @var string
     */
    protected $_panose = null;

    /**
     * The constructor.
     *
     * @param string|SetaPDF_Core_Reader_Binary $reader
     * @throws SetaPDF_Core_Exception
     */
    public function __construct($reader)
    {
        if (!$reader instanceof SetaPDF_Core_Reader_Binary) {
            $fileReader = new SetaPDF_Core_Reader_File($reader);
            $reader = new SetaPDF_Core_Reader_Binary($fileReader);
        }

        $this->_reader = $reader;

        $version = $this->_reader->readInt32();
        if (0x4F54544F === $version) {
            throw new SetaPDF_Core_Exception('OpenType fonts with PostScript outlines are not supported');
        } else if (0x00010000 !== $version && 0x74727565 !== $version) {
            throw new SetaPDF_Core_Exception('Unsupported file format');
        }

        $tableCount = $this->_reader->readUInt16();
        $this->_reader->skip(6); // searchRange, entrySelector, rangeShift

        for ($i = 0; $i < $tableCount; $i++) {
            $tag = $this->_reader->readBytes(4);
            // $checkSum = $this->_reader->readUInt32();
            $this->_reader->skip(4);
            $offset = $this->_reader->readUInt32();
            // $length = $this->_reader->readUInt32();
            $this->_reader->skip(4);
            $this->_tablePositions[$tag] = $offset;
        }
    }

    /**
     * Release resources.
     */
    public function cleanUp()
    {
        $this->_reader->cleanUp();
        $this->_reader = null;
    }

    /**
     * Check if a specific table exists.
     *
     * @param string $tag
     * @return boolean
     */
    public function tableExists($tag)
    {
        return isset($this->_tablePositions[$tag]);
    }

    /**
     * Get the units per em.
     *
     * @return float
     */
    public function getUnitsPerEm()
    {
        if (null === $this->_unitsPerEm) {
            $this->_parseHeadTable();
        }

        return $this->_unitsPerEm;
    }

    /**
     * Get the bounding box.
     *
     * @param float $factor
     * @return array
     */
    public function getBoundingBox($factor = 1.)
    {
        if (null === $this->_xMin) {
            $this->_parseHeadTable();
        }

        return array(
            round($this->_xMin * $factor),
            round($this->_yMin * $factor),
            round($this->_xMax * $factor),
            round($this->_yMax * $factor)
        );
    }

    /**
     * Get the mac style (macStyle).
     *
     * @return integer
     */
    public function getMacStyle()
    {
        if (null === $this->_macStyle) {
            $this->_parseHeadTable();
        }

        return $this->_macStyle;
    }

    /**
     * Get the ascender.
     *
     * @param float $factor
     * @return float
     */
    public function getAscender($factor = 1.)
    {
        if ($this->_ascender === null) {
            $this->_parseHheaTable();
        }

        return $this->_ascender * $factor;
    }

    /**
     * Get the descender.
     *
     * @param float $factor
     * @return float
     */
    public function getDescender($factor = 1.)
    {
        if ($this->_descender === null) {
            $this->_parseHheaTable();
        }

        return $this->_descender * $factor;
    }

    /**
     * Get the number of glyphs in the font file.
     *
     * @return integer
     */
    public function getNumGlyphs()
    {
        if ($this->_numGlyphs === null) {
            $this->_parseMaxpTable();
        }

        return $this->_numGlyphs;
    }

    /**
     * Get character/glyph width values.
     *
     * If $chars is null, all available width values will be returned.
     *
     * If $chars are passed, only the width values of the given chars will be returned.
     *
     * @param float $factor
     * @param array $chars
     * @return array
     */
    public function getWidths($factor = 1., $chars = null)
    {
        if ($this->_widths === null) {
            $this->_parseHmtxTable();
        }

        if ($this->_charsToGlyphs === null) {
            $this->_parseCmapTable();
        }

        $widths = array();

        if (is_array($chars)) {
            foreach ($chars AS $char) {
                if (isset($this->_charsToGlyphs[$char]) && $char !== false) {
                    $widths[] = $this->_widths[$this->_charsToGlyphs[$char]] * $factor;
                } else {
                    $widths[] = $this->getMissingWidth($factor);
                }
            }
        } else {
            foreach ($this->_charsToGlyphs AS $char => $glyphId) {
                $widths[$char] = $this->_widths[$glyphId] * $factor;
            }
        }

        return $widths;
    }

    /**
     * Get the width of a single character/glyph.
     *
     * @param float $factor
     * @param string $char
     * @return float|boolean
     */
    public function getWidth($factor, $char)
    {
        if ($this->_widths === null) {
            $this->_parseHmtxTable();
        }

        if ($this->_charsToGlyphs === null) {
            $this->_parseCmapTable();
        }

        if (isset($this->_charsToGlyphs[$char]) && $char !== false) {
            return $this->_widths[$this->_charsToGlyphs[$char]] * $factor;
        }

        return false;
    }

    /**
     * Get a glyph number by a character.
     *
     * @param string $char
     * @return integer|boolean
     */
    public function getGlyphNumberByChar($char)
    {
        if ($this->_charsToGlyphs === null) {
            $this->_parseCmapTable();
        }

        if (isset($this->_charsToGlyphs[$char])) {
            return $this->_charsToGlyphs[$char];
        }

        return false;
    }

    /**
     * Get the missing width.
     *
     * @param float $factor
     * @return float
     */
    public function getMissingWidth($factor = 1.)
    {
        if ($this->_widths === null) {
            $this->_parseHmtxTable();
        }

        return $this->_widths[0] * $factor;
    }

    /**
     * Get all registered names in the font file.
     *
     * @return array
     */
    public function getNames()
    {
        if ($this->_names === null) {
            $this->_parseNameTable();
        }

        return $this->_names;
    }

    /**
     * Get the postscript name of the font.
     *
     * @param string $encoding
     * @return string
     */
    public function getPostScriptName($encoding = 'UTF-8')
    {
        if ($this->_names === null) {
            $this->_parseNameTable();
        }

        // a: Platform: 1 [Macintosh]; Platform-specific encoding: 0 [Roman]; Language: 0 [English].
        if (isset($this->_names[1][0][0][6]) && SetaPDF_Core_Encoding::getLibrary() == 'iconv') {
            $name = SetaPDF_Core_Encoding::convert($this->_names[1][0][0][6], 'MACINTOSH', $encoding);
            return $name;
        }

        // b: Platform: 3 [Windows]; Platform-specific encoding: 1 [Unicode]; Language: 0x409 [English (American)]. 
        if (isset($this->_names[3][1][0x409][6])) {
            return SetaPDF_Core_Encoding::convert($this->_names[3][1][0x409][6], 'UCS-2', $encoding);
        }

        // b: Platform: 3 [Windows]; Platform-specific encoding: 0 [Symbol]; Language: 0x409 [English (American)].
        if (isset($this->_names[3][0][0x409][6])) {
            return SetaPDF_Core_Encoding::convert($this->_names[3][0][0x409][6], 'UCS-2', $encoding);
        }

        // Fallback if iconv is not available and no unicode value was found
        if (isset($this->_names[1][0][0][6])) {
            return $this->_names[1][0][0][6];
        }
    }

    /**
     * Alias for getPostScriptName().
     *
     * @param string $encoding
     * @return string
     */
    public function getFontName($encoding = 'UTF-8')
    {
        return $this->getPostScriptName($encoding);
    }

    /**
     * Get the font family name.
     *
     * @param string $encoding
     * @return string
     */
    public function getFontFamily($encoding = 'UTF-8')
    {
        return $this->_getName(1, $encoding);
    }

    /**
     * Get a name by nameId.
     *
     * @param integer $nameId
     * @param string $encoding
     * @return string
     */
    protected function _getName($nameId, $encoding = 'UTF-8')
    {
        if ($this->_names === null)
            $this->_parseNameTable();

        $found = false;
        foreach ($this->_names AS $platformId => $encodings) {
            foreach ($encodings AS $encodingId => $languages) {
                foreach ($languages AS $languageId => $names) {
                    if (isset($names[$nameId])) {
                        $found = true;
                        break 3;
                    }
                }
            }
        }

        /**
         * All OpenType fonts use Motorola-style byte ordering (Big Endian).
         *
         * @see https://www.microsoft.com/typography/otspec/otff.htm#otttables
         */
        if ($found) {
            switch ($platformId) {
                case 0: // Unicode
                    return SetaPDF_Core_Encoding::convert($names[$nameId], 'UTF-16BE', $encoding);
                case 1: // Mac
                    return SetaPDF_Core_Encoding::convert($names[$nameId], 'MacRoman', $encoding);
            }
        }

        return '';
    }

    /**
     * Get all covered characters.
     *
     * Returns an array of all covered characters in UTF-16BE encoding.
     *
     * @return array
     */
    public function getCoveredChars()
    {
        if ($this->_charsToGlyphs === null)
            $this->_parseCmapTable();

        return array_keys($this->_charsToGlyphs);
    }

    /**
     * Checks if characters are covered by this font.
     *
     * @param array $chars The chars in UTF-16BE encoding
     * @return boolean
     */
    public function areCharsCovered($chars)
    {
        if ($this->_charsToGlyphs === null)
            $this->_parseCmapTable();

        foreach ($chars AS $char) {
            if ($char === false)
                continue;

            if (!isset($this->_charsToGlyphs[$char]) && $char > "\x00\x31") {
                //echo SetaPDF_Core_Type_HexString::str2hex($char);
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a character is covered by this font.
     *
     * @param string $char The character in UTF-16BE encoding
     * @return boolean
     */
    public function isCharCovered($char)
    {
        if ($this->_charsToGlyphs === null)
            $this->_parseCmapTable();

        return isset($this->_charsToGlyphs[$char]);
    }

    /**
     * Get the weight class.
     *
     * @see http://www.microsoft.com/typography/otspec/os2.htm#wtc
     * @return integer
     */
    public function getUsWeightClass()
    {
        if ($this->_usWeightClass === null)
            $this->_parseOs2Table();

        return $this->_usWeightClass;
    }

    /**
     * Get the fsType flags.
     *
     * Indicates font embedding licensing rights for the font. Embeddable fonts may be stored in a document.
     *
     * @see http://www.microsoft.com/typography/otspec/os2.htm#fst
     * @return integer
     */
    public function getFsType()
    {
        if ($this->_fsType === null)
            $this->_parseOs2Table();

        return $this->_fsType;
    }

    /**
     * Checks if a font is embeddable.
     *
     * @return boolean
     */
    public function isEmbeddable()
    {
        $fsType = $this->getFsType();

        return ($fsType != 0x0002) && ($fsType & 0x0200) == 0;
    }

    /**
     * Get the fsSelection flags.
     *
     * @see http://www.microsoft.com/typography/otspec/os2.htm#fss
     * @return integer
     */
    public function getFsSelection()
    {
        if ($this->_fsSelection === null)
            $this->_parseOs2Table();

        return $this->_fsSelection;
    }

    /**
     * Checks if a font contains italic or oblique characters.
     *
     * @return boolean
     */
    public function isItalic()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 1) !== 0;
    }

    /**
     * Checks if the characters of the font are underscore.
     *
     * @return boolean
     */
    public function isUnderscore()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 2) !== 0;
    }

    /**
     * Checks if the characters have their foreground and background reversed.
     *
     * @return boolean
     */
    public function isNegative()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 4) !== 0;
    }

    /**
     * Check if the characters are outline (hollow) characters.
     *
     * @return boolean
     */
    public function isOutlined()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 8) !== 0;
    }

    /**
     * Checks if the characters is struck out.
     *
     * @return boolean
     */
    public function isStrikeout()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 16) !== 0;
    }

    /**
     * Checks if the characters are emboldened.
     *
     * @return boolean
     */
    public function isBold()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 32) !== 0;
    }

    /**
     * Checks if the characters are in the standard weight/style for the font.
     *
     * @return boolean
     */
    public function isRegular()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 64) !== 0;
    }

    /**
     * Checks if the font contains oblique characters.
     *
     * @return boolean
     */
    public function isOblique()
    {
        $fsSelection = $this->getFsSelection();
        return ($fsSelection & 512) !== 0;
    }

    /**
     * Get the xHeight.
     *
     * @param float $factor
     * @return float
     * @see http://www.microsoft.com/typography/otspec/os2.htm#xh
     */
    public function getXHeight($factor = 1.)
    {
        if ($this->_xHeight === null)
            $this->_parseOs2Table();

        return $this->_xHeight * $factor;
    }

    /**
     * Get the capital height.
     *
     * @param float $factor
     * @return float
     * @see http://www.microsoft.com/typography/otspec/os2.htm#ch
     */
    public function getCapHeight($factor = 1.)
    {
        if ($this->_capHeight === null)
            $this->_parseOs2Table();

        return $this->_capHeight * $factor;
    }

    /**
     * Get the sFamilyClass values from the OS/2 Table
     * @return array
     * @see http://www.microsoft.com/typography/otspec/os2.htm#fc
     */
    public function getFamilyClass()
    {
        if ($this->_familyClass === null)
            $this->_parseOs2Table();

        return array(
            ord($this->_familyClass[0]),
            ord($this->_familyClass[1])
        );
    }

    /**
     * Get the italic angle.
     *
     * @param float $factor
     * @return float
     */
    public function getItalicAngle($factor = 1.)
    {
        if ($this->_italicAngle === null)
            $this->_parsePostTable();

        return $this->_italicAngle * $factor;
    }

    /**
     * Get the underline position.
     *
     * @param float $factor
     * @return float
     */
    public function getUnderlinePosition($factor = 1.)
    {
        if ($this->_underlinePosition === null)
            $this->_parsePostTable();

        return $this->_underlinePosition * $factor;
    }

    /**
     * Get the suggested values for the underline thickness.
     *
     * @param float $factor
     * @return float
     */
    public function getUnderlineThickness($factor = 1.)
    {
        if ($this->_underlineThickness === null)
            $this->_parsePostTable();

        return $this->_underlineThickness * $factor;
    }

    /**
     * Check if the font is proportionally spaces (i.e. monospaced).
     *
     * @return bool
     */
    public function isFixedPitch()
    {
        if ($this->_isFixedPitch === null)
            $this->_parsePostTable();

        return $this->_isFixedPitch != 0;
    }

    /**
     * Parses the "Head" table.
     *
     * @throws SetaPDF_Core_Exception
     * @see http://www.microsoft.com/typography/otspec/head.htm
     */
    protected function _parseHeadTable()
    {
        $this->_seekTable('head');

        $this->_reader->skip(12); // version, fontRevision, checkSumAdjustment
        $magicNumber = $this->_reader->readUInt32();
        if (0x5F0F3CF5 != $magicNumber) {
            throw new SetaPDF_Core_Exception('Incorrect magic number for head-table.');
        }
        $this->_reader->skip(2); // flags
        $this->_unitsPerEm = $this->_reader->readUInt16();
        $this->_reader->skip(16); // created, modified
        $this->_xMin = $this->_reader->readInt16();
        $this->_yMin = $this->_reader->readInt16();
        $this->_xMax = $this->_reader->readInt16();
        $this->_yMax = $this->_reader->readInt16();
        $this->_macStyle = $this->_reader->readUInt16();
    }

    /**
     * Parses the "Horizontal Header" table.
     *
     * @see http://www.microsoft.com/typography/otspec/hhea.htm
     */
    protected function _parseHheaTable()
    {
        $this->_seekTable('hhea');
        $this->_reader->skip(4); // version
        $this->_ascender = $this->_reader->readInt16();
        $this->_descender = $this->_reader->readInt16();
        $this->_reader->skip(26);
        $this->_numberOfHMetrics = $this->_reader->readUInt16();
    }

    /**
     * Parses the "Maximum Profile" table.
     *
     * @see http://www.microsoft.com/typography/otspec/maxp.htm
     */
    protected function _parseMaxpTable()
    {
        $this->_seekTable('maxp');
        $this->_reader->skip(4);
        $this->_numGlyphs = $this->_reader->readUInt16();
    }

    /**
     * Parses the "Horizontal Metrics" table.
     *
     * @see http://www.microsoft.com/typography/otspec/hmtx.htm
     */
    protected function _parseHmtxTable()
    {
        if ($this->_numberOfHMetrics === null)
            $this->_parseHheaTable();

        $this->_seekTable('hmtx');
        $this->_widths = array();
        for ($i = 0; $i < $this->_numberOfHMetrics; $i++) {
            $this->_widths[$i] = $this->_reader->readUInt16();
            $this->_reader->skip(2); // lsb
        }

        $numGlyphs = $this->getNumGlyphs();
        if ($this->_numberOfHMetrics < $numGlyphs) {
            $this->_widths = array_pad($this->_widths, $numGlyphs, $this->_widths[$i - 1]);
        }
    }

    /**
     * Parses the "Name" table.
     *
     * @see http://www.microsoft.com/typography/otspec/name.htm
     */
    protected function _parseNameTable()
    {
        $this->_names = array();
        $this->_seekTable('name');
        $tableOffset = $this->_tablePositions['name'];
        $this->_reader->skip(2); // format
        $nameRecordsCount = $this->_reader->readUInt16();
        $stringOffset = $this->_reader->readUInt16();

        $namesData = array();
        for ($i = 0; $i < $nameRecordsCount; $i++) {
            $platformId = $this->_reader->readUInt16();
            $encodingId = $this->_reader->readUInt16();
            $languageId = $this->_reader->readUInt16();
            $nameId = $this->_reader->readUInt16();
            $length = $this->_reader->readUInt16();
            $offset = $this->_reader->readUInt16();
            $namesData[] = array(
                $platformId, $encodingId, $languageId,
                $nameId, $offset, $length
            );
        }

        foreach ($namesData AS $nameData) {
            $this->_reader->seek($tableOffset + $stringOffset + $nameData[4]);
            $this->_names[$nameData[0]][$nameData[1]][$nameData[2]][$nameData[3]] = $this->_reader->readBytes($nameData[5]);
        }
    }

    /**
     * Parses the "Character To Glyph Index Mapping" table.
     *
     * @see http://www.microsoft.com/typography/otspec/cmap.htm
     */
    protected function _parseCmapTable()
    {
        $this->_seekTable('cmap');
        $tableOffset = $this->_tablePositions['cmap'];
        $this->_reader->skip(2); // version
        $numTables = $this->_reader->readUInt16();

        $tables = array();
        for ($i = 0; $i < $numTables; $i++) {
            $platformId = $this->_reader->readUInt16();
            $encodingId = $this->_reader->readUInt16();
            $offset = $this->_reader->readUInt32();
            $tables[] = array($platformId, $encodingId, $offset);
        }

        // Currently we parse "all" tables... maybe this could be reduced to a unicode table
        foreach ($tables AS $cmapTable) {
            $this->_reader->seek($tableOffset + $cmapTable[2]);
            $format = $this->_reader->readUInt16();

            switch ($format) {
                case 0:

                    $this->_reader->skip(4); // length (always 262), language

                    $tmpLibrary = SetaPDF_Core_Encoding::getLibrary();
                    SetaPDF_Core_Encoding::setLibrary('iconv');
                    for ($char = 0; $char < 256; $char++) {
                        $utf16beChar = @SetaPDF_Core_Encoding::convert(chr($char), 'MACINTOSH', 'UTF-16BE');
                        if (!isset($this->_charsToGlyphs[$utf16beChar]))
                            $this->_charsToGlyphs[$utf16beChar] = ord($this->_reader->readByte());
                        else {
                            $this->_reader->skip(1);
                        }
                    }
                    SetaPDF_Core_Encoding::setLibrary($tmpLibrary);
                    break;

                case 2:
                    throw new SetaPDF_Exception_NotImplemented('Not implemented yet.');

                case 4:
                    $endCount = $startCount = array();
                    $idDelta = $idRangeOffset = array();
                    $glyphIdArray = array();

                    $length = $this->_reader->readUInt16();
                    $this->_reader->skip(2); // language
                    $segCount = $this->_reader->readUInt16() / 2;
                    $this->_reader->skip(6); // searchRange, entrySelector, rangeShift

                    for ($i = 0; $i < $segCount; $i++) {
                        $endCount[$i] = $this->_reader->readUInt16();
                    }

                    $this->_reader->skip(2); // reservedPad

                    for ($i = 0; $i < $segCount; $i++) {
                        $startCount[$i] = $this->_reader->readUInt16();
                    }

                    for ($i = 0; $i < $segCount; $i++) {
                        $idDelta[$i] = $this->_reader->readInt16();
                    }

                    for ($i = 0; $i < $segCount; $i++) {
                        $idRangeOffset[$i] = $this->_reader->readUInt16();
                    }

                    $glyphIdArrayLength = ($length - 14 - $segCount * 8 - 2) / 2;
                    for ($i = 0; $i < $glyphIdArrayLength; $i++) {
                        $glyphIdArray[$i] = $this->_reader->readUInt16();
                    }

                    for ($i = 0; $i < $segCount; $i++) {
                        for ($char = $startCount[$i]; $char <= $endCount[$i]; $char++) {
                            if (0 === $idRangeOffset[$i]) {
                                $glyph = $char;
                            } else {
                                $glyphId = (int)(($idRangeOffset[$i] / 2) + ($char - $startCount[$i]) - ($segCount - $i));
                                $glyph = $glyphIdArray[$glyphId];
                            }

                            $glyph += $idDelta[$i];
                            if ($glyph >= 65536)
                                $glyph -= 65536;

                            if ($glyph < 0) {
                                $glyph = 0;
                            }
                            $utf16beChar = SetaPDF_Core_Encoding::unicodePointToUtf16Be($char);
                            if (!isset($this->_charsToGlyphs[$utf16beChar]))
                                $this->_charsToGlyphs[$utf16beChar] = $glyph;
                        }
                    }
                    break;

                default:
                    continue;
                /*
                throw new SetaPDF_Exception_NotImplemented(
                    sprintf('Cmap with the format %s is not implemented.', $format)
                );*/

            }
        }
    }

    /**
     * Parses the "OS/2 and Windows Metrics" table.
     *
     * @see http://www.microsoft.com/typography/otspec/os2.htm
     */
    protected function _parseOs2Table()
    {
        $this->_seekTable('OS/2');
        $version = $this->_reader->readUInt16();
        $this->_reader->skip(2); // xAvgCharWidth
        $this->_usWeightClass = $this->_reader->readUInt16();
        $this->_reader->skip(2); // usWidthClass
        $this->_fsType = $this->_reader->readUInt16();
        $this->_reader->skip(10 * 2);
        $this->_familyClass = $this->_reader->readBytes(2);
        $this->_panose = $this->_reader->readBytes(10);
        $this->_reader->skip(20); // 5 * 4
        $this->_fsSelection = $this->_reader->readUInt16();

        if ($version >= 2) {
            $this->_reader->skip(22);
            $this->_xHeight = $this->_reader->readInt16();
            $this->_capHeight = $this->_reader->readInt16();
        } else {
            $this->_xHeight = 0;
            $this->_capHeight = 0;
        }
    }

    /**
     * Parses the "PostScript" table.
     *
     * @see http://www.microsoft.com/typography/otspec/post.htm
     */
    protected function _parsePostTable()
    {
        $this->_seekTable('post');
        $version = $this->_reader->readBytes(4);
        $this->_italicAngle = (float)($this->_reader->readInt16() + $this->_reader->readInt16() / 65536.0);
        $this->_underlinePosition = $this->_reader->readInt16();
        $this->_underlineThickness = $this->_reader->readInt16();
        $this->_isFixedPitch = ($this->_reader->readUInt32() !== 0);

        /*
        if ($version === "\x00\x02\x00\x00") {
            $this->_reader->skip(16);
            $numberOfGlyphs = $this->_reader->readUInt16();

            $glyphNameIndex = array();
            for ($i = 0; $i < $numberOfGlyphs; $i++) {
                $glyphNameIndex[] = $this->_reader->readUInt16();
            }
        }*/
    }

    /**
     * Set the file pointer to the start byte offset position of table.
     *
     * @param string $tag
     * @throws SetaPDF_Core_Exception
     */
    protected function _seekTable($tag)
    {
        if (!isset($this->_tablePositions[$tag])) {
            throw new SetaPDF_Core_Exception(sprintf('Could not find table "%s".', $tag));
        }

        $this->_reader->seek($this->_tablePositions[$tag]);
    }
}