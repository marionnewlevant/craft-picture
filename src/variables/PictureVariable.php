<?php
/**
 * Picture plugin for Craft CMS 3.x
 *
 * Generate responsive <picture> <img> elements, based on config.
 *
 * @link      http://marion.newlevant.com
 * @copyright Copyright (c) 2018 Marion Newlevant
 */

namespace marionnewlevant\picture\variables;

use marionnewlevant\picture\Picture;

use Craft;
use craft\elements\Asset;

/**
 * Picture Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.picture }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Marion Newlevant
 * @package   Picture
 * @since     2.0.0
 */
class PictureVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Generate <picture> element (or <img> if no sources)
     *
     * @param Asset $asset
     * @param string $assetStyle (from 'imageStyles' in config file)
     * @param array | null $options
     * @return \Twig_Markup
     */
    public function element($assetSet, $assetStyle='default', $options=null)
    {
        if (!is_array($assetSet))
        {
            $assetSet = [$assetSet];
        }
        return Picture::$plugin->pictureService->element($assetSet, $assetStyle, $options);
    }

    /**
     * Generate url based on urlTransfoms
     *
     * @param Asset $asset
     * @param string $assetStyle (from 'urlTransforms' in config file)
     * @param array | null $options
     * @return \Twig_Markup
     */
    public function url(Asset $asset, $assetStyle='default', $options=null)
    {
        return Picture::$plugin->pictureService->url($asset, $assetStyle, $options);
    }

    public function imageStyles()
    {
        return Picture::$plugin->pictureService->imageStyles();
    }
    
    public function urlTransforms()
    {
        return Picture::$plugin->pictureService->urlTransforms();
    }
}
