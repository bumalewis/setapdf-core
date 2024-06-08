<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: Page.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * Class representing a PDF page
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Document_Page implements SetaPDF_Core_Canvas_ContainerInterface
{
    /**
     * The page indirect object
     *
     * @var SetaPDF_Core_Type_IndirectObject
     */
    protected $_pageObject;

    /**
     * Inherited attributes
     *
     * @var array An array of SetaPDF_Core_Type_Dictionary_Entry instances
     */
    protected $_inheritedAttributes = array();

    /**
     * Flag for resolving of inherited attributes
     *
     * @var boolean
     */
    protected $_inheritedAttributesResolved = false;

    /**
     * Flag for observing the page object
     *
     * @var boolean
     */
    protected $_pageIsObserved = false;

    /**
     * The annotations object
     *
     * @var SetaPDF_Core_Document_Page_Annotations
     */
    protected $_annotations;

    /**
     * The contents object for this page
     *
     * @var SetaPDF_Core_Document_Page_Contents
     */
    protected $_contents;

    /**
     * The canvas object of this page
     *
     * @var SetaPDF_Core_Canvas
     */
    protected $_canvas;

    /**
     * The additional actions object of this page
     *
     * @var SetaPDF_Core_Document_Page_AdditionalActions
     */
    protected $_additionalActions;

    /**
     * Creates a new page for a specific document.
     *
     * @param SetaPDF_Core_Document $document
     * @param array $values
     * @return SetaPDF_Core_Document_Page
     */
    static public function create(SetaPDF_Core_Document $document, $values = array())
    {
        if ($values instanceof SetaPDF_Core_Type_Dictionary) {
            $page = $values;
        } else {
            $page = new SetaPDF_Core_Type_Dictionary();
            $page->offsetSet('Type', new SetaPDF_Core_Type_Name('Page', true));

            foreach ($values AS $value) {
                $page->offsetSet(null, $value);
            }
        }

        // Add required resource dictionary
        if (!$page->offsetExists('Resources')) {
            $page->offsetSet('Resources', new SetaPDF_Core_Type_Dictionary());
        }

        return new self($document->createNewObject($page));
    }

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Type_IndirectObject $pageObject
     * @throws SetaPDF_Core_Exception
     */
    public function __construct(SetaPDF_Core_Type_IndirectObject $pageObject)
    {
        if (!$pageObject->ensure() instanceof SetaPDF_Core_Type_Dictionary) {
            throw new SetaPDF_Core_Exception(
                'Errorious object passed to constructor. This leads to an corrupted PDF document.'
            );
        }

        // TODO: Check for default/required keys

        $this->_pageObject = $pageObject;
    }

    /**
     * Release memory/resources.
     */
    public function cleanUp()
    {
        $this->_pageObject = null;
        if (null !== $this->_annotations) {
            $this->_annotations->cleanUp();
            $this->_annotations = null;
        }

        if (null !== $this->_contents) {
            $this->_contents->cleanUp();
            $this->_contents = null;
        }

        if (null !== $this->_canvas) {
            $this->_canvas->cleanUp();
            $this->_canvas = null;
        }

        if (null !== $this->_additionalActions) {
            $this->_additionalActions->cleanUp();
            $this->_additionalActions = null;
        }
    }

    /**
     * Get the page indirect object.
     *
     * @param boolean $observe
     * @return SetaPDF_Core_Type_IndirectObject
     */
    public function getPageObject($observe = false)
    {
        if (true === $observe) {
            $this->_ensureObservation();
        }
        return $this->_pageObject;
    }

    /**
     * Get the page object.
     * 
     * @param bool $observe
     * @return SetaPDF_Core_Type_IndirectObject
     */
    public function getObject($observe = false)
    {
        return $this->getPageObject($observe);
    }

    /**
     * Get the pages stream proxy object.
     * 
     * @return SetaPDF_Core_Document_Page_Contents
     */
    public function getStreamProxy()
    {
        return $this->getContents();
    }

    /**
     * Ensures that all inherited properties are resolved.
     */
    protected function _ensureInheritedAttributes()
    {
        if ($this->_inheritedAttributesResolved)
            return;

        $pageDict = $this->_pageObject->ensure(true);
        $needed = array();
        foreach (array(
                     'Resources', 'MediaBox', 'CropBox', 'Rotate'
                 ) as $key) {
            if (!$pageDict->offsetExists($key))
                $needed[$key] = true;
        }

        if (count($needed) > 0) {
            $parentDict = $pageDict->offsetGet('Parent')->ensure(true);

            while ($parentDict instanceof SetaPDF_Core_Type_Dictionary && count($needed) > 0) {
                foreach (array_keys($needed) AS $key) {
                    if ($parentDict->offsetExists($key)) {
                        $this->_inheritedAttributes[$key] = $parentDict->offsetGet($key);
                        unset($needed[$key]);
                    }
                }

                if (count($needed) > 0 && $parentDict->offsetExists('Parent')) {
                    $parentDict = $parentDict->offsetGet('Parent')->ensure(true);
                } else {
                    break;
                }
            }
        }

        $this->_inheritedAttributesResolved = true;
    }

    /**
     * Get an attribute of the page object or from an inherited pages object.
     *
     * @param string $name
     * @param bool $inherited
     * @return SetaPDF_Core_Type_AbstractType|null
     */
    public function getAttribute($name, $inherited = true)
    {
        $pageDict = $this->_pageObject->ensure(true);

        if ($pageDict->offsetExists($name))
            return $pageDict->offsetGet($name);

        if ($inherited &&
            (
                $name === 'Resources' || $name === 'MediaBox' ||
                $name === 'CropBox' || $name === 'Rotate'
            )
        ) {
            $this->_ensureInheritedAttributes();
            if (isset($this->_inheritedAttributes[$name]))
                return $this->_inheritedAttributes[$name];
        }

        return null;
    }

    /**
     * Make sure that the page object is observed.
     */
    protected function _ensureObservation()
    {
        if (false === $this->_pageIsObserved) {
            $this->_pageObject->observe();
            $this->_pageIsObserved = true;
        }
    }

    /**
     * Flattens the inherited attributes to the main page object.
     */
    public function flattenInheritedAttributes()
    {
        $this->_ensureInheritedAttributes();

        if (0 === count($this->_inheritedAttributes))
            return;

        $pageDict = $this->getPageObject(true)->ensure(true);
        foreach ($this->_inheritedAttributes AS $entry) {
            $pageDict->offsetSet(null, clone $entry);
        }
    }

    /**
     * Get width and height of the page.
     *
     * @param string $box
     * @param boolean $fallback
     * @return array|boolean array(width, height)
     */
    public function getWidthAndHeight($box = SetaPDF_Core_PageBoundaries::CROP_BOX, $fallback = true)
    {
        $boundary = $this->getBoundary($box, $fallback, true);
        if (false === $boundary)
            return false;

        $rotation = $this->getRotation();
        $interchange = ($rotation / 90) % 2;

        return array(
            $interchange ? $boundary->getHeight() : $boundary->getWidth(),
            $interchange ? $boundary->getWidth() : $boundary->getHeight()
        );
    }

    /**
     * Get the width of the page.
     *
     * @param string $box
     * @param boolean $fallback
     * @return float|integer|boolean
     */
    public function getWidth($box = SetaPDF_Core_PageBoundaries::CROP_BOX, $fallback = true)
    {
        $widthAndHeight = $this->getWidthAndHeight($box, $fallback);
        return $widthAndHeight ? $widthAndHeight[0] : false;
    }

    /**
     * Get the height of the page.
     *
     * @param string $box
     * @param boolean $fallback
     * @return float|integer|boolean
     */
    public function getHeight($box = SetaPDF_Core_PageBoundaries::CROP_BOX, $fallback = true)
    {
        $widthAndHeight = $this->getWidthAndHeight($box, $fallback);
        return $widthAndHeight ? $widthAndHeight[1] : false;
    }

    /**
     * Get a page boundary box of the page.
     *
     * To work with the boundary box it should be cloned and reset by the
     * {@link SetaPDF_Core_Document_Page::setBoundary()} method. This is
     * necessary because a box could be inherited by a parent page tree node.
     *
     * @param string $box See SetaPDF_Core_PageBoundaries::XXX_BOX constants
     * @param boolean $fallback Use the fallback box instead if box not exist
     * @param boolean $asRect Return boundary box as SetaPDF_Core_DataStructure_Rectangle
     * @return boolean|SetaPDF_Core_DataStructure_Rectangle|SetaPDF_Core_Type_AbstractType
     */
    public function getBoundary($box = SetaPDF_Core_PageBoundaries::CROP_BOX, $fallback = true, $asRect = true)
    {
        $value = $this->getAttribute($box);

        if (null !== $value) {
            if ($asRect) {
                return new SetaPDF_Core_DataStructure_Rectangle($value->ensure());
            }
            return $value;
        }

        //No fallback
        if (false == $fallback) {
            return false;

        //$box is bleed, trim or art box
        } else if (
            $box === SetaPDF_Core_PageBoundaries::BLEED_BOX ||
            $box === SetaPDF_Core_PageBoundaries::TRIM_BOX ||
            $box === SetaPDF_Core_PageBoundaries::ART_BOX
        ) {
            return $this->getBoundary(SetaPDF_Core_PageBoundaries::CROP_BOX, true, $asRect);

        //$box is crop box
        } else if ($box == SetaPDF_Core_PageBoundaries::CROP_BOX) {
            return $this->getBoundary(SetaPDF_Core_PageBoundaries::MEDIA_BOX, true, $asRect);

        } else {
            return false;
        }
    }

    /**
     * Checks a boundary for validity.
     * 
     * @param SetaPDF_Core_DataStructure_Rectangle|SetaPDF_Core_Type_Array $newBoundary
     * @param string $newBox
     * @throws OutOfBoundsException
     */
    private function _checkBoundary($newBoundary, $newBox)
    {
        if(!($newBoundary instanceof SetaPDF_Core_DataStructure_Rectangle)) {
            $newBoundary = new SetaPDF_Core_DataStructure_Rectangle($newBoundary);
        }

        if($newBox == SetaPDF_Core_PageBoundaries::MEDIA_BOX) {
            $artBox = $this->getBoundary(SetaPDF_Core_PageBoundaries::ART_BOX, false, true);
            if($artBox !== false && !$newBoundary->contains($artBox)) {
                throw new OutOfBoundsException('new MediaBox wouldn\'t be in the ArtBox');
            }

            $bleedBox = $this->getBoundary(SetaPDF_Core_PageBoundaries::BLEED_BOX, false, true);
            if($bleedBox !== false && !$newBoundary->contains($bleedBox)) {
                throw new OutOfBoundsException('new MediaBox wouldn\'t be in the BleedBox');
            }

            $cropBox = $this->getBoundary(SetaPDF_Core_PageBoundaries::CROP_BOX, false, true);
            if($cropBox !== false && !$newBoundary->contains($cropBox)) {
                throw new OutOfBoundsException('new MediaBox wouldn\'t be in the CropBox');
            }

            $trimBox = $this->getBoundary(SetaPDF_Core_PageBoundaries::TRIM_BOX, false, true);
            if($trimBox !== false && !$newBoundary->contains($trimBox)) {
                throw new OutOfBoundsException('new MediaBox wouldn\'t be in the TrimBox');
            }
        } else {
            $mediaBox = $this->getBoundary(SetaPDF_Core_PageBoundaries::MEDIA_BOX, true, true);

            if(!$mediaBox->contains($newBoundary)) {
                throw new OutOfBoundsException('new ' . $newBox . ' wouldn\'t be in the MediaBox');
            }
        }
    }

    /**
     * Set a boundary box.
     *
     * A boundary consists of four numeric values: llx, lly, urx and ury. They can be passed in various ways:
     *
     * <ul>
     *  <li>By a simple PHP array.</li>
     *  <li>A {@link SetaPDF_Core_Type_Array PDF Array} with 4 {@link SetaPDF_Core_Type_Numeric numeric} values.</li>
     *  <li>An instance of {@link SetaPDF_Core_DataStructure_Rectangle}.</li>
     *  <li>
     *     An instance of {@link SetaPDF_Core_Type_Dictionary_Entry} where the key defines the box and the value the
     *     boundary itself.
     *  </li>
     * </ul>
     *
     * @param array|SetaPDF_Core_Type_Dictionary_Entry|SetaPDF_Core_Type_Array|SetaPDF_Core_DataStructure_Rectangle $boundary
     *          The data of the boundary.
     * @param string $box The page boundary name
     * @param boolean $checkBoundary Ensure that boundary values are valid or not
     * @throws InvalidArgumentException
     */
    public function setBoundary($boundary, $box = SetaPDF_Core_PageBoundaries::CROP_BOX, $checkBoundary = true)
    {
        $document = $this->_pageObject->getOwnerPdfDocument();
        SetaPDF_Core_SecHandler::checkPermission($document, SetaPDF_Core_SecHandler::PERM_ASSEMBLE);

        $pageDict = $this->getPageObject(true)->ensure(true);

        if (is_array($boundary)) {
            $boundary = SetaPDF_Core_DataStructure_Rectangle::byArray($boundary, true);
        }

        if ($boundary instanceof SetaPDF_Core_Type_Dictionary_Entry) {
            if ($checkBoundary) {
                $this->_checkBoundary($boundary->getValue(), $boundary->getKeyValue());
            }
            $pageDict->offsetSet(null, $boundary);

        } elseif ($boundary instanceof SetaPDF_Core_Type_Array) {
            if ($checkBoundary) {
                $this->_checkBoundary($boundary, $box);
            }
            $pageDict->offsetSet($box, $boundary);

        } elseif ($boundary instanceof SetaPDF_Core_DataStructure_Rectangle) {
            if ($checkBoundary) {
                $this->_checkBoundary($boundary, $box);
            }
            $pageDict->offsetSet($box, $boundary->getValue());

        } elseif ($boundary === null) {
            if($box == SetaPDF_Core_PageBoundaries::MEDIA_BOX) {
                throw new InvalidArgumentException('Deleting the MediaBox isn\'t possible');
            }

            $pageDict->offsetUnset($box);

        } else {
            throw new InvalidArgumentException(
                'Argument have to be an instance of SetaPDF_Core_Type_Dictionary_Entry, ' .
                'SetaPDF_Core_Type_Array or SetaPDF_Core_DataStructure_Rectangle'
            );
        }
    }

    /**
     * Get the media box of this page.
     * 
     * @param bool $fallback
     * @param bool $asRect
     * @return bool|SetaPDF_Core_DataStructure_Rectangle|SetaPDF_Core_Type_AbstractType
     */
    public function getMediaBox($fallback = true, $asRect = true)
    {
        return $this->getBoundary(SetaPDF_Core_PageBoundaries::MEDIA_BOX, $fallback, $asRect);
    }

    /**
     * Set the media box.
     * 
     * @param array|SetaPDF_Core_Type_Dictionary_Entry|SetaPDF_Core_Type_Array|SetaPDF_Core_DataStructure_Rectangle $boundary
     * @param boolean $checkBoundary Ensure that boundary values are valid or not
     */
    public function setMediaBox($boundary, $checkBoundary = true)
    {
        $this->setBoundary($boundary, SetaPDF_Core_PageBoundaries::MEDIA_BOX, $checkBoundary);
    }

    /**
     * Get the crop box of this page.
     * 
     * @param bool $fallback
     * @param bool $asRect
     * @return bool|SetaPDF_Core_DataStructure_Rectangle|SetaPDF_Core_Type_AbstractType
     */
    public function getCropBox($fallback = true, $asRect = true)
    {
        return $this->getBoundary(SetaPDF_Core_PageBoundaries::CROP_BOX, $fallback, $asRect);
    }

    /**
     * Set the crop box.
     * 
     * @param array|SetaPDF_Core_Type_Dictionary_Entry|SetaPDF_Core_Type_Array|SetaPDF_Core_DataStructure_Rectangle $boundary
     * @param boolean $checkBoundary Ensure that boundary values are valid or not
     */
    public function setCropBox($boundary, $checkBoundary = true)
    {
        $this->setBoundary($boundary, SetaPDF_Core_PageBoundaries::CROP_BOX, $checkBoundary);
    }

    /**
     * Get the bleed box of this page.
     * 
     * @param bool $fallback
     * @param bool $asRect
     * @return bool|SetaPDF_Core_DataStructure_Rectangle|SetaPDF_Core_Type_AbstractType
     */
    public function getBleedBox($fallback = true, $asRect = true)
    {
        return $this->getBoundary(SetaPDF_Core_PageBoundaries::BLEED_BOX, $fallback, $asRect);
    }

    /**
     * Set the bleed box.
     * 
     * @param array|SetaPDF_Core_Type_Dictionary_Entry|SetaPDF_Core_Type_Array|SetaPDF_Core_DataStructure_Rectangle $boundary
     * @param boolean $checkBoundary Ensure that boundary values are valid or not
     */
    public function setBleedBox($boundary, $checkBoundary = true)
    {
        $this->setBoundary($boundary, SetaPDF_Core_PageBoundaries::BLEED_BOX, $checkBoundary);
    }

    /**
     * Get the trim box of this page.
     * 
     * @param bool $fallback
     * @param bool $asRect
     * @return bool|SetaPDF_Core_DataStructure_Rectangle|SetaPDF_Core_Type_AbstractType
     */
    public function getTrimBox($fallback = true, $asRect = true)
    {
        return $this->getBoundary(SetaPDF_Core_PageBoundaries::TRIM_BOX, $fallback, $asRect);
    }

    /**
     * Set the trim box.
     * 
     * @param array|SetaPDF_Core_Type_Dictionary_Entry|SetaPDF_Core_Type_Array|SetaPDF_Core_DataStructure_Rectangle $boundary
     * @param boolean $checkBoundary Ensure that boundary values are valid or not
     */
    public function setTrimBox($boundary, $checkBoundary = true)
    {
        $this->setBoundary($boundary, SetaPDF_Core_PageBoundaries::TRIM_BOX, $checkBoundary);
    }

    /**
     * Get the art box of this page.
     * 
     * @param bool $fallback
     * @param bool $asRect
     * @return bool|SetaPDF_Core_DataStructure_Rectangle|SetaPDF_Core_Type_AbstractType
     */
    public function getArtBox($fallback = true, $asRect = true)
    {
        return $this->getBoundary(SetaPDF_Core_PageBoundaries::ART_BOX, $fallback, $asRect);
    }

    /**
     * Set the art box.
     * 
     * @param array|SetaPDF_Core_Type_Dictionary_Entry|SetaPDF_Core_Type_Array|SetaPDF_Core_DataStructure_Rectangle $boundary
     */
    public function setArtBox($boundary)
    {
        $this->setBoundary($boundary, SetaPDF_Core_PageBoundaries::ART_BOX);
    }

    /**
     * Get the page rotation.
     *
     * @return integer
     */
    public function getRotation()
    {
        $rotate = $this->getAttribute('Rotate');
        if (!$rotate)
            return 0;

        $rotation = $rotate->getValue()->ensure()->getValue() % 360;

        if ($rotation < 0)
            $rotation = $rotation + 360;
        
        return $rotation;
    }

    /**
     * Set the page rotation.
     *
     * @param integer $rotation The rotation value
     * @return SetaPDF_Core_Document_Page Returns the SetaPDF_Core_Document_Page object for method chaining.
     * @throws InvalidArgumentException
     */
    public function setRotation($rotation)
    {
        if (($rotation % 90) !== 0) {
            throw new InvalidArgumentException('The page rotation value has to be a multiple of 90.');
        }

        $document = $this->_pageObject->getOwnerPdfDocument();
        SetaPDF_Core_SecHandler::checkPermission($document, SetaPDF_Core_SecHandler::PERM_ASSEMBLE);

        $rotation = $rotation % 360;

        $pageDict = $this->getPageObject(true)->ensure(true);

        $pageDict->offsetSet(null, new SetaPDF_Core_Type_Dictionary_Entry(
            new SetaPDF_Core_Type_Name('Rotate', true),
            new SetaPDF_Core_Type_Numeric($rotation)
        ));

        return $this;
    }

    /**
     * Rotate a page by degrees.
     *
     * @param integer $rotation Degrees to rotate by
     * @return SetaPDF_Core_Document_Page Returns the SetaPDF_Core_Document_Page object for method chaining.
     */
    public function rotateBy($rotation)
    {
        $currentRotation = $this->getRotation();
        $this->setRotation($currentRotation + $rotation);

        return $this;
    }

    /**
     * Gets the annotation instance of this page.
     *
     * @return SetaPDF_Core_Document_Page_Annotations
     */
    public function getAnnotations()
    {
        if (null === $this->_annotations)
            $this->_annotations = new SetaPDF_Core_Document_Page_Annotations($this);

        return $this->_annotations;
    }

    /**
     * Gets the contents instance of this page.
     *
     * @return SetaPDF_Core_Document_Page_Contents
     */
    public function getContents()
    {
        if (null === $this->_contents)
            $this->_contents = new SetaPDF_Core_Document_Page_Contents($this);

        return $this->_contents;
    }

    /**
     * Gets the canvas instance for this page.
     *
     * @return SetaPDF_Core_Canvas
     */
    public function getCanvas()
    {
        if (null === $this->_canvas)
            $this->_canvas = new SetaPDF_Core_Canvas($this);

        return $this->_canvas;
    }

    /**
     * Gets the additional actions object instance for this page.
     *
     * @return SetaPDF_Core_Document_Page_AdditionalActions
     */
    public function getAdditionalActions()
    {
        if (null === $this->_additionalActions)
            $this->_additionalActions = new SetaPDF_Core_Document_Page_AdditionalActions($this);

        return $this->_additionalActions;
    }

    /**
     * Get the date and time the page was edited.
     *
     * @param boolean $asString
     * @return null|string|SetaPDF_Core_DataStructure_Date
     */
    public function getLastModified($asString = true)
    {
        $lastModified = $this->getAttribute('LastModified', false);
        if (null === $lastModified)
            return null;

        if (true === $asString) {
            return $lastModified->ensure()->getValue();
        }

        return new SetaPDF_Core_DataStructure_Date($lastModified->ensure());
    }

    /**
     * Set the date and time the page was edited.
     *
     * @param string|SetaPDF_Core_DataStructure_Date $date The last modification date. An instance of
     *          {@link SetaPDF_Core_DataStructure_Date}. Alternatively a string which is passed to its constructor.
     */
    public function setLastModified($date)
    {
        $pageDict = $this->getPageObject(true)->ensure(true);

        if (null === $date) {
            $pageDict->offsetUnset('LastModified');
            return;
        }

        if (!($date instanceof SetaPDF_Core_DataStructure_Date))
            $date = new SetaPDF_Core_DataStructure_Date($date);

        $pageDict->offsetSet('CreationDate', $date->getValue());
    }
    
    /**
     * Get a group attributes object.
     *
     * @return null|SetaPDF_Core_TransparencyGroup
     */
    public function getGroup()
    {
        $pageDict = $this->getPageObject(true)->ensure(true);
        if (!$pageDict->offsetExists('Group')) {
            return null;
        }
    
        return new SetaPDF_Core_TransparencyGroup($pageDict->getValue('Group'));
    }
    
    /**
     * Set the group attributes object.
     *
     * @param false|SetaPDF_Core_TransparencyGroup $group
     * @throws InvalidArgumentException
     */
    public function setGroup($group)
    {
        $pageDict = $this->getPageObject(true)->ensure(true);
        if (false === $group) {
            $pageDict->offsetUnset('Group');
            return;
        }
    
        if (!$group instanceof SetaPDF_Core_TransparencyGroup) {
            throw new InvalidArgumentException('Group parameter has to be an instance of SetaPDF_Core_TransparencyGroup');
        }
    
        $pageDict['Group'] = $group->getDictionary();
    }
    
    /**
     * Converts the page object into a form XObject.
     * 
     * @param SetaPDF_Core_Document $document
     * @param string $box The name of the bounding box
     * @return SetaPDF_Core_XObject_Form
     */
    public function toXObject(SetaPDF_Core_Document $document, $box = SetaPDF_Core_PageBoundaries::CROP_BOX)
    {
        $dict = new SetaPDF_Core_Type_Dictionary();
        $dict->offsetSet('Type', new SetaPDF_Core_Type_Name('XObject', true));
        $dict->offsetSet('Subtype', new SetaPDF_Core_Type_Name('Form', true));
        $dict->offsetSet('BBox', new SetaPDF_Core_Type_Array(array(
            new SetaPDF_Core_Type_Numeric(0),
            new SetaPDF_Core_Type_Numeric(0),
            new SetaPDF_Core_Type_Numeric($this->getWidth($box)),
            new SetaPDF_Core_Type_Numeric($this->getHeight($box))
        )));
        $resources = $this->getAttribute('Resources', true);
        if ($resources)
            $dict->offsetSet(null, clone $resources);

        $contents = $this->getContents();

        if ($contents->count() === 1) {
            $stream = clone $contents->getStreamObject();
            $filter = $stream->getValue()->offsetGet('Filter');
            if ($filter) {
                $filter = clone $filter;
                $dict->offsetSet(null, $filter);
            }
            $stream->setValue($dict);
            
        } else {
            $dict->offsetSet('Filter', new SetaPDF_Core_Type_Name('FlateDecode', true));
            $stream = new SetaPDF_Core_Type_Stream($dict);
            $stream->setStream($contents->getStream());
        }

        $bbox = $this->getBoundary($box);
        // Add outer matrix
        $r = deg2rad(-$this->getRotation());
        $a = $d = cos($r);
        $b = sin($r);
        $c = -$b;
        $e = $bbox->getLlx() * -1;
        $f = $bbox->getLly() * -1; // translate x, translate y

        $height = $this->getHeight($box);
        $width = $this->getWidth($box);
        if ($a == -1) {
            $e = $bbox->getLlx();
            $e += $width;
            $f = $bbox->getLly();
            $f += $height;
        }

        if ($b == 1) {
            $e = $bbox->getLly();
            $e += $width;
            $f = -$bbox->getLlx();
        }

        if ($c == 1) {
            $f = $bbox->getLlx();
            $f += $height;
            $e = -$bbox->getLly();
        }

        if ($a != 1 || $b != 0 || $c != 0 || $d != 1 || $e != 0 || $f != 0) {
            $start = sprintf(' q %.5F %.5F %.5F %.5F %.2F %.2F cm ', $a, $b, $c, $d, $e, $f);
            $end = ' Q';

            $stream->setStream($start . $stream->getStream() . $end);
        }
        $object = $document->createNewObject($stream);
        
        return SetaPDF_Core_XObject::get($object);
    }
}