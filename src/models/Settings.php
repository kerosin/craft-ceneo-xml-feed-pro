<?php
/**
 * Ceneo Xml Feed Pro plugin for Craft CMS 3.x
 *
 * @link      https://github.com/kerosin
 * @copyright Copyright (c) 2021 kerosin
 */

namespace kerosin\ceneoxmlfeedpro\models;

use Craft;
use craft\base\Model;
use craft\commerce\errors\CurrencyException;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\ArrayHelper;

/**
 * @author    kerosin
 * @package   CeneoXmlFeedPro
 * @since     1.0.0
 */
class Settings extends Model
{
    // Constants
    // =========================================================================

    const OPTION_CUSTOM_VALUE = '__custom_value__';

    /**
     * @since 1.1.0
     */
    const FILTER_STATUS_LIVE = 'live';
    /**
     * @since 1.1.0
     */
    const FILTER_STATUS_PENDING = 'pending';
    /**
     * @since 1.1.0
     */
    const FILTER_STATUS_EXPIRED = 'expired';
    /**
     * @since 1.1.0
     */
    const FILTER_STATUS_ENABLED = 'enabled';
    /**
     * @since 1.1.0
     */
    const FILTER_STATUS_DISABLED = 'disabled';
    /**
     * @since 1.1.0
     */
    const FILTER_STATUS_ARCHIVED = 'archived';

    // Public Properties
    // =========================================================================

    /**
     * Currency.
     *
     * @var string
     */
    public $currency;

    /**
     * Include variants.
     *
     * @var bool
     */
    public $includeVariants = false;

    /**
     * Use product URL for variants.
     *
     * @var bool
     */
    public $useProductUrl = true;

    /**
     * Use product data for variants.
     *
     * @var bool
     */
    public $useProductData = true;

    /**
     * ID [id] field.
     *
     * @var string
     */
    public $idField;

    /**
     * Price [price] field.
     *
     * @var string
     */
    public $priceField;

    /**
     * Avail [avail] field.
     *
     * @var string
     */
    public $availField;

    /**
     * Avail custom value.
     *
     * @var string
     */
    public $availCustomValue;

    /**
     * Weight [weight] field.
     *
     * @var string
     */
    public $weightField;

    /**
     * Basket [basket] field.
     *
     * @var string
     */
    public $basketField;

    /**
     * Basket custom value.
     *
     * @var string
     */
    public $basketCustomValue;

    /**
     * Stock [stock] field.
     *
     * @var string
     */
    public $stockField;

    /**
     * Stock custom value.
     *
     * @var string
     */
    public $stockCustomValue;

    /**
     * Cat [cat] field.
     *
     * @var string
     */
    public $catField;

    /**
     * Cat custom value.
     *
     * @var string
     */
    public $catCustomValue;

    /**
     * Name [name] field.
     *
     * @var string
     */
    public $nameField;

    /**
     * Imgs main [main] field.
     *
     * @var string
     */
    public $imgsMainField;

    /**
     * Imgs I [i] field.
     *
     * @var string
     */
    public $imgsIField;
    
    /**
     * Desc [desc] field.
     *
     * @var string
     */
    public $descField;

    /**
     * Attrs [attrs].
     *
     * @var array
     */
    public $attrs;

    /**
     * Entry status filter.
     *
     * @var array
     * @since 1.1.0
     */
    public $entryStatusFilter = [self::FILTER_STATUS_LIVE];

    /**
     * Entry type filter.
     *
     * @var array
     * @since 1.1.0
     */
    public $entryTypeFilter = [];

    /**
     * Entry category filter.
     *
     * @var array
     * @since 1.1.0
     */
    public $entryCategoryFilter = [];

    /**
     * Product status filter.
     *
     * @var array
     * @since 1.1.0
     */
    public $productStatusFilter = [self::FILTER_STATUS_LIVE];

    /**
     * Product type filter.
     *
     * @var array
     * @since 1.1.0
     */
    public $productTypeFilter = [];

    /**
     * Product category filter.
     *
     * @var array
     * @since 1.1.0
     */
    public $productCategoryFilter = [];

    /**
     * Product available for purchase filter.
     *
     * @var string
     * @since 1.1.0
     */
    public $productAvailableForPurchaseFilter;

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public static function getCmsStandardFields(): array
    {
        return [
            'id' => Craft::t('ceneo-xml-feed-pro', 'ID'),
            'title' => Craft::t('ceneo-xml-feed-pro', 'Title'),
            'expiryDate' => Craft::t('ceneo-xml-feed-pro', 'Expiry Date'),
        ];
    }

    /**
     * @return array
     */
    public static function getCommerceStandardFields(): array
    {
        return [
            'sku' => Craft::t('ceneo-xml-feed-pro', 'SKU'),
            'price' => Craft::t('ceneo-xml-feed-pro', 'Price'),
            'salePrice' => Craft::t('ceneo-xml-feed-pro', 'Sale Price'),
            'stock' => Craft::t('ceneo-xml-feed-pro', 'Stock'),
            'length' => Craft::t('ceneo-xml-feed-pro', 'Dimensions (L)'),
            'width' => Craft::t('ceneo-xml-feed-pro', 'Dimensions (W)'),
            'height' => Craft::t('ceneo-xml-feed-pro', 'Dimensions (H)'),
            'weight' => Craft::t('ceneo-xml-feed-pro', 'Weight'),
        ];
    }

    /**
     * @return array
     */
    public function getStandardFields(): array
    {
        $result = self::getCmsStandardFields();

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            $result = array_merge($result, self::getCommerceStandardFields());
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getCustomFields(): array
    {
        $result = [];

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            $result[$field->handle] = $field->name;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getFieldOptions(): array
    {
        $result = [];
        $fields = $this->getStandardFields();

        if (count($fields)) {
            $result[] = ['optgroup' => Craft::t('ceneo-xml-feed-pro', 'Standard Fields')];

            foreach ($fields as $handle => $name) {
                $result[] = [
                    'value' => $handle,
                    'label' => $name,
                ];
            }
        }

        $fields = $this->getCustomFields();

        if (count($fields)) {
            $result[] = ['optgroup' => Craft::t('ceneo-xml-feed-pro', 'Custom Fields')];

            foreach ($fields as $handle => $name) {
                $result[] = [
                    'value' => $handle,
                    'label' => $name,
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getCurrencyOptions(): array
    {
        $result = [];

        if (!Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            return $result;
        }

        try {
            $currencies = CommercePlugin::getInstance()->getPaymentCurrencies()->getAllPaymentCurrencies();

            foreach ($currencies as $currency) {
                $result[] = [
                    'value' => $currency->iso,
                    'label' => $currency->iso,
                ];
            }
        } catch (CurrencyException $e) {
        }

        return $result;
    }

    /**
     * @return array
     * @since 1.1.0
     */
    public function getStatusFilterOptions(): array
    {
        return [
            self::FILTER_STATUS_LIVE => Craft::t('ceneo-xml-feed-pro', 'Live'),
            self::FILTER_STATUS_PENDING => Craft::t('ceneo-xml-feed-pro', 'Pending'),
            self::FILTER_STATUS_EXPIRED => Craft::t('ceneo-xml-feed-pro', 'Expired'),
            self::FILTER_STATUS_ENABLED => Craft::t('ceneo-xml-feed-pro', 'Enabled'),
            self::FILTER_STATUS_DISABLED => Craft::t('ceneo-xml-feed-pro', 'Disabled'),
            self::FILTER_STATUS_ARCHIVED => Craft::t('ceneo-xml-feed-pro', 'Archived'),
        ];
    }

    /**
     * @return array
     * @since 1.1.0
     */
    public function getEntryTypeFilterOptions(): array
    {
        $result = [];
        $sections = Craft::$app->getSections()->getAllSections();

        foreach ($sections as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $result[] = [
                    'value' => $entryType->id,
                    'label' => Craft::t('site', $section->name) . ' - ' . Craft::t('site', $entryType->name),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     * @since 1.1.0
     */
    public function getProductTypeFilterOptions(): array
    {
        $result = [];

        if (!Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            return $result;
        }

        $productTypes = CommercePlugin::getInstance()->getProductTypes()->getAllProductTypes();

        foreach ($productTypes as $productType) {
            $result[] = [
                'value' => $productType->id,
                'label' => Craft::t('site', $productType->name),
            ];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $fieldOptions = array_merge(
            [
                self::OPTION_CUSTOM_VALUE,
            ],
            array_keys($this->getStandardFields()),
            array_keys($this->getCustomFields())
        );
        $currencyOptions = array_keys($this->getCurrencyOptions());
        $statusFilterOptions = array_keys($this->getStatusFilterOptions());
        $entryTypeFilterOptions = ArrayHelper::getColumn($this->getEntryTypeFilterOptions(), 'value');
        $productTypeFilterOptions = ArrayHelper::getColumn($this->getProductTypeFilterOptions(), 'value');

        return [
            ['currency', 'in', 'range' => $currencyOptions],
            ['includeVariants', 'boolean'],
            ['useProductUrl', 'boolean'],
            ['useProductData', 'boolean'],
            ['idField', 'in', 'range' => $fieldOptions],
            ['priceField', 'in', 'range' => $fieldOptions],
            ['availField', 'in', 'range' => $fieldOptions],
            ['weightField', 'in', 'range' => $fieldOptions],
            ['basketField', 'in', 'range' => $fieldOptions],
            ['stockField', 'in', 'range' => $fieldOptions],
            ['catField', 'in', 'range' => $fieldOptions],
            ['nameField', 'in', 'range' => $fieldOptions],
            ['imgsMainField', 'in', 'range' => $fieldOptions],
            ['imgsIField', 'in', 'range' => $fieldOptions],
            ['descField', 'in', 'range' => $fieldOptions],
            ['entryStatusFilter', 'in', 'allowArray' => true, 'range' => $statusFilterOptions],
            ['entryTypeFilter', 'in', 'allowArray' => true, 'range' => $entryTypeFilterOptions],
            ['productStatusFilter', 'in', 'allowArray' => true, 'range' => $statusFilterOptions],
            ['productTypeFilter', 'in', 'allowArray' => true, 'range' => $productTypeFilterOptions],
        ];
    }
}
