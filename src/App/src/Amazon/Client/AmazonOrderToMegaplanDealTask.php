<?php

namespace rollun\application\App\Amazon\Client;

use AmazonOrderList;
use AmazonOrder;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\application\App\Megaplan\Aspect\Deal;
use rollun\logger\Logger;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;

class AmazonOrderToMegaplanDealTask implements InterruptorInterface
{
    const TRACKING_DATASTORE_INVOICE_NUMBER_KEY = 'invoice_number';

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
        AmazonOrderList $amazonOrderList,
        DataStoresInterface $megaplanDataStore,
        DataStoresInterface $trackingNumberDataStore,
        Logger $logger = null
    )
    {
        $this->amazonOrderList = $amazonOrderList;
        $this->trackingNumberDataStore = $trackingNumberDataStore;
        $this->megaplanDataStore = $megaplanDataStore;
        $this->logger = $logger;
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
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->log('critical', "Item wasn't created for reason: " . $e->getMessage());
            }
            return;
        }
        foreach ($orderList as $order) {
            /** AmazonOrder $order */
            try {
                $item = $this->megaplanDataStore->create($this->fetchMegaplanItemData($order));
                $message = "Item was created: " . print_r($item, 1);
                $logLevel = 'info';
            } catch (\Exception $e) {
                $message = "Item wasn't created for reason: " . $e->getMessage();
                $logLevel = 'critical';
            } finally {
                if ($this->logger) {
                    $this->logger->log($logLevel, $message);
                }
            }
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
            $value['since_datetime'] = null;
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
        $this->amazonOrderList->setLimits($value['mode'], $value['since_datetime'], $value['till_datetime']);
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
        ];
        if ('Shipped' == $order->getOrderStatus()) {
            $megaplanItemData[Deal::TRACKING_NUMBER_KEY] = $this->getTrackingNumber($order->getAmazonOrderId());
        }
        return $megaplanItemData;
    }

    /**
     * Gets tracking number from a remote dataStore.
     *
     * @param $amazonOrderId
     * @return mixed
     * @throws AmazonOrderTaskException
     */
    public function getTrackingNumber($amazonOrderId)
    {
        $query = new Query();
        $node = new ScalarOperator\EqNode(
            static::TRACKING_DATASTORE_INVOICE_NUMBER_KEY, $amazonOrderId
        );
        $query->setQuery($node);
        $items = $this->trackingNumberDataStore->query($query);
        switch (count($items)) {
            case 0:
                throw new AmazonOrderTaskException("There is no order with specified tracking number");
                break;
            case 1:
                $item = array_shift($items);
                break;
            default:
                throw new AmazonOrderTaskException("There are a few orders with the same tracking number");
                break;
        }
        return $item['tracking_number'];
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
}