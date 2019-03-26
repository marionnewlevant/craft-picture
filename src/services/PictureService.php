<?php
/**
 * Picture plugin for Craft CMS 3.x
 *
 * Generate responsive <picture> <img> elements, based on config.
 *
 * @link      http://marion.newlevant.com
 * @copyright Copyright (c) 2018 Marion Newlevant
 */

namespace marionnewlevant\picture\services;

use marionnewlevant\picture\Picture;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\web\View;
use craft\helpers\Template;

/**
 * PictureService Service
 *
 * @author    Marion Newlevant
 * @package   Picture
 * @since     2.0.0
 */
class PictureService extends Component
{
    private $_config = [];
    private $_styles = [];
    private $_lazysizesTrigger = '';
    private $_transforms = [];

    // Public Methods
    // =========================================================================

    /**
     * Generates a <picture> element, or <img> if no sources.
     *
     * @param array $assetSet Array of assets to generate the picture element for.
     * @param string $assetStyle The asset style - index of imageStyles in config file
     * @param array | null $options
     * @return \Twig_Markup
     */
    public function element(array $assetSet, $assetStyle, $options = null)
    {
        // priority order for configuration (low to high):
        // imageStyles config in picture config file
        // individual source config in picture config file
        // options passed in on the call

        if (null === $options)
        {
            $options = [];
        }

        $sourcesHtml = '';
        $imgHtml = '';

        $style = $this->_getStyle($assetStyle);

        $styleTransform = array_key_exists('transform', $style) ? $style['transform'] : [];

        $optionsTransform = array_key_exists('transform', $options) ? $options['transform'] : [];
        unset($options['transform']);

        $lazysizes = array_key_exists('lazysizes', $options) ? $options['lazysizes'] : (array_key_exists('lazysizes', $style) ? $style['lazysizes'] : false);
        unset($options['lazysizes']);

        $oldMode = Craft::$app->view->getTemplateMode();
        Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $i = 0;
        foreach ((array_key_exists('sources', $style) ? $style['sources'] : []) as $source) {
            $asset = $assetSet[$i];
            $i++;
            if (count($assetSet) <= $i) { $i = 0; }
            $transformedImages = $this->_transformedImages($asset, $source, $styleTransform, $optionsTransform);
            $sourcesHtml .= Craft::$app->view->renderTemplate('picture/source',
                [
                    'source' => $source,
                    'transformedImages' => $transformedImages,
                    'lazysizes' => $lazysizes,
                ]
            );
        }

        $asset = $assetSet[$i];
        if (array_key_exists('img', $style))
        {
            $img = $style['img'];
            $transformedImages = $this->_transformedImages($asset, $img, $styleTransform, $optionsTransform);
            $imgHtml = Craft::$app->view->renderTemplate('picture/img',
                array(
                    'img' => $img,
                    'transformedImages' => $transformedImages,
                    'attrs' => $options,
                    'asset' => $asset,
                    'lazysizes' => $lazysizes,
                    'lazysizesTrigger' => $this->_getLazysizesTrigger(),
                )
            );
        }
        else
        {
            $imgHtml = Craft::$app->view->renderTemplate('picture/img',
                array(
                    'img' => [],
                    'transformedImages' => [],
                    'attrs' => $options,
                    'asset' => $asset,
                    'lazysizes' => $lazysizes,
                    'lazysizesTrigger' => $this->_getLazysizesTrigger(),
                )
            );
        }

        Craft::$app->view->setTemplateMode($oldMode);

        // if we have sources, wrap it in a <picture> element. Otherwise, just the <img>
        $html = $sourcesHtml ? '<picture>'.$sourcesHtml.$imgHtml.'</picture>' : $imgHtml;
        return Template::raw($html);
    }

    public function url(Asset $asset, $assetTransform, $options = null)
    {
        // priority order for configuration (low to high):
        // image class config in picture config file
        // individual source config in picture config file
        // focal point of the image (position only)
        // options passed in on the call

        if (null === $options)
        {
            $options = [];
        }

        $style = $this->_getTransform($assetTransform);

        $styleTransform = array_key_exists('transform', $style) ? $style['transform'] : [];

        $optionsTransform = array_key_exists('transform', $options) ? $options['transform'] : [];
        unset($options['transform']);

        $transformedImages = $this->_transformedImages($asset, $style, $styleTransform, $optionsTransform);
        $url = $asset->url;
        if (count($transformedImages)) {
            $url = $transformedImages[0]['url'];
        }

        return Template::raw($url);
    }

    public function imageStyles()
    {
        return array_keys($this->_getImageStyles());
    }

    public function urlTransforms()
    {
        return array_keys($this->_getUrlTransforms());
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
        $styles = $this->_getImageStyles();
        if (array_key_exists($assetStyle, $styles))
        {
            return $styles[$assetStyle];
        }
        if (array_key_exists('default', $styles))
        {
            return $styles['default'];
        }
        return [];
    }

    private function _getImageStyles()
    {
        if (!$this->_styles)
        {
            $this->_initConfig();
            $this->_styles = array_key_exists('imageStyles', $this->_config) ? $this->_config['imageStyles'] : [];
        }
        return $this->_styles;
    }

    private function _getLazysizesTrigger()
    {
        if (!$this->_lazysizesTrigger)
        {
            $this->_initConfig();
            $this->_lazysizesTrigger = array_key_exists('lazysizesTrigger', $this->_config) ? $this->_config['lazysizesTrigger'] : 'lazyload';
        }
        return $this->_lazysizesTrigger;
    }

    private function _initConfig()
    {
        if (!$this->_config)
        {
            $this->_config = Craft::$app->getConfig()->getConfigFromFile('picture');
        }
    }

    /**
     * Get the transform configuration.
     *
     * @param string $assetTransform
     *
     * @return array
     */
    private function _getTransform($assetTransform)
    {
        $urlTransforms = $this->_getUrlTransforms();
        if (array_key_exists($assetTransform, $urlTransforms))
        {
            return $urlTransforms[$assetTransform];
        }
        if (array_key_exists('default', $urlTransforms))
        {
            return $urlTransforms['default'];
        }
        return [];
    }

    private function _getUrlTransforms()
    {
        if (!$this->_transforms)
        {
            $config = Craft::$app->getConfig()->getConfigFromFile('picture');
            $this->_transforms = array_key_exists('urlTransforms', $config) ? $config['urlTransforms'] : [];
        }
        return $this->_transforms;
    }

    /**
     * Generate the transformed images.
     *
     * @param Asset          $asset The asset to transform
     * @param array          $config The source or img configuration from the style, or the config for the transformation
     * @param array          $styleTransform transform global to the image style.
     * @param array          $optionsTransform transform passed in options.
     *
     * @return array         Array of [width, url] for each transform
     */
    private function _transformedImages(Asset $asset, $config, $styleTransform, $optionsTransform)
    {
        $transformedImages = [];
        $transform = array_merge($styleTransform, (array_key_exists('transform', $config) ? $config['transform'] : []), $optionsTransform);
        $aspectRatio = array_key_exists('aspectRatio', $config) ? $config['aspectRatio'] : null;

        if ($asset->getExtension() != 'svg')
        {

            // what can we transform to w/o upscaling?
            $maxTransformWidth = self::_maxTransformWidth($asset, $aspectRatio, $transform);
            // we accept either 'widths' - an array, or 'width' - a single number
            $widths = array_key_exists('widths', $config) ? $config['widths'] : (array_key_exists('width', $config) ? [$config['width']] : []);

            foreach ($widths as $width) {
                if ($maxTransformWidth && $width <= $maxTransformWidth)
                {
                    $transformedUrl = $asset->getUrl(self::_transform($width, $aspectRatio, $transform));
                    if ($transformedUrl) {
                        $transformedImages[] = [
                            'width' => $width,
                            'url' => $transformedUrl
                        ];
                    }
                }
            }
        }
        if (!$transformedImages)
        {
            // if we didn't get any (probably because of upscaling or svg, try at our native width)
            $transformedUrl = $aspectRatio ? $asset->getUrl(self::_transform($asset->width, $aspectRatio, $transform)) : $asset->getUrl(null);
            if ($transformedUrl) {
                $transformedImages[] = [
                    'width' => $asset->width,
                    'url' => $transformedUrl
                ];
            }
        }
        return $transformedImages;
    }

    private static function _transform($width, $aspectRatio, $transform)
    {
        $transform['width'] = $width;
        if ($aspectRatio)
        {
            $transform['height'] = $width / $aspectRatio;
        }
        return $transform;
    }

    private static function _maxTransformWidth($asset, $aspectRatio, $transform)
    {
        $nativeWidth = $asset->width;
        if (!$aspectRatio) { return $nativeWidth; }

        $mode = array_key_exists('mode', $transform) ? $transform['mode'] : 'crop';
        if ($mode != 'crop') { return $nativeWidth; }
        // we have to handle the aspectRatio...
        $nativeHeight = $asset->height;
        if (($nativeWidth / $nativeHeight) <= $aspectRatio) {
            return $nativeWidth;
        }
        return $aspectRatio * $nativeHeight;
    }

}
