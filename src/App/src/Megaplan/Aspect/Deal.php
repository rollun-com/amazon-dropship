<?php

namespace rollun\application\App\Megaplan\Aspect;

use rollun\api\Api\Megaplan\Exception\InvalidArgumentException;
use rollun\application\App\Megaplan\MegaplanFieldProviderInterface;
use rollun\datastore\DataStore\Aspect\AspectAbstract;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;
use Xiag\Rql\Parser\Query;

/**
 * Class Megaplan\Aspect\Deal.
 *
 * This is an aspect for MegaplanDataStore. It is used for creation/update deals with data is coming from the Amazon.
 *
 * amazon_order_id
 * payments_date [UTC timestamp]
 * merchant_order_id
 * tracking_number
 *
 * @package rollun\application\Megaplan\Aspect
 */
class Deal extends AspectAbstract implements MegaplanFieldProviderInterface
{
    const AMAZON_ORDER_ID_KEY = 'amazon_order_id';
    const PAYMENTS_DATE_KEY = 'payments_date';
    const MERCHANT_ORDER_ID_KEY = 'merchant_order_id';
    const TRACKING_NUMBER_KEY = 'tracking_number';

    protected $fieldsMap = [
        self::AMAZON_ORDER_ID_KEY => 'Category1000060CustomFieldOrderId',
        self::PAYMENTS_DATE_KEY => 'Category1000060CustomFieldDataZakaza',
        self::MERCHANT_ORDER_ID_KEY => 'Category1000060CustomFieldNomerZakazaUPostavshchika',
        self::TRACKING_NUMBER_KEY => 'Category1000060CustomFieldTrekNomer',
    ];

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function preCreate($itemData, $rewriteIfExist = false)
    {
        $itemData = $this->mapItemData($itemData);
        $query = new Query();
        $node = new ScalarOperator\EqNode(
            $this->fieldsMap[static::AMAZON_ORDER_ID_KEY],
            $itemData['Model'][$this->fieldsMap[static::AMAZON_ORDER_ID_KEY]]
        );
        $query->setQuery($node);
        $result = $this->dataStore->query($query);
        if (count($result) > 1) {
            throw new InvalidArgumentException("There are \"" . count($result)
                . "\" entities with OrderID=\"" . $itemData['Model'][$this->fieldsMap[static::AMAZON_ORDER_ID_KEY]] . "\"");
        }
        $result = current($result);
        if (isset($result['Id'])) {
            $itemData['Id'] = $result['Id'];
        }
        return $itemData;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        return $this->create($itemData, true);
    }

    /**
     * Maps incoming fields to outcoming ones.
     *
     * @param $itemData
     * @return array
     * @throws InvalidArgumentException
     */
    protected function mapItemData($itemData)
    {
        // Checks data format
        $this->checkIncomingData($itemData);
        // Convert the data value from a timestamp to a string view of the data.
        $itemData[static::PAYMENTS_DATE_KEY] = date('Y-m-d', $itemData[static::PAYMENTS_DATE_KEY]);
        // Wraps data to the 'Model' field (it's requirement of the Megaplan)
        return ['Model' => array_combine(array_values($this->fieldsMap), $itemData)];
    }

    /**
     * Checks incoming data
     *
     * There are number of rules for incoming data:
     * 1. Fields 'amazon_order_id' and 'payments_date' are required.
     * 2. The fields 'payments_date' has to be a timestamp
     *
     * @param $itemData
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function checkIncomingData($itemData)
    {
        $check = (
            isset($itemData[static::AMAZON_ORDER_ID_KEY]) &&
            isset($itemData[static::PAYMENTS_DATE_KEY]) && date('Y-m-d', $itemData[static::PAYMENTS_DATE_KEY]) &&
            array_key_exists(static::MERCHANT_ORDER_ID_KEY, $itemData) &&
            array_key_exists(static::TRACKING_NUMBER_KEY, $itemData) &&
            count($itemData) == 4
        );
        if (!$check) {
            throw new InvalidArgumentException("Format of specified data is wrong");
        }
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getMappedField($fieldName)
    {
        if (isset($this->fieldsMap[$fieldName])) {
            return $this->fieldsMap[$fieldName];
        }
        return null;
    }
}