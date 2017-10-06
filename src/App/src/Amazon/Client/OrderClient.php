<?php

namespace rollun\application\App\Amazon\Client;

use AmazonOrderList;
use AmazonOrder;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\application\App\Megaplan\Aspect\Deal;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;

class OrderClient implements InterruptorInterface
{
    /** @var AmazonOrderList */
    protected $amazonOrderList;

    /** @var  DataStoresInterface */
    protected $megaplanDataStore;

    /** @var DataStoresInterface */
    protected $trackingNumberDataStore;

    /** @var DataStoresInterface */
    protected $logDataStore;

    protected $orderList;

    /**
     * OrderClient constructor.
     * @param AmazonOrderList $amazonOrderList
     * @param DataStoresInterface $megaplanDataStore
     * @param DataStoresInterface $trackingNumberDataStore
     * @param DataStoresInterface $logDataStore
     */
    public function __construct(
        AmazonOrderList $amazonOrderList,
        DataStoresInterface $megaplanDataStore,
        DataStoresInterface $trackingNumberDataStore,
        DataStoresInterface $logDataStore = null
    )
    {
        $this->amazonOrderList = $amazonOrderList;
        $this->trackingNumberDataStore = $trackingNumberDataStore;
        $this->megaplanDataStore = $megaplanDataStore;
        $this->logDataStore = $logDataStore;
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
     * @throws OrderClientException
     */
    public function __invoke($value)
    {
        $orderList = $this->getOrderList($value);
        foreach ($orderList as $order) {
            /** AmazonOrder $order */
            $item = $this->megaplanDataStore->create($this->fetchMegaplanItemData($order));
            if ($this->logDataStore) {
                $this->logDataStore->create($item);
            }
        }
    }

    protected function checkIncomingParameters($value)
    {
        if (!isset($value['mode'])) {
            throw new OrderClientException("The required parameter \"mode\" is expected");
        }
        if (!in_array($value['mode'], ['Created', 'Modified'])) {
            throw new OrderClientException("The parameter \"mode\" has to have a value 'Created' or 'Modified' only");
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
     * @throws OrderClientException
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
            throw new OrderClientException("Something went wrong during getting order list");
        }
    }

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


    public function getTrackingNumber($amazonOrderId)
    {
        $query = new Query();
        $node = new ScalarOperator\EqNode(
            Deal::AMAZON_ORDER_ID_KEY, $amazonOrderId
        );
        $query->setQuery($node);
        $item = $this->trackingNumberDataStore->query($query);
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