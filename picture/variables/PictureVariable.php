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
     * @param string $assetStyle
     * @param array | null $options
     * @return \Twig_Markup
     */
    public function picture(AssetFileModel $asset, $assetStyle='default', $options=null)
    {
        return craft()->picture->picture($asset, $assetStyle, $options);
    }
}