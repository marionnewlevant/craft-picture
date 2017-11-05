<?php

/* configuration is very site specific, so you will want to
make a craft/config/picture.php file with configuration for your
site. The configuration file is documented in the plugin README
*/
return array(
  'imageStyles' => array(
    // imageStyles is array of styles. The 'default' style is the fallback
    'default' => array(
      'img' => array(
        'widths' => array(100)
      )
    ),
  ),
);