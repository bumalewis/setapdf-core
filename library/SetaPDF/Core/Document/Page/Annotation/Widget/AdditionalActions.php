<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: AdditionalActions.php 698 2015-02-04 15:48:35Z maximilian.kresse $
 */

/**
 * Class representing a widget annotations additional-actions dictionary
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Document_Page_Annotation_Widget_AdditionalActions
extends SetaPDF_Core_Document_Page_Annotation_AdditionalActions
{
    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Document_Page_Annotation_Widget $annotation
     */
    public function __construct(SetaPDF_Core_Document_Page_Annotation_Widget $annotation)
    {
        parent::__construct($annotation);
    }

    /**
     * Get the action that shall be performed when the annotation receives the input focus.
     *
     * @return null|SetaPDF_Core_Document_Action
     */
    public function getFocus()
    {
        return $this->_getAction('Fo');
    }

    /**
     * Set the action that shall be performed when the annotation receives the input focus.
     *
     * @param SetaPDF_Core_Document_Action $action
     * @return SetaPDF_Core_Document_Page_Annotation_Widget_AdditionalActions
     */
    public function setFocus(SetaPDF_Core_Document_Action $action)
    {
        $this->_setAction('Fo', $action);

        return $this;
    }

    /**
     * Get the action that shall be performed when the annotation loses the input focus.
     *
     * @return null|SetaPDF_Core_Document_Action
     */
    public function getBlur()
    {
        return $this->_getAction('Bl');
    }

    /**
     * Set the action that shall be performed when the annotation loses the input focus.
     *
     * @param SetaPDF_Core_Document_Action $action
     * @return SetaPDF_Core_Document_Page_Annotation_Widget_AdditionalActions
     */
    public function setBlur(SetaPDF_Core_Document_Action $action)
    {
        $this->_setAction('Bl', $action);

        return $this;
    }
}