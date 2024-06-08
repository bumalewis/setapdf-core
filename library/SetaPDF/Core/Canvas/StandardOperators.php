<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: StandardOperators.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * Abstract canvas helper class for standard operators
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Canvas
 * @license    http://www.setasign.com/ Commercial
 */
abstract class SetaPDF_Core_Canvas_StandardOperators extends SetaPDF_Core_Canvas_Operators
{
  /** Setting Colors **/

    /**
     * Proxy method for setting the color on the canvas.
     *
     * @see SetaPDF_Core_Canvas::setColor()
     * @param SetaPDF_Core_DataStructure_Color|array $color
     * @param boolean $stroking
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function setColor($color, $stroking = true)
    {
        $this->_canvas->setColor($color, $stroking);

        return $this;
    }

    /**
     * Proxy method for setting the stroking color on the canvas.
     *
     * @see SetaPDF_Core_Canvas::setColor()
     * @param SetaPDF_Core_DataStructure_Color|array $color
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function setStrokingColor($color)
    {
        return $this->setColor($color, true);
    }

    /**
     * Proxy method for setting the non-stroking color on the canvas.
     *
     * @see SetaPDF_Core_Canvas::setColor()
     * @param SetaPDF_Core_DataStructure_Color|array $color
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function setNonStrokingColor($color)
    {
        return $this->setColor($color, false);
    }

  /** Graphic state **/

    /**
     * Proxy method for setting a graphic state on the canvas.
     *
     * @see SetaPDF_Core_Canvas::setGraphicState()
     * @param SetaPDF_Core_Resource_ExtGState $graphicState
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function setGraphicState(SetaPDF_Core_Resource_ExtGState $graphicState)
    {
        $this->_canvas->setGraphicState($graphicState);

        return $this;
    }

    /**
     * Proxy method for saving the graphic state on the canvas.
     *
     * @see SetaPDF_Core_Canvas::saveGraphicState()
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function saveGraphicState()
    {
        $this->_canvas->saveGraphicState();

        return $this;
    }

    /**
     * Proxy method for restoring the graphic state on the canvas.
     *
     * @see SetaPDF_Core_Canvas::restoreGraphicState()
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function restoreGraphicState()
    {
        $this->_canvas->restoreGraphicState();

        return $this;
    }

    /**
     * Proxy method for adding a transformation matrix on the canvas.
     *
     * @see SetaPDF_Core_Canvas::addCurrentTransformationMatrix()
     * @param float|int $a
     * @param float|int $b
     * @param float|int $c
     * @param float|int $d
     * @param float|int $e
     * @param float|int $f
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function addCurrentTransformationMatrix($a, $b, $c, $d, $e, $f)
    {
        $this->_canvas->addCurrentTransformationMatrix($a, $b, $c, $d, $e, $f);

        return $this;
    }

    /**
     * Proxy method for rotating the transformation matrix on the canvas.
     *
     * @see SetaPDF_Core_Canvas::rotate()
     * @param int|float $x X-coordinate of rotation point
     * @param int|float $y Y-coordinate of rotation point
     * @param int|float $angle Angle to rotate in degrees
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function rotate($x, $y, $angle)
    {
        $this->_canvas->rotate($x, $y, $angle);

        return $this;
    }

    /**
     * Proxy method for scaling the transformation matrix on the canvas.
     *
     * @see SetaPDF_Core_Canvas::scale()
     * @param int|float $scaleX Scale factor on X
     * @param int|float $scaleY Scale factor on Y
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function scale($scaleX, $scaleY)
    {
        $this->_canvas->scale($scaleX, $scaleY);

        return $this;
    }


    /**
     * Proxy method for moving the transformation matrix on the canvas.
     *
     * @see SetaPDF_Core_Canvas::translate()
     * @param int|float $shiftX Points to move on x-axis
     * @param int|float $shiftY Points to move on y-axis
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function translate($shiftX, $shiftY)
    {
        $this->_canvas->translate($shiftX, $shiftY);

        return $this;
    }

    /**
     * Proxy method for skewing the transformation matrix on the canvas.
     *
     * @see SetaPDF_Core_Canvas::skew()
     * @param int|float $angleX Angle to x-axis in degrees
     * @param int|float $angleY Angle to y-axis in degrees
     * @param int|float $x Points to stretch on x-axis
     * @param int|float $y Point to stretch on y-axis
     * @return SetaPDF_Core_Canvas_StandardOperators
     */
    public function skew($angleX, $angleY, $x = 0, $y = 0)
    {
        $this->_canvas->skew($angleX, $angleY, $x, $y);

        return $this;
    }
}