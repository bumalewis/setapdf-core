<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Reader
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: Binary.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * Class for a binary reader
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Reader
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Reader_Binary
{
    const BYTE_ORDER_BIG_ENDIAN = 'bigEndian';
    const BYTE_ORDER_LITTLE_ENDIAN = 'littleEndian';

    /**
     * @var SetaPDF_Core_Reader_ReaderInterface
     */
    protected $_reader;

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Reader_ReaderInterface $reader
     */
    public function __construct(SetaPDF_Core_Reader_ReaderInterface $reader)
    {
        $this->_reader = $reader;
    }

    /**
     * Release resources/cycled references.
     */
    public function cleanUp()
    {
        $this->_reader->cleanUp();
        $this->_reader = null;
    }

    /**
     * Get the reader.
     *
     * @return SetaPDF_Core_Reader_ReaderInterface
     */
    public function getReader()
    {
        return $this->_reader;
    }

    /**
     * Reads a 8-bit/1-byte signed integer.
     *
     * @return integer
     */
    public function readInt8()
    {
        $t = unpack('c', $this->_reader->readByte());
        return current($t);
    }

    /**
     * Reads a 8-bit/1-byte unsigned integer.
     *
     * @return integer
     */
    public function readUInt8()
    {
        $t = unpack('C', $this->_reader->readByte());
        return current($t);
    }

    /**
     * Reads a 16-bit signed integer.
     *
     * @param string $byteOrder
     * @return integer
     */
    public function readInt16($byteOrder = self::BYTE_ORDER_BIG_ENDIAN)
    {
        $value = $this->readUInt16($byteOrder);
        if ($value >= 0x8000)
            $value -= 65536;

        return $value;
    }

    /**
     * Reads a 16-bit unsigned integer.
     *
     * @param string $byteOrder
     * @return integer
     */
    public function readUInt16($byteOrder = self::BYTE_ORDER_BIG_ENDIAN)
    {
        $bytes = $this->_reader->readBytes(2);
        $bytesArr = unpack($byteOrder == self::BYTE_ORDER_BIG_ENDIAN ? 'n' : 'v', $bytes);
        return current($bytesArr);
    }

    /**
     * Reads a 32-bit signed integer.
     *
     * @param string $byteOrder
     * @return mixed
     */
    public function readInt32($byteOrder = self::BYTE_ORDER_BIG_ENDIAN)
    {
        $value = $this->readUInt32($byteOrder);
        if ($value >= 0x80000000)
            $value -= 4294967296;

        return $value;
    }

    /**
     * Reads a 32-bit unsigned integer.
     *
     * @param string $byteOrder
     * @return mixed
     */
    public function readUInt32($byteOrder = self::BYTE_ORDER_BIG_ENDIAN)
    {
        $bytes = $this->_reader->readBytes(4);

        return $this->_uint32($bytes, $byteOrder);
    }

    /**
     * @see http://www.php.net/function.unpack.php#106041
     * @param string $bin Binary string
     * @param string $byteOrder Byte Order, use BYTE_ORDER_XXX constant
     * @return mixed
     * @internal
     */
    private function _uint32($bin, $byteOrder = self::BYTE_ORDER_BIG_ENDIAN)
    {
        // $bin is the binary 32-bit BE string that represents the integer
        if (PHP_INT_SIZE <= 4) {
            $isBigEndian = (bool)($byteOrder == self::BYTE_ORDER_BIG_ENDIAN);

            if ($isBigEndian) {
                list(, $h, $l) = unpack('n*', $bin);
            } else {
                list(, $l, $h) = unpack('v*', $bin);
            }
            //     ($l | ($h << 16))
            return ($l + ($h * 0x010000));
        } else {
            list(, $int) = unpack(($byteOrder == self::BYTE_ORDER_BIG_ENDIAN ? 'N' : 'V'), $bin);
            return $int;
        }
    }

    /**
     * Read a single byte.
     *
     * @return string
     */
    public function readByte()
    {
        return $this->_reader->readByte();
    }

    /**
     * Read a specific amount of bytes.
     *
     * @param integer $length
     * @return string
     */
    public function readBytes($length)
    {
        return $this->_reader->readBytes($length);
    }

    /**
     * Reset the reader to a specific position.
     *
     * @param integer $position
     * @param integer $length
     */
    public function reset($position, $length)
    {
        $this->_reader->reset($position, $length);
    }

    /**
     * Seek to a position.
     *
     * @param integer $position
     */
    public function seek($position)
    {
        $this->_reader->reset($position);
    }

    /**
     * Skip a specific byte count.
     *
     * @param integer $length
     */
    public function skip($length)
    {
        $currentPos = $this->_reader->getPos();
        $currentOffset = $this->_reader->getOffset();
        $this->_reader->reset($currentPos + $currentOffset + $length);
    }
}