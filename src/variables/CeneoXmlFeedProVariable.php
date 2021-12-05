<?php
/**
 * Ceneo Xml Feed Pro plugin for Craft CMS 3.x
 *
 * @link      https://github.com/kerosin
 * @copyright Copyright (c) 2021 kerosin
 */

namespace kerosin\ceneoxmlfeedpro\variables;

use kerosin\ceneoxmlfeedpro\CeneoXmlFeedPro;
use kerosin\ceneoxmlfeedpro\services\CeneoXmlFeedProService;

use craft\base\Element;

use Exception;

/**
 * @author    kerosin
 * @package   CeneoXmlFeedPro
 * @since     1.0.0
 */
class CeneoXmlFeedProVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param Element[] $elements
     * @return void
     * @throws Exception
     */
    public function generateFeed(array $elements): void
    {
        $this->getService()->generateFeed($elements);
    }

    /**
     * @param Element $element
     * @param string|null $field
     * @param mixed $customValue
     * @return mixed
     * @throws Exception
     */
    public function elementFieldValue(Element $element, ?string $field, $customValue = null)
    {
        return $this->getService()->getElementFieldValue($element, $field, $customValue);
    }

    /**
     * @param Element $element
     * @return string|null
     * @throws Exception
     */
    public function elementUrl(Element $element): ?string
    {
        return $this->getService()->getElementUrl($element);
    }

    /**
     * @param string|null $value
     * @return bool
     */
    public function isCustomValue(?string $value): bool
    {
        return $this->getService()->isCustomValue($value);
    }

    /**
     * @param Element $element
     * @return bool
     * @throws Exception
     */
    public function isIncludeElementVariants(Element $element): bool
    {
        return $this->getService()->isIncludeElementVariants($element);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return CeneoXmlFeedProService
     */
    protected function getService(): CeneoXmlFeedProService
    {
        return CeneoXmlFeedPro::$plugin->ceneoXmlFeedProService;
    }
}
