# picture plugin for Craft CMS

Generate &lt;picture&gt; elements. Relies on [Imager](https://github.com/aelvan/Imager-Craft) and optionally [Focal Point Field](https://github.com/aelvan/FocalPointField-Craft).


## Installation

Download the zip file and install in the usual way. Enable in the control panel under Settings > Plugins.

## picture Overview

Uses [Imager](https://github.com/aelvan/Imager-Craft) and optionally [Focal Point Field](https://github.com/aelvan/FocalPointField-Craft) to transform the images, and generate a &lt;picture&gt; or &lt;img&gt; element, or simply return the transformed image url. Most of the work is in writing the configuration that describes the different image styles.

The plugin provides two variables: craft.picture.element generates &lt;picture&gt; and &lt;img&gt; elements, which contain multiple urls for transfomations of a single image. craft.picture.url generates a single url, useful when the image is a `background-image`.

## Configuring picture

You will want a config file, `craft/config/picture.php`. That file defines the different image styles for your project, and the name of the `Focal Point Field` if you are using one.

Here is a sample configuration file. I have used the `[]` syntax for arrays as I find it more readable. The `array()` syntax will work as well.:

    <?php

    return [
      // handle of the focal point field. This is optional.
      'focalPointField' => 'focalPoint',

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
            // on narrow screens, we are going to have a 4x3 zoomed in
            // small image. This source element will look like:
            // <source
            //   media="(max-sidth: 600px)"
            //   srcset="t100pxUrl 100w, t200pxUrl 200w"
            //   sizes="100px"
            // />
            [
              // optional 'media' attribute for this source
              'media' => '(max-width: 600px)',
              // optional aspect ratio
              'aspectRatio' => 4/3,
              'sizes' => '100px',
              'widths' => [100, 200],
              // imager transformDefaults (see imager documentation).
              // This is where the zoom is specified.
              'transformDefaults' => [
                'cropZoom' => 3
              ]
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
&lt;picture&gt; and &lt;img&gt; elements, the `urlTransforms` for
generating single image urls, and the name of the focal point field.

Each individual element in `imageStyles` has an optional array of `sources` and an `img`. The img can have:

- sizes: optional sizes attribute
- widths: array of pixel widths. Must have at least one
- aspectRatio: optional aspect ratio
- transformDefaults: optional Imager transformDefaults
- configOverrides: optional Imager configOverrides

Each source can have all of those, plus

- media: optional media attribute

Additionally, the style as a whole can have:

- transformDefaults: optional Imager transformDefaults
- configOverrides: optional Imager configOverrides.

Imager `transformDefaults` and `configOverrides` are documented in the [Imager documentation](https://github.com/aelvan/Imager-Craft#craftimagertransformimageimage-transform--transformdefaultsnull-configoverridesnull)

Each individual element in `urlTransforms` can have:

- width: single pixel width. This is required.
- aspectRatio: optional aspect ratio
- transformDefaults: optional Imager transformDefaults
- configOverrides: optional Imager configOverrides

You will also want to configure the Imager plugin. I always set
`'allowUpscale' => false` in the Imager configuration.

## Using craft.picture.element

Use craft.picture.element in your templates like this:

    {{ craft.picture.element(asset, style, options) }}

- `asset` is an [AssetFileModel](https://craftcms.com/docs/templating/assetfilemodel) - a regular Craft asset
- `style` is the name of the image style. It is optional, and if missing, the _default_ style will be used.
- `options` is an optional hash of options.
  - `transformDefaults`  and `configOverrides` - will be passed to the [craft.imager.transformImage](https://github.com/aelvan/Imager-Craft#craftimagertransformimageimage-transform--transformdefaultsnull-configoverridesnull)
  - anything else will be attributes on the &lt;img&gt; element.

Example for a thumb style image with alt text of _thumbAlt_, and the crop position _bottom-right_ and jpegQuality of _80_:

    {{ craft.picture.element(
         entry.image.first,
         'thumb',
         {
           alt: 'thumbAlt',
           transformDefaults: {
             position: 'bottom-right',
             jpegQuality: 80
           }
         }
    ) }}

## using craft.picture.url

For a hero image used as a background image:

    <div class="hero" style="background-image: url({{ craft.picture.url(entry.hero, 'hero') }})"></div>

## Tips

I use a twig macro which handles missing images and svg images before calling _craft.picture.element_.

I define a style _preparse_ which includes all the different transforms, and generate that style in a [Preparse](https://github.com/aelvan/Preparse-Field-Craft) field when the entry is saved to pre-build the transforms.

---
Brought to you by [Marion Newlevant](http://marion.newlevant.com). Icon insides by [Setyo Ari Wibowo](https://thenounproject.com/search/?q=picture%20frame&i=1191340)
