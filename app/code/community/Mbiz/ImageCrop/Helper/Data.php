<?php
/**
 * This file is part of Mbiz_ImageCrop for Magento.
 *
 * @license All rights reserved
 * @author Jacques Bodin-Hullin <@jacquesbh> <j.bodinhullin@monsieurbiz.com>
 * @category Mbiz
 * @package Mbiz_ImageCrop
 * @copyright Copyright (c) 2014 Monsieur Biz (http://monsieurbiz.com/)
 */

/**
 * Data Helper
 * @package Mbiz_ImageCrop
 */
class Mbiz_ImageCrop_Helper_Data extends Mage_Core_Helper_Abstract
{

// Monsieur Biz Tag NEW_CONST

    /**
     * The prefix directory (under media)
     *
     * @var string
     */
    protected $_prefix = null;

    /**
     * Default quality setting
     *
     * @var int
     */
    protected $_quality = 95;

// Monsieur Biz Tag NEW_VAR

    /**
     * Crop the image then return the URL
     *
     * @param $imageRelativePath The relative path from 'media' folder
     * @param $width
     * @param null $height
     *
     * @return null|string The URL of the cropped image
     */
    public function crop($imageRelativePath, $width, $height = null)
    {
        /**
         * Retrieve image absolute path and basename
         */
        $image = $this->_getImageAbsolutePath($imageRelativePath);
        $imageName = $this->_getImageBaseName($image);

        /**
         * If source image does not exist
         */
        if (!is_file($image)) {
            return null;
        }

        /**
         * Determine the height of the generated image
         */
        if ($height === null) {
            $height = $width;
        }

        /**
         * Misc parameters
         */
        $parameters = array(
            'constrainOnly',
            'keepAspectRatio',
            'keepFrame',
            'crop',
            $this->_getImageAdapter(),
            $this->getQuality(),
        );

        /**
         * Parameters hash
         */
        $parametersHash = $this->_getParametersHash($parameters);

        /**
         * Directories
         */
        $baseDir = $this->_getMediaBaseDir();
        $intermediateDir = $this->_generateIntermediateDir($width, $height, $parametersHash, $imageName);
        $dir = $baseDir . DS . $intermediateDir;

        /**
         * Check if destination directory exists
         */
        $this->_checkDestinationDir($dir);

        /**
         * Get new image Url
         */
        $imageUrl = $this->_getImageUrl($intermediateDir, $imageName);

        /**
         * New image full path
         */
        $imageFullPath = $dir . DS . $imageName;

        /**
         * If cropped image has been already generated, we return it and skip another useless generation
         */
        if (is_file($imageFullPath)) {
            return $this->_filterImageUrl($imageUrl);
        }

        /**
         * First, resize the image
         */
        $imageObj = new Varien_Image($image, $this->_getImageAdapter());
        $oldHeight = $imageObj->getOriginalHeight();
        $oldWidth = $imageObj->getOriginalWidth();

        /**
         * Settings
         */
        $imageObj->constrainOnly(true);
        $imageObj->keepAspectRatio(true);
        $imageObj->keepFrame(false);
        $imageObj->quality($this->getQuality());

        /**
         * Transparency detection
         */
        $transparent = false;
        if (exif_imagetype($image) == IMAGETYPE_PNG) {
            $transparent = true;
        }
        $imageObj->keepTransparency($transparent);

        /**
         * Resize in the good way
         */
        if (($oldWidth / $oldHeight) < ($width / $height)) {
            $imageObj->resize($width, null);
        } else {
            $imageObj->resize(null, $height);
        }

        $imageObj->save($dir, $imageName);
        unset($imageObj);

        /**
         * Then we crop previously resized image
         */
        $imageObj2 = new Varien_Image($imageFullPath, $this->_getImageAdapter());
        $imageObj2->quality($this->getQuality());

        $top = ($imageObj2->getOriginalHeight() - $height) / 2;
        $left = ($imageObj2->getOriginalWidth() - $width) / 2;

        $imageObj2->crop($top, $left, $left, $top);
        $imageObj2->save($imageFullPath);
        unset($imageObj2);

        /**
         * Return new image URL
         */
        return $this->_filterImageUrl($imageUrl);
    }

    /**
     * Resize the image then return the URL
     *
     * @param $imageRelativePath The relative path from 'media' folder
     * @param $width
     * @param null $height
     * @return null|string The URL of the resized image
     */
    public function resize($imageRelativePath, $width, $height = null)
    {
        /**
         * Retrieve image absolute path and basename
         */
        $image = $this->_getImageAbsolutePath($imageRelativePath);
        $imageName = $this->_getImageBaseName($image);

        /**
         * If source image does not exist
         */
        if (!is_file($image)) {
            return null;
        }

        /**
         * Determine the 'height path' value of the generated image
         */
        if ($height === null) {
            $heightPathValue = 0;
        } else {
            $heightPathValue = $height;
        }

        /**
         * Misc parameters
         */
        $parameters = array(
            'constrainOnly',
            'keepAspectRatio',
            'keepFrame',
            'resize',
            $this->_getImageAdapter(),
            $this->getQuality()
        );

        /**
         * Parameters hash
         */
        $parametersHash = $this->_getParametersHash($parameters);

        /**
         * Directories
         */
        $baseDir = $this->_getMediaBaseDir();
        $intermediateDir = $this->_generateIntermediateDir($width, $heightPathValue, $parametersHash, $imageName);
        $dir = $baseDir . DS . $intermediateDir;

        /**
         * Check if destination directory exists
         */
        $this->_checkDestinationDir($dir);

        /**
         * Get new image Url
         */
        $imageUrl = $this->_getImageUrl($intermediateDir, $imageName);

        /**
         * New image full path
         */
        $imageFullPath = $dir . DS . $imageName;

        /**
         * If resized image has been already generated, we return it and skip another useless generation
         */
        if (is_file($imageFullPath)) {
            return $this->_filterImageUrl($imageUrl);
        }

        /**
         * Resize image
         */
        $imageObj = new Varien_Image($image, $this->_getImageAdapter());

        /**
         * Settings
         */
        $imageObj->constrainOnly(true);
        $imageObj->keepAspectRatio(true);
        $imageObj->keepFrame(false);
        $imageObj->quality($this->getQuality());

        /**
         * Transparency detection
         */
        $transparent = false;
        if (exif_imagetype($image) == IMAGETYPE_PNG) {
            $transparent = true;
        }
        $imageObj->keepTransparency($transparent);

        $imageObj->resize($width, $height);
        $imageObj->save($dir, $imageName);
        unset($imageObj);

        /**
         * Return new image URL
         */
        return $this->_filterImageUrl($imageUrl);
    }

    /**
     * Set the current prefix
     *
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
        return $this;
    }

    /**
     * Retrieve the current prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Set current quality for resize/crop
     *
     * @param string|int $quality
     * @return $this
     */
    public function setQuality($quality)
    {
        $this->_quality = (int)$quality;
        return $this;
    }

    /**
     * Retrieve current quality for resize/crop
     *
     * @return int
     */
    public function getQuality()
    {
        return $this->_quality;
    }

    /**
     * Generate the intermediate dir path (without )
     *
     * @param $width
     * @param $height
     * @param $parametersHash
     * @param $imageName
     * @return string
     */
    protected function _generateIntermediateDir($width, $height, $parametersHash, $imageName)
    {
        $intermediateDir = '';

        /**
         * Prefix the directory
         */
        if ($prefix = $this->getPrefix()) {
            $intermediateDir .= $prefix . DS;
        }

        $intermediateDir .= 'cache' . DS
            . $width . 'x' . $height . DS
            . $parametersHash . DS
            . strtolower($imageName[0]) . DS
            . strtolower(isset($imageName[1]) && $imageName[1] !== '.' ? $imageName[1] : $imageName[0]);

        return $intermediateDir;
    }

    /**
     * Get parameters hash (md5 based)
     *
     * @param $parameters
     * @return string
     */
    protected function _getParametersHash($parameters)
    {
        return md5(implode('|', $parameters));
    }

    /**
     * Get image absolute path
     *
     * @param $imageRelativePath
     * @return string
     */
    protected function _getImageAbsolutePath($imageRelativePath)
    {
        return Mage::getBaseDir('media') . DS . ltrim($imageRelativePath, '/');
    }

    /**
     * Get image base name
     *
     * @param $imageAbsolutePath
     * @return string
     */
    protected function _getImageBaseName($imageAbsolutePath)
    {
        return basename($imageAbsolutePath);
    }

    /**
     * Get finale image Url
     *
     * @param $intermediateDir
     * @param $imageName
     * @return string
     */
    protected function _getImageUrl($intermediateDir, $imageName)
    {
        return Mage::getBaseUrl('media') . $intermediateDir . '/' . $imageName;
    }

    /**
     * Get 'media' folder base directory
     *
     * @return string
     */
    protected function _getMediaBaseDir()
    {
        return Mage::getBaseDir('media');
    }

    /**
     * Filter generated image Url to prevent an issue with Windows DS
     *
     * @param $url
     * @return mixed
     */
    protected function _filterImageUrl($url)
    {
        // Windows DS fix
        if (DS == '\\') {
            $url = str_replace('\\', '/', $url);
        }

        return $url;
    }

    /**
     * Check if destination directory exists or not, and create it if not
     *
     * @param $dir
     */
    protected function _checkDestinationDir($dir)
    {
        if (!@is_dir($dir)) {
            $io = new Varien_Io_File();
            $io->setAllowCreateFolders(true)
                ->createDestinationDir($dir);
        }
    }

    /**
     * Get image adapter
     *
     * (FireGento_PerfectWatermarks setting compatibility)
     *
     * @return mixed|string
     */
    protected function _getImageAdapter()
    {
        return Mage::getStoreConfig('design/watermark_adapter/adapter')
            ? Mage::getStoreConfig('design/watermark_adapter/adapter')
            : Varien_Image_Adapter::ADAPTER_GD2;
    }

// Monsieur Biz Tag NEW_METHOD

}