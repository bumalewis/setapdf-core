<?php
/**
 * This file is part of the SetaPDF-FormFiller Component
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    http://www.setasign.com/ Commercial
 * @version    $Id: SplObjectStorage.php 816 2016-02-12 08:50:35Z jan.slabon $
 */

/**
 * The helper class for SplObjectStorage.
 *
 * This class exists for compatibility to PHP 5.2 because it doesn't support values to the
 * object keys. This class does emulate the behavior of a php5.3+ object storage for php5.2.
 * For php5.3+ the class will use the default methods of the SplObjectStorage.
 *
 * @copyright  Copyright (c) 2016 Setasign - Jan Slabon (http://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    http://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Compat_SplObjectStorage
    extends SplObjectStorage
    implements ArrayAccess
{
    /**
     * Property to cache the PHP version check.
     *
     * @var null|boolean
     */
    static private $_isPhp52 = null;

    /**
     * Data array for PHP 5.2.
     *
     * @var array
     */
    private $_data = array();

    /**
     * Check if the PHP version is 5.2 or not.
     *
     * @return boolean
     */
    static private function isPhp52()
    {
        if (null === self::$_isPhp52) {
            self::$_isPhp52 = version_compare(phpversion(), '5.3', '<');
        }

        return self::$_isPhp52;
    }

    /**
     * Adds an object inside the storage, and optionally associate it to some data.
     *
     * @param object $object The object to add.
     * @param null $data The data to associate with the object.
     * @see http://php.net/splobjectstorage.attach.php
     */
    public function attach($object, $data = null)
    {
        if (!self::isPhp52()) {
            parent::attach($object, $data);
            return;
        }

        parent::attach($object);
        $this->_data[spl_object_hash($object)] = $data;
    }

    /**
     * Removes the object from the storage.
     *
     * @param object $object The object to remove.
     * @see http://php.net/manual/splobjectstorage.detach.php
     */
    public function detach($object)
    {
        parent::detach($object);
        if (!self::isPhp52()) {
            return;
        }

        unset($this->_data[spl_object_hash($object)]);
    }

    /**
     * Returns the data associated with an object in the storage.
     *
     * @param object $object The object to look for.
     * @return mixed
     * @see http://php.net/manual/splobjectstorage.offsetget.php
     */
    public function offsetGet($object)
    {
        if (!self::isPhp52()) {
            return parent::offsetGet($object);
        }

        return $this->_data[spl_object_hash($object)];
    }

    /**
     * Associate data to an object in the storage.
     *
     * @param object $object The object to associate data with.
     * @param mixed|null $data The data to associate with the object.
     * @see attach()
     */
    public function offsetSet($object, mixed $info = null)
    {
        $this->attach($object, $info);
    }

    /**
     * Removes an object from the storage.
     *
     * @param object $object The object to remove.
     * @see detach()
     */
    public function offsetUnset($object)
    {
        $this->detach($object);
    }

    /**
     * Checks whether an object exists in the storage.
     *
     * @param object $object The object to look for.
     * @return bool
     */
    public function offsetExists($object)
    {
        if (!self::isPhp52()) {
            return parent::offsetExists($object);
        }

        return isset($this->_data[spl_object_hash($object)]);
    }

    /**
     * Returns the data, or info, associated with the object pointed by the current iterator position.
     *
     * @return mixed
     */
    public function getInfo()
    {
        if (!self::isPhp52()) {
            return parent::getInfo();
        }

        $object = $this->current();
        return $this->_data[spl_object_hash($object)];
    }
}