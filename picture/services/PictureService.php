<?php
/**
 * picture plugin for Craft CMS
 *
 * Picture Service
 *
 * @author    Marion Newlevant
 * @copyright Copyright (c) 2017 Marion Newlevant
 * @link      http://marion.newlevant.com
 * @package   Picture
 * @since     1.0.0
 */

namespace Craft;

class PictureService extends BaseApplicationComponent
{
    private $_styles = array();
    private $_focalPointField = false;

    /**
     * Generates a <picture> element, or <img> if no sources.
     *
     * @param AssetFileModel $asset The asset to generate the picture element for.
     * @param string $assetStyle The asset style - index of imageStyles in config file
     * @param array | null $options
     * @return \Twig_Markup
     */
    public function picture(AssetFileModel $asset, $assetStyle, $options = null)
    {
        // priority order for configuration (low to high):
        // imager config file
        // image class config in picture config file
        // individual source config in picture config file
        // focal point of the image (position only)
        // options passed in on the call

        if (null === $options)
        {
            $options = array();
        }
        $oldPath = craft()->templates->getTemplatesPath();
        $newPath = craft()->path->getPluginsPath().'picture/templates';
        craft()->templates->setTemplatesPath($newPath);

        $sourcesHtml = '';
        $imgHtml = '';

        $style = $this->_getStyle($assetStyle);
        $styleTransformDefaults = array_key_exists('transformDefaults', $style) ? $style['transformDefaults'] : array();
        $styleConfigOverrides = array_key_exists('configOverrides', $style) ? $style['configOverrides'] : array();

        $optionsTransformDefaults = array_key_exists('transformDefaults', $options) ? $options['transformDefaults'] : array();
        unset($options['transformDefaults']);
        $focalPointField = $this->getFocalPointField();
        if ($focalPointField && !array_key_exists('position', $optionsTransformDefaults))
        {
            $optionsTransformDefaults['position'] = $asset->$focalPointField;
        }
        $optionsConfigOverrides = array_key_exists('configOverrides', $options) ? $options['configOverrides'] : array();
        unset($options['configOverrides']);

        foreach ((array_key_exists('sources', $style) ? $style['sources'] : array()) as $source) {
            $imagerImages = $this->_imagerImages($asset, $source, $styleTransformDefaults, $styleConfigOverrides, $optionsTransformDefaults, $optionsConfigOverrides);
            $sourcesHtml .= craft()->templates->render('source',
                array(
                    'source' => $source,
                    'imagerImages' => $imagerImages,
                )
            );
        }
        if (array_key_exists('img', $style))
        {
            $img = $style['img'];
            $imagerImages = $this->_imagerImages($asset, $img, $styleTransformDefaults, $styleConfigOverrides, $optionsTransformDefaults, $optionsConfigOverrides);
            $imgHtml = craft()->templates->render('img',
                array(
                    'img' => $img,
                    'imagerImages' => $imagerImages,
                    'attrs' => $options,
                )
            );
        }

        craft()->templates->setTemplatesPath($oldPath);

        // if we have sources, wrap it in a <picture> element. Otherwise, just the <img>
        $html = $sourcesHtml ? '<picture>'.$sourcesHtml.$imgHtml.'</picture>' : $imgHtml;
        return TemplateHelper::getRaw($html);
    }

    /**
     * Call imager to generate the transformed images.
     *
     * @param AssetFileModel $asset
     * @param array          $config The source or img configuration from the style.
     * @param array          $styleTransformDefaults transformDefaults global to the image style.
     * @param array          $styleConfigOverrides configOverrides global to the image style.
     * @param array          $optionsTransformDefaults transformDefaults passed in options. Also includes the focal point position.
     * @param array          $optionsConfigOverrides configOverrides passed in options.
     *
     * @return array Imager_ImageModel
     */
    private function _imagerImages(AssetFileModel $asset, $config, $styleTransformDefaults, $styleConfigOverrides, $optionsTransformDefaults, $optionsConfigOverrides)
    {
        $transformDefaults = array_merge($styleTransformDefaults, (array_key_exists('transformDefaults', $config) ? $config['transformDefaults'] : array()), $optionsTransformDefaults);
        $configOverrides = array_merge($styleConfigOverrides, (array_key_exists('configOverrides', $config) ? $config['configOverrides'] : array()), $optionsConfigOverrides);
        $aspectRatio = array_key_exists('aspectRatio', $config) ? $config['aspectRatio'] : null;
        $widths = array_key_exists('widths', $config) ? $config['widths'] : array();

        $imagerImages = array();
        foreach ($widths as $width) {
            $imagerImage = craft()->imager->transformImage($asset, PictureService::_transform($width, $aspectRatio), $transformDefaults, $configOverrides);
            $imagerImages[] = $imagerImage;
        }
        return $imagerImages;
    }

    /**
     * Get the style configuration.
     *
     * @param string $assetStyle
     *
     * @return array
     */
    private function _getStyle($assetStyle)
    {
        if (!$this->_styles)
        {
            $this->_styles = craft()->config->get('imageStyles', 'picture');
        }
        if (array_key_exists($assetStyle, $this->_styles))
        {
            return $this->_styles[$assetStyle];
        }
        if (array_key_exists('default', $this->_styles))
        {
            return $this->_styles['default'];
        }
        return array();
    }

    /**
     * Get the focalPoint field name from the config.
     *
     * @return string | null
     */
    public function getFocalPointField()
    {
        if ($this->_focalPointField === false)
        {
            $this->_focalPointField = craft()->config->get('focalPointField', 'picture');
        }
        return $this->_focalPointField;
    }

    private static function _transform($width, $aspectRatio)
    {
        $transform = array('width' => $width);
        if ($aspectRatio)
        {
            $transform['ratio'] = $aspectRatio;
        }
        return $transform;
    }

}