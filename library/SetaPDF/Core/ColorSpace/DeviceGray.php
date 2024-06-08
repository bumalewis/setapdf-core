<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: DeviceGray.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * DeviceGray Color Space
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage ColorSpace
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_ColorSpace_DeviceGray
    extends SetaPDF_Core_ColorSpace
{
    /**
     * Creates an instance of this color space.
     *
     * @return SetaPDF_Core_ColorSpace_DeviceCmyk
     */
    static public function create()
    {
        return new self(new SetaPDF_Core_Type_Name('DeviceGray'));
    }

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Type_AbstractType $name
     * @throws InvalidArgumentException
     */
    public function __construct(SetaPDF_Core_Type_AbstractType $name)
    {
        parent::__construct($name);

        if ($this->getPdfValue()->getValue() !== 'DeviceGray') {
            throw new InvalidArgumentException('DeviceGray color space has to be named "DeviceGray".');
        }
    }
}