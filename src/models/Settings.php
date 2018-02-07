<?php

namespace marionnewlevant\picture\models;

use craft\base\Model;
use craft\validators\ArrayValidator;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $imageStyles = [];
    public $urlTransforms = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'imageStyles',
                    'urlTransforms',
                ],
                ArrayValidator::class,
            ],
        ];
    }
}
