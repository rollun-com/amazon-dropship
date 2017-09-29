<?php

namespace rollun\application\App\Amazon\Client;

use AmazonOrderList;

class OrderClient
{
    /** @var AmazonOrderList */
    protected $amazonOrderList;

    /**
     * OrderClient constructor.
     * @param AmazonOrderList $amazonOrderList
     */
    public function __construct(AmazonOrderList $amazonOrderList)
    {
        $this->amazonOrderList = $amazonOrderList;
    }

    /**
     * Returns Amazon order list in the array view where each element is an AmazonOrder object
     *
     * @return array|bool
     * @throws OrderClientException
     */
    public function getOrderList()
    {
        // TODO: Как-то надо передавать из конфига минимальный и максимальный временной интервал и режим выборки
        $this->amazonOrderList->setLimits('Modified', "2017-09-29 00:00:00");
        $this->amazonOrderList->setUseToken();
        $this->amazonOrderList->fetchOrders();
        $result = $this->amazonOrderList;

        if ($result !== false) {
            return $this->amazonOrderList->getList();
        } else {
            throw new OrderClientException("Something went wrong during getting order list");
        }
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