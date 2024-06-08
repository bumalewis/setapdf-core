<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: IccBased.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * ICCBased Color Space
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage ColorSpace
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_ColorSpace_IccBased
    extends SetaPDF_Core_ColorSpace
    implements SetaPDF_Core_Resource
{
    /**
     * An array caching profile stream objects.
     *
     * @var array
     */
    static $_profileStreams = array();

    /**
     * Creates an instance of this color space.
     *
     * @param SetaPDF_Core_IccProfile_Stream $iccStream
     * @return SetaPDF_Core_ColorSpace_IccBased
     */
    static public function create(SetaPDF_Core_IccProfile_Stream $iccStream)
    {
        return new self(new SetaPDF_Core_Type_Array(array(
            new SetaPDF_Core_Type_Name('ICCBased'),
            $iccStream->getIndirectObject()
        )));
    }

    /**
     * Release profile stream instances by a document instance.
     *
     * @param SetaPDF_Core_Document $document
     */
    static public function freeCache(SetaPDF_Core_Document $document)
    {
        unset(self::$_profileStreams[$document->getInstanceIdent()]);
    }

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Type_AbstractType $definition
     * @throws InvalidArgumentException
     */
    public function __construct(SetaPDF_Core_Type_AbstractType $definition)
    {
        parent::__construct($definition);

        $definition = $this->getPdfValue();

        if ($definition->offsetGet(0)->getValue() !== 'ICCBased') {
            throw new InvalidArgumentException('ICCBased color space has to be named "ICCBased".');
        }

        if ($definition->offsetGet(1)->ensure() instanceof SetaPDF_Core_Type_IndirectObjectInterface) {
            throw new InvalidArgumentException("ICCBased color space needs a ICC profile in it's definition.");
        }
    }

    /**
     * Get an instance of the ICC Profile stream.
     *
     * @return SetaPDF_Core_IccProfile_Stream
     */
    public function getIccProfileStream()
    {
        $indirectObject = $this->getPdfValue()->offsetGet(1);

        $ident = $indirectObject->getObjectIdent();
        $documentId = $indirectObject->getOwnerPdfDocument()->getInstanceIdent();
        if (isset(self::$_profileStreams[$documentId][$ident])) {
            return self::$_profileStreams[$documentId][$ident];
        }

        self::$_profileStreams[$documentId][$ident] = new SetaPDF_Core_IccProfile_Stream($indirectObject);

        return self::$_profileStreams[$documentId][$ident];
    }

    /**
     * Gets an indirect object for this color space dictionary.
     *
     * @see SetaPDF_Core_Resource::getIndirectObject()
     * @param SetaPDF_Core_Document $document
     * @return SetaPDF_Core_Type_IndirectObjectInterface
     * @throws InvalidArgumentException
     */
    public function getIndirectObject(SetaPDF_Core_Document $document = null)
    {
        if (null === $this->_indirectObject) {
            if (null === $document) {
                throw new InvalidArgumentException('To initialize a new object $document parameter is not optional!');
            }

            $this->_indirectObject = $document->createNewObject($this->getPdfValue());
        }

        return $this->_indirectObject;
    }

    /**
     * Get the resource type of an implementation.
     *
     * @return string
     */
    public function getResourceType()
    {
        return SetaPDF_Core_Resource::TYPE_COLOR_SPACE;
    }
}