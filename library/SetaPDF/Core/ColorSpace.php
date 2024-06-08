<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: ColorSpace.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * Default implementation of a color space
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage ColorSpace
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_ColorSpace
{
    /**
     * The indirect object for this color space
     *
     * @var SetaPDF_Core_Type_IndirectObjectInterface
     */
    protected $_indirectObject;

    /**
     * The main color space PDF value
     *
     * @var SetaPDF_Core_Type_Name|SetaPDF_Core_Type_Array
     */
    protected $_value;

    /**
     * Creates a color space instance based on the incoming value.
     *
     * @param string|SetaPDF_Core_Type_Name|SetaPDF_Core_Type_Array|SetaPDF_Core_Type_IndirectObjectInterface $object A color space definition
     * @return string|SetaPDF_Core_ColorSpace|SetaPDF_Core_ColorSpace_DeviceCmyk|SetaPDF_Core_ColorSpace_DeviceGray|SetaPDF_Core_ColorSpace_DeviceRgb|SetaPDF_Core_ColorSpace_IccBased|SetaPDF_Core_ColorSpace_Separation
     * @throws InvalidArgumentException
     */
    static public function createByDefinition($object)
    {
        if (is_string($object)) {
            $object = new SetaPDF_Core_Type_Name($object);
        }

        if ($object instanceof SetaPDF_Core_Type_Name) {
            $family = $object->getValue();
        } else if ($object instanceof SetaPDF_Core_Type_Array) {
            $family = $object->offsetGet(0)->getValue();
        } else if ($object instanceof SetaPDF_Core_Type_IndirectObjectInterface) {
            $family = $object->ensure()->offsetGet(0)->getValue();
        } else {
            throw new InvalidArgumentException('Argument has to be a name or array color space definition.');
        }

        switch ($family) {
            case 'DeviceGray':
                return new SetaPDF_Core_ColorSpace_DeviceGray($object);
            case 'DeviceRGB':
                return new SetaPDF_Core_ColorSpace_DeviceRgb($object);
            case 'DeviceCMYK':
                return new SetaPDF_Core_ColorSpace_DeviceCmyk($object);
            case 'ICCBased':
                return new SetaPDF_Core_ColorSpace_IccBased($object);
            case 'Separation':
                return new SetaPDF_Core_ColorSpace_Separation($object);
            case 'DeviceN':
                return new SetaPDF_Core_ColorSpace_DeviceN($object);
            case 'Indexed':
                return new SetaPDF_Core_ColorSpace_Indexed($object);

            #case 'Pattern':
            #    return new SetaPDF_Core_ColorSpace_IccBased($object);
        }

        return new SetaPDF_Core_ColorSpace($object);
    }

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Type_AbstractType $value A color space definition
     */
    public function __construct(SetaPDF_Core_Type_AbstractType $value)
    {
        if ($value instanceof SetaPDF_Core_Type_IndirectObjectInterface)
            $this->_indirectObject = $value;

        $this->_value = $value->ensure();
    }

    /**
     * Get the color space family name of this color space.
     *
     * @return string
     */
    public function getFamily()
    {
        $value = $this->getPdfValue();
        if ($value instanceof SetaPDF_Core_Type_Name) {
            return $value->getValue();
        }

        if ($value instanceof SetaPDF_Core_Type_Array) {
            return $value->offsetGet(0)->getValue();
        }
    }

    /**
     * Get the main color space PDF value.
     *
     * @return SetaPDF_Core_Type_Name|SetaPDF_Core_Type_Array
     */
    public function getPdfValue()
    {
        return $this->_value;
    }
}