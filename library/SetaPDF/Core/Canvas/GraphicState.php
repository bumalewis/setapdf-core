<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: GraphicState.php 851 2016-05-28 17:08:59Z jan.slabon $
 */

/**
 * A canvas helper class for graphicState operators
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Canvas
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Canvas_GraphicState
{
    /**
     * The maximum nesting level of the graphic states (default = 28).
     *
     * @var integer
     * @see PDF 32000-1:2008 - C.2 Architectural limits
     */
    static protected $_maxGraphicStateNestingLevel = 28;

    /**
     * Stack of all opened or closed graphic states.
     *
     * @var array
     */
    protected $_stack = array();

    /**
     * Text state helper
     *
     * @var SetaPDF_Core_Canvas_GraphicState_Text
     */
    protected $_text;

    /**
     * Set the maximum nesting level of graphic states.
     *
     * @param integer $maxGraphicStateNestingLevel
     */
    static public function setMaxGraphicStateNestingLevel($maxGraphicStateNestingLevel)
    {
        self::$_maxGraphicStateNestingLevel = (int)$maxGraphicStateNestingLevel;
    }

    /**
     * Get the maximum nesting level of graphic states.
     *
     * @return integer
     */
    static public function getMaxGraphicStateNestingLevel()
    {
        return self::$_maxGraphicStateNestingLevel;
    }

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Geometry_Matrix $matrix
     */
    public function __construct(SetaPDF_Core_Geometry_Matrix $matrix = null)
    {
        $this->_stack[] = array(
            'matrix' => $matrix === null ? new SetaPDF_Core_Geometry_Matrix() : $matrix
        );
    }

    /**
     * Get the current state of the stack.
     *
     * @return mixed
     */
    protected function &_getCurrent()
    {
        return $this->_stack[count($this->_stack) - 1];
    }

    /**
     * Add a transformation matrix to the stack of the current graphic state.
     *
     * @see PDF-Reference PDF 32000-1:2008 8.3.4 Transformation Matrices
     * @param int|float $a
     * @param int|float $b
     * @param int|float $c
     * @param int|float $d
     * @param int|float $e
     * @param int|float $f
     */
    public function addCurrentTransformationMatrix($a, $b, $c, $d, $e, $f)
    {
        $matrix = new SetaPDF_Core_Geometry_Matrix($a, $b, $c, $d, $e, $f);

        $current =& $this->_getCurrent();
        $current['matrix'] = $matrix->multiply($current['matrix']);
    }

    /**
     * Get the current transformation matrix.
     *
     * @return SetaPDF_Core_Geometry_Matrix
     */
    public function getCurrentTransformationMatrix()
    {
        $current =& $this->_getCurrent();
        return $current['matrix'];
    }

    /**
     * Open a new graphic state and copy the entire graphic state onto the stack of the new graphic state.
     *
     * @throws BadMethodCallException
     */
    public function save()
    {
        if (count($this->_stack) === self::getMaxGraphicStateNestingLevel()) {
            throw new BadMethodCallException('Too many graphic states open!');
        }

        $current =& $this->_getCurrent();
        $matrix = clone $current['matrix'];

        $this->_stack[] = array('matrix' => $matrix);
    }

    /**
     * Restore the last graphic state and pop all matrices of the current graphic state out of the matrix stack.
     *
     * @throws BadMethodCallException
     */
    public function restore()
    {
        array_pop($this->_stack);

        if(count($this->_stack) === 0) {
            throw new BadMethodCallException("Graphic state is empty!");
        }
    }

    /**
     * Returns the user space coordinates.
     *
     * @param int|float $x
     * @param int|float $y
     * @return array('x' => $x, 'y' => $y)
     */
    public function getUserSpaceXY($x, $y)
    {
        $vector = new SetaPDF_Core_Geometry_Vector($x, $y, 1);
        $result = $this->toUserSpace($vector);

        return array('x' => $result->getX(), 'y' => $result->getY());
    }

    /**
     * Returns the user space coordinates vector.
     *
     * @param SetaPDF_Core_Geometry_Vector $vector
     * @return SetaPDF_Core_Geometry_Vector
     */
    public function toUserSpace(SetaPDF_Core_Geometry_Vector $vector)
    {
        return $vector->multiply($this->getCurrentTransformationMatrix());
    }

    /**
     * Returns the text state helper.
     *
     * @return SetaPDF_Core_Canvas_GraphicState_Text
     */
    public function text()
    {
        if (null === $this->_text) {
            $this->_text = new SetaPDF_Core_Canvas_GraphicState_Text($this, $this->_stack);
        }

        return $this->_text;
    }
}