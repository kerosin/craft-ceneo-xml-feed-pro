<?php
/**
 * Ceneo Xml Feed Pro plugin for Craft CMS 3.x
 *
 * @link      https://github.com/kerosin
 * @copyright Copyright (c) 2021 kerosin
 */

namespace kerosin\ceneoxmlfeedpro\assetbundles\ceneoxmlfeedpro;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    kerosin
 * @package   CeneoXmlFeedPro
 * @since     1.0.0
 */
class CeneoXmlFeedProAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        $this->sourcePath = '@kerosin/ceneoxmlfeedpro/assetbundles/ceneoxmlfeedpro/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/app.css',
        ];

        parent::init();
    }
}
