<?php

namespace rollun\amazonItemSearch\Client;

use ApaiIO\ApaiIO;
use ApaiIO\Operations\Search;
use rollun\callback\Callback\CallbackInterface;
use rollun\datastore\DataStore\Interfaces\DataSourceInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\dic\InsideConstruct;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;

class AmazonItemSearchTask implements CallbackInterface, \Serializable
{
    /** @var DataStoresInterface|DataSourceInterface */
    protected $brandSourceDataStore;

    /** @var DataStoresInterface|DataSourceInterface */
    protected $temporaryDataStore;

    /** @var DataStoresInterface|DataSourceInterface */
    protected $itemSearchResultDataStore;

    /** @var ApaiIO  */
    protected $amazonProductAdvertisingApiClient;

    /** @var Search */
    protected $amazonSearchOperation;

    /**
     * AmazonItemSearchTask constructor.
     * @param DataStoresInterface $brandSourceDataStore
     * @param DataStoresInterface $temporaryDataStore
     * @param DataStoresInterface $itemSearchResultDataStore
     * @param ApaiIO $amazonProductAdvertisingApiClient
     * @param Search $amazonSearchOperation
     */
    public function __construct(DataStoresInterface $brandSourceDataStore = null,
                                DataStoresInterface $temporaryDataStore = null,
                                DataStoresInterface $itemSearchResultDataStore = null,
                                $amazonProductAdvertisingApiClient = null,
                                $amazonSearchOperation = null
    )
    {
        InsideConstruct::setConstructParams();
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke($value)
    {
        // Iterate brand DataStore
        foreach ($this->brandSourceDataStore as $row) {
            // Fill out search parameters
            $this->amazonSearchOperation->setCategory($row['category'])
                ->setKeywords($row['brand']);
            $formattedResponse = $this->amazonProductAdvertisingApiClient->runOperation($this->amazonSearchOperation);

            // clear the temporary DataStore
            $this->temporaryDataStore->deleteAll();

            foreach ((array)$formattedResponse['Items']['Item'] as $item) {
                $itemData = [
                    'id' => $item['ASIN'],
                    'ASIN' => $item['ASIN'],
                    'SalesRank' => (!is_null($item['SalesRank']) ? $item['SalesRank'] : ''),
                    'Brand' => (isset($item['ItemAttributes']['Brand']) ? $item['ItemAttributes']['Brand']
                        : (isset($item['ItemAttributes']['Feature']) ? $item['ItemAttributes']['Feature'] : "")),
                    'ProductName' => $item['ItemAttributes']['Title'],
                    'BuyBoxPrice' => $item['ItemAttributes']['ListPrice']['Amount'] / 100,
                    'LowestPrice' => $item['OfferSummary']['LowestNewPrice']['Amount'] / 100,
                    'PartNumber' => $item['ItemAttributes']['PartNumber'],
                    'ManufacturerPartNumber' => $item['ItemAttributes']['MPN'],
                    'UPC' => (isset($item['ItemAttributes']['UPCList']) ? join(",", $item['ItemAttributes']['UPCList']['UPCListElement']) : ''),
                    'Model' => $item['ItemAttributes']['Model'],
                    'Merchant' => $item['Offers']['Offer']['Merchant']['Name'],
                    'Prime' =>  $item['Offers']['Offer']['OfferListing']['IsEligibleForPrime'],
                ];
                $this->temporaryDataStore->create($itemData);
            }
            // Additinal search filters
            $query = new Query();
            $leNode = new ScalarOperator\LeNode('SalesRank', 300000);
            $query->setQuery($leNode);
            $finalResults = $this->temporaryDataStore->query($query);
            // saving results
            foreach ($finalResults as $resultItem) {
                $this->itemSearchResultDataStore->create($resultItem, true);
            }
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
}