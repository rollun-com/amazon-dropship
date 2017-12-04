<?php

namespace rollun\amazonDropship\Client;

use AmazonOrderList;
use AmazonOrder;
use DateTime;
use rollun\callback\Callback\CallbackInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\amazonDropship\Megaplan\Aspect\Deal;
use rollun\dic\InsideConstruct;
use rollun\logger\Logger;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;
use Xiag\Rql\Parser\Node\Query\LogicOperator;

class AmazonOrderToMegaplanDealTask implements CallbackInterface, \Serializable
{
    const TRACKING_DATASTORE_INVOICE_NUMBER_KEY = 'invoice_number';

    const WAITING_FOR_SHIPPING_STATUS = 100;

    /** @var AmazonOrderList */
    protected $amazonOrderList;

    /** @var  DataStoresInterface */
    protected $megaplanDataStore;

    /** @var DataStoresInterface */
    protected $trackingNumberDataStore;

    /** @var Logger */
    protected $logger;

    /**
     * OrderClient constructor.
     * @param AmazonOrderList $amazonOrderList
     * @param DataStoresInterface $megaplanDataStore
     * @param DataStoresInterface $trackingNumberDataStore
     * @param Logger $logger
     */
    public function __construct(
        AmazonOrderList $amazonOrderList = null,
        DataStoresInterface $megaplanDataStore = null,
        DataStoresInterface $trackingNumberDataStore = null,
        Logger $logger = null
    )
    {
        InsideConstruct::setConstructParams();
    }

    /**
     * An alias for the 'getOrderList' method.
     *
     * The received value has to be an array with the following fields:
     * - 'mode': "Created" or "Modified";
     * - since_datetime: datetime value since which it needs to receive orders; can be null or absent; by default 4 minutes ago
     * = till_datetime: datetime value till which it needs to receive order; can be null or absent; by default 4 minutes ago
     * If both datetime values are null then since_datetime will be shifted to 150 seconds ago relatively till_datetime.
     *
     * @param array $value
     * @return array|bool
     * @throws AmazonOrderTaskException
     */
    public function __invoke($value)
    {
        try {
            $orderList = $this->getOrderList($value);
            $this->log('debug', count($orderList) . " order(-s) was/were found");
            foreach ($orderList as $order) {
                /** AmazonOrder $order */
                try {
                    $item = $this->megaplanDataStore->create($this->fetchMegaplanItemData($order), true);
                    $message = "Item was created: " . print_r($item, 1);
                    $logLevel = 'debug';
                } catch (\Exception $e) {
                    $message = "Item wasn't created for reason: " . $e->getMessage();
                    $logLevel = 'critical';
                } finally {
                    $this->log($logLevel, $message);
                }
            }
        } catch (\Exception $e) {
            $this->log('critical', "Can't create deals for the following reason: " . $e->getMessage());
        }
        try {
            $this->findTrackingNumbersAndSetThemToMegaplanDeals();
        } catch (\Exception $e) {
            $this->log('critical', "Can't find tracking numbers for the deals for the following reason: " . $e->getMessage());
        }
    }

    /**
     * Checks specified parameters and extract them.
     *
     * If there are no parameters of date period insert them into returned data.
     * If the parameters have some wrong structure throws an exception.
     *
     * @param $value
     * @return mixed
     * @throws AmazonOrderTaskException
     */
    protected function checkIncomingParameters($value)
    {
        if (!isset($value['mode'])) {
            throw new AmazonOrderTaskException("The required parameter \"mode\" is expected");
        }
        if (!in_array($value['mode'], ['Created', 'Modified'])) {
            throw new AmazonOrderTaskException("The parameter \"mode\" has to have a value 'Created' or 'Modified' only");
        }
        if (!isset($value['since_datetime'])) {
            $value['since_datetime'] = "-1 Hours";
        }
        if (!isset($value['till_datetime'])) {
            $value['till_datetime'] = null;
        }
        return $value;
    }

    /**
     * Returns Amazon order list in the array view where each element is an AmazonOrder object
     *
     * @param $value
     * @return array|bool
     * @throws AmazonOrderTaskException
     */
    public function getOrderList($value)
    {
        $value = $this->checkIncomingParameters($value);
        $sinceDateTime = $value['since_datetime'];

        if (!is_null($sinceDateTime)) {
            $sinceDateTime = min(new DateTime(date("Y-m-d H:i:s", strtotime($sinceDateTime))), $this->getLastSuccessfulStartTime());
            $sinceDateTime = $sinceDateTime->format(DateTime::RFC3339);
        }

        $tillDateTime = $value['till_datetime'] ? new DateTime(date("Y-m-d H:i:s", strtotime($value['till_datetime']))) : new DateTime();
        $tillDateTime = $tillDateTime->format(DateTime::RFC3339);

        $this->log('debug', "Try to receive orders since {$sinceDateTime} till {$tillDateTime}");

        // A Hardcode!! By default Amazon provides both types of orders: FBA and MFN. We are only interested in MFN.
        // if you ever need another value you have to change this row, comment it out or remove.
        $this->amazonOrderList->setFulfillmentChannelFilter("MFN");

        $this->amazonOrderList->setLimits($value['mode'], $sinceDateTime, $tillDateTime);
        $this->amazonOrderList->setUseToken();
        $this->amazonOrderList->fetchOrders();
        $result = $this->amazonOrderList;

        if ($result !== false) {
            return $this->amazonOrderList->getList();
        } else {
            throw new AmazonOrderTaskException("Something went wrong during getting order list");
        }
    }

    /**
     * Fetches necessary parameters from the Amazon order
     *
     * @param AmazonOrder $order
     * @return array
     * @throws AmazonOrderTaskException
     */
    protected function fetchMegaplanItemData(AmazonOrder $order)
    {
        $megaplanItemData = [
            Deal::AMAZON_ORDER_ID_KEY => $order->getAmazonOrderId(),
            Deal::PAYMENTS_DATE_KEY => $order->getPurchaseDate(),
            Deal::MERCHANT_ORDER_ID_KEY => $order->getSellerOrderId(),
            Deal::TRACKING_NUMBER_KEY => null,
        ];
        if ('Shipped' == $order->getOrderStatus()) {
            $megaplanItemData[Deal::TRACKING_NUMBER_KEY] = $this->getTrackingNumber($order->getAmazonOrderId());
        }
        return $megaplanItemData;
    }

    /**
     * Gets tracking number from a remote dataStore.
     *
     * @todo: this method is not a responsibility of this class. It has to be moved to another service/class.
     *
     * @param $merchantOrderId
     * @return mixed
     * @throws AmazonOrderTaskException
     */
    public function getTrackingNumber($merchantOrderId)
    {
        $query = new Query();
        $node = new ScalarOperator\EqNode(
            static::TRACKING_DATASTORE_INVOICE_NUMBER_KEY, $merchantOrderId
        );
        $query->setQuery($node);
        $items = $this->trackingNumberDataStore->query($query);
        switch (count($items)) {
            case 0:
                $trackingNumber = null;
                break;
            case 1:
                $item = array_shift($items);
                /*
                 * There may be several numbers
                 * This field will return JSON string which is decoded to an array of the following structure (for example):
                 * Array
                 * (
                 *     [0] => Array
                 *         (
                 *             [tracking_number] => C11573505555568
                 *             [scanned_date] => 1507199520
                 *             [status] => DELIVERED  Delivered  JUBA KENYON
                 *         )
                 *
                 *     [1] => Array
                 *         (
                 *             [tracking_number] => C11573505555922
                 *             [scanned_date] => 1507199520
                 *             [status] => DELIVERED  Delivered  JUBA KENYON
                 *         )
                 *
                 *     [2] => Array
                 *         (
                 *             [tracking_number] => C11573505556524
                 *             [scanned_date] => 1507199520
                 *             [status] => DELIVERED  Delivered  JUBA KENYON
                 *         )
                 *
                 * )
                 * So we have to collect all the 'tracking_number' values from entire array to a single string,
                 * where values are separated by coma.
                 */

                $trackingJson = $item['tracking_data'];
                $trackingAssoc = json_decode($trackingJson, true);
                $trackingNumber = [];
                if (!isset($trackingAssoc['tracking'])) {
                    $trackingNumber = null;
                } else {
                    foreach ($trackingAssoc['tracking'] as $item) {
                        $trackingNumber[] = $item['tracking_number'];
                    }
                    $trackingNumber = join(", ", $trackingNumber);
                }
                break;
            default:
                throw new AmazonOrderTaskException("There are a few orders with the same tracking number");
                break;
        }
        return $trackingNumber;
    }

    /**
     * Gets tracking numbers for all deals in status "Waiting for shipping" which don't have one.
     */
    public function findTrackingNumbersAndSetThemToMegaplanDeals()
    {
        $query = new Query();
        $andNode = new LogicOperator\AndNode([
            new ScalarOperator\EqNode('Status', static::WAITING_FOR_SHIPPING_STATUS),
            new ScalarOperator\EqNode('Category1000060CustomFieldTrekNomer', null),
        ]);
        $query->setQuery($andNode);

        $this->log('debug', "Trying to search deals with the status = " . static::WAITING_FOR_SHIPPING_STATUS);
        $deals = $this->megaplanDataStore->query($query);
        switch (count($deals)) {
            case 0:
                $logMessage = "No deals were found";
                break;
            case 1:
                $logMessage = "One deal was found";
                break;
            default:
                $logMessage = count($deals) . " deals were found";
                break;
        }
        $this->log('debug', $logMessage);

        $trackingNumbersFound = 0;
        foreach($deals as $deal) {
            $amazonOrderId = $deal[$this->megaplanDataStore->getMappedField(Deal::AMAZON_ORDER_ID_KEY)];
            $merchantOrderId = $deal[$this->megaplanDataStore->getMappedField(Deal::MERCHANT_ORDER_ID_KEY)];
            $orderDate = $deal[$this->megaplanDataStore->getMappedField(Deal::PAYMENTS_DATE_KEY)];
            try {
                $trackingNumber = $this->getTrackingNumber($merchantOrderId);
                if ($trackingNumber) {
                    $trackingNumbersFound++;
                    $megaplanItemData = [
                        Deal::AMAZON_ORDER_ID_KEY => $amazonOrderId,
                        Deal::PAYMENTS_DATE_KEY => $orderDate,
                        Deal::MERCHANT_ORDER_ID_KEY => $merchantOrderId,
                        Deal::TRACKING_NUMBER_KEY => $trackingNumber,
                    ];
                    $this->megaplanDataStore->update($megaplanItemData, false);
                    $logMessage = "A tracking number for the Amazon order \"{$amazonOrderId}\" from {$orderDate} (Megaplan deal Id=\"{$deal['Id']}\") was found: {$trackingNumber}.";
                    $logLevel = 'debug';
                } else {
                    $logMessage = "A tracking number for the Amazon order \"{$amazonOrderId}\" from {$orderDate} (Megaplan deal Id=\"{$deal['Id']}\") wasn't found.";
                    $logLevel = 'debug';
                }
            } catch (\Exception $e) {
                $logMessage = "Can't find and update a tracking number for the next reason: " . $e->getMessage();
                $logLevel = 'critical';
            } finally {
                $this->log($logLevel, $logMessage);
            }
        }
        $this->log('debug', "Total of {$trackingNumbersFound} number(-s) was/were processed.");
    }

    /**
     * Just relaying mock mode and mock-file to the original object. This is used it tests
     *
     * @param bool|true $b
     * @param null $files
     * @see \AmazonCore::setMock
     */
    public function setMock($b = true, $files = null)
    {
        $this->amazonOrderList->setMock($b, $files);
    }

    /**
     * Just relays all messages to the logger if it is set.
     *
     * @param $logLevel
     * @param $message
     */
    protected function log($logLevel, $message)
    {
        if ($this->logger) {
            $this->logger->log($logLevel, $message);
        }
    }

    /**
     * Returns an empty string because during unserializing the InsideConstruct will create all the necessary parameters/services
     *
     * @return string
     */
    public function serialize()
    {
        return "";
    }

    /**
     * Just calls the constructor and InsideConstruct will create all the necessary parameters/services
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->__construct();
    }

    /**
     * Returns last successful start time from some remote dataStore or another store.
     *
     * @todo: now the method returns a constant. But it has to return this value from another store.
     *
     * @return bool|string
     */
    protected function getLastSuccessfulStartTime()
    {
        return new DateTime(date("Y-m-d H:i:s", strtotime("-3 Hours")));
    }
}