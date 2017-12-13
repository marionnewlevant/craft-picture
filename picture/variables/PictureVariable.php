<?php
/**
 * picture plugin for Craft CMS
 *
 * picture Variable
 *
 * @author    Marion Newlevant
 * @copyright Copyright (c) 2017 Marion Newlevant
 * @link      http://marion.newlevant.com
 * @package   Picture
 * @since     1.0.0
 */

namespace Craft;

class PictureVariable
{
    /**
     * Generate <picture> element (or <img> if no sources)
     *
     * @param AssetFileModel $asset
     * @param string $assetStyle (from 'imageStyles' in config file)
     * @param array | null $options
     * @return \Twig_Markup
     */
    public function element(AssetFileModel $asset, $assetStyle='default', $options=null)
    {
        return craft()->picture->element($asset, $assetStyle, $options);
    }

    /**
     * Generate url based on urlTransfoms
     *
     * @param AssetFileModel $asset
     * @param string $assetStyle (from 'urlTransforms' in config file)
     * @param array | null $options
     * @return \Twig_Markup
     */
    public function url(AssetFileModel $asset, $assetStyle='default', $options=null)
    {
        return craft()->picture->url($asset, $assetStyle, $options);
    }
}