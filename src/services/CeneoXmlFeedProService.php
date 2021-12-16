<?php
/**
 * Ceneo Xml Feed Pro plugin for Craft CMS 3.x
 *
 * @link      https://github.com/kerosin
 * @copyright Copyright (c) 2021 kerosin
 */

namespace kerosin\ceneoxmlfeedpro\services;

use kerosin\ceneoxmlfeedpro\CeneoXmlFeedPro;
use kerosin\ceneoxmlfeedpro\models\Settings;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craft\elements\Entry;
use craft\fields\data\OptionData;
use craft\helpers\ArrayHelper;
use craft\web\View;

use DateTime;
use Exception;

/**
 * @author    kerosin
 * @package   CeneoXmlFeedPro
 * @since     1.0.0
 */
class CeneoXmlFeedProService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @param mixed $criteria
     * @return Entry[]
     * @throws Exception
     */
    public function getFeedEntries($criteria = null): array
    {
        if (!empty($criteria)) {
            $result = Entry::findAll($criteria);
        } else {
            $query = Entry::find()
                ->site(Craft::$app->getSites()->getCurrentSite());

            $settings = $this->getSettings();

            if (!empty($settings->entryStatusFilter)) {
                $query->status($settings->entryStatusFilter);
            }

            if (!empty($settings->entryTypeFilter)) {
                $query->typeId($settings->entryTypeFilter);
            }

            if (!empty($settings->entryCategoryFilter)) {
                $query->relatedTo($settings->entryCategoryFilter);
            }

            $result = $query->all();
        }

        return $result;
    }

    /**
     * @param mixed $criteria
     * @return Product[]
     * @throws Exception
     */
    public function getFeedProducts($criteria = null): array
    {
        $result = [];

        if (!$this->isCommerceInstalled()) {
            return $result;
        }

        if (!empty($criteria)) {
            $result = Product::findAll($criteria);
        } else {
            $query = Product::find()
                ->site(Craft::$app->getSites()->getCurrentSite());

            $settings = $this->getSettings();

            if (!empty($settings->productStatusFilter)) {
                $query->status($settings->productStatusFilter);
            }

            if (!empty($settings->productTypeFilter)) {
                $query->typeId($settings->productTypeFilter);
            }

            if (!empty($settings->productCategoryFilter)) {
                $query->relatedTo($settings->productCategoryFilter);
            }

            if (!empty($settings->productAvailableForPurchaseFilter)) {
                $query->availableForPurchase(true);
            }

            $result = $query->all();
        }

        return $result;
    }

    /**
     * @param Element[] $elements
     * @return string
     * @throws Exception
     */
    public function getFeedXml(array $elements): string
    {
        if (Craft::$app->getPlugins()->getPluginInfo('ceneo-xml-feed-pro')['isTrial']) {
            $elements = array_slice($elements, 0, 10);
        }

        return Craft::$app->getView()->renderTemplate(
            'ceneo-xml-feed-pro/_feed',
            [
                'elements' => $elements,
                'settings' => $this->getSettings(),
            ],
            View::TEMPLATE_MODE_CP
        );
    }

    /**
     * @param mixed $criteria
     * @return string
     * @throws Exception
     */
    public function getEntriesFeedXml($criteria = null): string
    {
        return $this->getFeedXml($this->getFeedEntries($criteria));
    }

    /**
     * @param mixed $criteria
     * @return string
     * @throws Exception
     */
    public function getProductsFeedXml($criteria = null): string
    {
        return $this->getFeedXml($this->getFeedProducts($criteria));
    }

    /**
     * @param Element[] $elements
     * @return void
     * @throws Exception
     */
    public function generateFeed(array $elements): void
    {
        $response = Craft::$app->getResponse();
        $response->getHeaders()->set('Content-Type', 'application/xml; charset=UTF-8');

        echo $this->getFeedXml($elements);
    }

    /**
     * @param Element $element
     * @param string|null $field
     * @param mixed $customValue
     * @return mixed
     * @throws Exception
     */
    public function getElementFieldValue(Element $element, ?string $field, $customValue = null)
    {
        $result = null;

        if ($field == null) {
            return $result;
        }

        if ($this->isCustomValue($field)) {
            return $customValue;
        }

        $object = $element;

        if ($this->isCommerceInstalled()) {
            if ($element instanceof Product) {
                if (isset($element->getDefaultVariant()->{$field})) {
                    $object = $element->getDefaultVariant();
                }
            } elseif ($element instanceof Variant) {
                $product = $element->getProduct();

                if (
                    !isset($element->{$field}) &&
                    $this->getSettings()->useProductData &&
                    $product != null &&
                    isset($product->{$field})
                ) {
                    $object = $element->getProduct();
                }
            }
        }

        if (!isset($object->{$field})) {
            return $result;
        }

        $value = $object->{$field};

        if ($value instanceof DateTime) {
            $result = $value->format(DateTime::ATOM);
        } elseif ($value instanceof AssetQuery) {
            $items = $value->all();

            if (count($items)) {
                $values = [];

                foreach ($items as $item) {
                    if ($item->getUrl() != null) {
                        $values[] = $item->getUrl();
                    }
                }

                if (count($values)) {
                    $result = $values;
                }
            }
        } elseif ($value instanceof UserQuery) {
            $items = $value->all();

            if (count($items)) {
                $values = [];

                foreach ($items as $item) {
                    if ($item->username != null) {
                        $values[] = $item->username;
                    }
                }

                if (count($values)) {
                    $result = $values;
                }
            }
        } elseif ($value instanceof ElementQuery) {
            $items = $value->all();

            if (count($items)) {
                $values = [];

                foreach ($items as $item) {
                    if (isset($item->title) && $item->title != '') {
                        $values[] = $item->title;
                    }
                }

                if (count($values)) {
                    $result = $values;
                }
            }
        } elseif (ArrayHelper::isTraversable($value)) {
            if (count($value)) {
                $values = [];

                foreach ($value as $item) {
                    if ($item instanceof OptionData && isset($item->label) && $item->label != '') {
                        $values[] = $item->label;
                    } elseif ($item != null) {
                        $values[] = (string)$item;
                    }
                }

                if (count($values)) {
                    $result = $values;
                }
            }
        } elseif ($value instanceof OptionData) {
            if (isset($value->label) && $value->label != '') {
                $result = $value->label;
            } else {
                $result = (string)$value;
            }
        } else {
            $result = $value;
        }

        return $result;
    }

    /**
     * @param Element $element
     * @return string|null
     * @throws Exception
     */
    public function getElementUrl(Element $element): ?string
    {
        $result = $element->getUrl();

        if (
            $element instanceof Variant &&
            $this->getSettings()->useProductUrl &&
            $element->getProduct() != null
        ) {
            $result = $element->getProduct()->getUrl();
        }

        return $result;
    }

    /**
     * @param string|null $value
     * @return bool
     */
    public function isCustomValue(?string $value): bool
    {
        return $value == $this->getSettings()::OPTION_CUSTOM_VALUE;
    }

    /**
     * @param Element $element
     * @return bool
     * @throws Exception
     */
    public function isIncludeElementVariants(Element $element): bool
    {
        return $this->getSettings()->includeVariants &&
            $element instanceof Product &&
            $element->getType()->hasVariants;
    }

    /**
     * @return bool
     * @since 1.1.0
     */
    public function isCommerceInstalled(): bool
    {
        return Craft::$app->getPlugins()->isPluginInstalled('commerce');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return Settings
     */
    protected function getSettings(): Settings
    {
        return CeneoXmlFeedPro::$plugin->getSettings();
    }
}
