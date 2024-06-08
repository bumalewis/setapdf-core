<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Filter
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: FilterInterface.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * A filter interface
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Filter
 * @license    http://www.setasign.com/ Commercial
 */
interface SetaPDF_Core_Filter_FilterInterface
{
    /**
     * Decode a string.
     *
     * @param string $data The input string
     * @return string
     */
    public function decode($data);

    /**
     * Encodes a string.
     *
     * @param string $data The input string
     * @return string
     */
    public function encode($data);
}