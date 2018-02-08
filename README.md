# picture plugin for Craft 3

Generate &lt;picture&gt; elements.

## See v1 branch for the Craft 2 version

## Installation

1. Install with Composer via `composer require marionnewlevant/picture` from your project directory
2. Install plugin in the Craft Control Panel under Settings > Plugins

or

1. Install via the Plugin Store

## picture Overview

Creates the transforms for an asset, and generates a &lt;picture&gt; or &lt;img&gt; element, or simply returns the transformed image url. Most of the work is in writing the configuration that describes the different image styles.

The plugin provides two variables: craft.picture.element generates &lt;picture&gt; and &lt;img&gt; elements, which contain multiple urls for transfomations of a single image. craft.picture.url generates a single url, useful when the image is a `background-image`.

## integration with [ImageOptimize](https://github.com/nystudio107/craft-imageoptimize)

The ImageOptimize plugin will automatically run image optimizers such as jpegoptim on your transformed images. It works well with this plugin, and no additional changes are required.

## Configuring picture

You need a config file, `craft/config/picture.php`. That file defines the different image styles for your project.

Here is a sample configuration file:

    <?php

    return [
      // array of image styles. The name of the style will be the key,
      // and the configuration of the style will be the value.
      // These styles are used to generate picture and img elements
      // with craft.picture.element
      'imageStyles' => [

        // this is the 'thumb' style. It will generate an img like:
        // <img
        //   srcset="transform75pxUrl 75w, transform150pxUrl 150w"
        //   sizes="75px"
        //   src="transform75pxUrl"
        // />
        'thumb' => [
          // since the 'thumb' style has no sources, it will generate
          // an img element, not a picture element
          'img' => [
            // optional aspect ratio, width / height. Will use the
            // native aspect ratio of the asset if not specified
            // This will be square
            'aspectRatio' => 1,
            // the 'sizes' attribute of the element
            'sizes' => '75px',
            // pixel widths for the generated images. The first
            // element in the array will be the width of the default
            // src
            'widths' => [75, 150]
          ]
        ],
        
        // the 'art' style will generate a picture element with two
        // nested source elements and a nested img element
        'art' => [

          // since there is a sources array, this will generate a
          // picture element. sources is an array of source
          // configurations
          'sources' => [
            // on narrow screens, we are going to have a 4x3
            // small image. This source element will look like:
            // <source
            //   media="(max-sidth: 600px)"
            //   srcset="t100pxUrl 100w, t200pxUrl 200w"
            //   sizes="100px"
            // />
            [
              // optional 'media' attribute for this source
              // (the 'media' attribute is required on all but
              // the last source)
              'media' => '(max-width: 600px)',
              // optional aspect ratio
              'aspectRatio' => 4/3,
              'sizes' => '100px',
              'widths' => [100, 200],
            ],
            // on other screens we are going to use the native aspect
            // ratio. The image will be 1/2 the screen width up to
            // 1000px, and 500px after that. The source element:
            // <source
            //   srcset="t500pxUrl 500w, t1000pxUrl 1000w"
            //   sizes="(max-width: 1000px) 50vw, 500px"
            // />
            [
              // sizes is just a string, but we can use php to make it
              // easier to construct
              'sizes' => implode(', ', [
                '(max-width: 1000px) 50vw',
                '500px'
              ]),
              'widths' => [500, 1000]
            ]
          ],

          // the img element is required. We just need a fallback src,
          // this will be 500px wide (no srcset when there is only one
          // width).
          // <img src="t500pxUrl" />
          'img' => [
            'widths' => [500]
          ],

          // transform is optional, and specifies parameters for
          // the Craft transforms for the style
          'transform' => [
            'format' => 'jpg'
          ]
        ],
     
        // the default style will be used when none is specified.
        'default' => [
          'img' => [
            'widths' => [500],
          ]
        ]
      ],

      // the urlTransforms are used to specify individual urls for
      // craft.picture.url
      'urlTransforms' => [
        // the 'hero' transform - these image will be 7:3, and 1000px wide
        'hero' => [
          'aspectRatio' => 7/3,
          'width' => 1000
        ]
      ]
    ];

To recap, the config file specifies the `imageStyles` for generating
&lt;picture&gt; and &lt;img&gt; elements, and the `urlTransforms` for
generating single image urls.

Each individual element in `imageStyles` has an optional array of `sources` and an `img`. The img can have:

- sizes: optional sizes attribute
- widths: array of pixel widths. Must have at least one
- aspectRatio: optional aspect ratio
- transform: additional craft transform parameters (width is set by the plugin, and so is height if the aspect ratio is specified)

Each source can have all of those, plus

- media: optional media attribute

Additionally, the style as a whole can have:

- transform: optional Imager transformDefaults

Each individual element in `urlTransforms` can have:

- width: single pixel width. This is required.
- aspectRatio: optional aspect ratio
- transform: additional craft transform parameters

Whenever possible, Picture avoids generating transforms that will up-scale the original image.

## Using craft.picture.element

Use craft.picture.element in your templates like this:

    {{ craft.picture.element(asset, style, options) }}

- `asset` is an [Asset] element - a regular Craft asset
- `style` is the name of the image style. It is optional, and if missing, the _default_ style will be used.
- `options` is an optional hash of options.
  - `transform` - additional Craft transform parameters
  - anything else will be attributes on the &lt;img&gt; element.

Example for a thumb style image with alt text of _thumbAlt_, and the crop position _bottom-right_ and quality of _80_:

    {{ craft.picture.element(
         entry.image.first,
         'thumb',
         {
           alt: 'thumbAlt',
           transform: {
             position: 'bottom-right',
             quality: 80
           }
         }
    ) }}

## using craft.picture.url

For a hero image used as a background image:

    <div class="hero" style="background-image: url({{ craft.picture.url(entry.hero, 'hero') }})"></div>

## Tips

I use a twig macro which handles missing images and svg images before calling _craft.picture.element_.

I define a style _preparse_ which includes all the different transforms, and generate that style in a [Preparse](https://github.com/aelvan/Preparse-Field-Craft) field when the asset is saved to pre-build the transforms.

---
Brought to you by [Marion Newlevant](http://marion.newlevant.com). Icon insides by [Setyo Ari Wibowo](https://thenounproject.com/search/?q=picture%20frame&i=1191340)
