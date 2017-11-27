<?php

namespace rollun\parser\TuckerRocky;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\parser\UnknownFieldTypeException;
use rollun\parser\InvalidArgumentException;
use SplFileObject;

class PriceParser
{
    const STATUS_ENUM = [
        ' ' => '',
        'C' => 'Closeout',
        'D' => 'Discontinued',
    ];

    const AVAILABILITY_ENUM = [
        'Z' => 'Zero',
        'L' => 'Limited',
        'S' => 'Sufficient',
    ];

    const YES_NO = [
        'Y' => 'Yes',
        'N' => 'No',
    ];

    protected $schema = [
        'Item' => [
            'start' => 1,
            'length' => 6,
            'type' => 'string',
        ],
        'Status' => [
            'start' => 7,
            'length' => 1,
            'type' => 'enum',
            'enum' => self::STATUS_ENUM,
        ],
        'Availability' => [
            'start' => 8,
            'length' => 1,
            'type' => 'enum',
            'enum' => self::AVAILABILITY_ENUM,
        ],
        'In-Transit' => [
            'start' => 9,
            'length' => 1,
            'type' => 'enum',
            'enum' => self::YES_NO,
        ],
        'Inventory Qty' => [
            'start' => 10,
            'length' => 1,
            'type' => 'int',
        ],
        'Standard Price' => [
            'start' => 11,
            'length' => 8,
            'type' => 'float',
        ],
        'Best Price' => [
            'start' => 19,
            'length' => 8,
            'type' => 'float',
        ],
        'Retail Price' => [
            'start' => 27,
            'length' => 8,
            'type' => 'float',
        ],
        'Drop Ship Availability' => [
            'start' => 35,
            'length' => 1,
            'type' => 'enum',
            'enum' => self::AVAILABILITY_ENUM,
        ],
        'Drop Ship Inventory Qty' => [
            'start' => 36,
            'length' => 1,
            'type' => 'int',
        ],
        // May absent in a price row!
        'Next Expected PO Due Date' => [
            'start' => 37,
            'length' => 6,
            'type' => 'date',
        ],
    ];

    /** @var SplFileObject */
    protected $priceListFile;

    /** @var DataStoresInterface */
    protected $dataStore;

    /**
     * RockyMountain constructor.
     * @param $filename
     * @param DataStoresInterface $dataStore
     * @throws InvalidArgumentException
     */
    public function __construct($filename, DataStoresInterface $dataStore)
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("The file \"{$filename}\" doesn't exist");
        }
        $this->priceListFile = new SplFileObject($filename);
        $this->priceListFile->openFile("rb");
        $this->dataStore = $dataStore;
    }

    /**
     * @return DataStoresInterface
     */
    public function getDataStore()
    {
        return $this->dataStore;
    }

    /**
     * Parses received row (string)
     *
     * @param $row
     * @return array
     * @throws InvalidArgumentException
     * @throws UnknownFieldTypeException
     */
    protected function parseRow($row)
    {
        if (!is_string($row)) {
            throw new InvalidArgumentException("Specified parameter for parsing has to be a string");
        }
        if (!strlen($row)) {
            return [];
        }
        $itemData = [
            'id' => null,
        ];

        // hard code: the last parameter may absent
        $schemaKeys = array_keys($this->schema);
        $lastElementKey = array_pop($schemaKeys);

        $lastElementAbsents = false;
        if (strlen($row) == $this->schema[$lastElementKey]['start'] - 1) {
            $lastElementAbsents = true;
        }

        foreach ($this->schema as $key => $params) {
            // If it's the last element in the schema and it absents in the price row, skip this iteration
            if ($lastElementKey == $key && $lastElementAbsents) {
                $itemData[$key] = '';
                continue;
            }
            // get value
            $value = substr($row, $params['start'] - 1, $params['length']);
            // cast the value's type
            switch ($params['type']) {
                case 'string':
                    break;
                case 'int':
                    $value = intval($value);
                    break;
                case 'float':
                    $value = floatval($value);
                    break;
                case 'date':
                    // usually the date from price row has a view like "MMDDYY", f.e. "091917"
                    $value = preg_replace("/(\d{2})(\d{2})(\d{2})/", "$2-$1-20$3", $value);
                    break;
                case 'enum':
                    if (!isset($params['enum'])) {
                        throw new InvalidArgumentException("For the enum type an enumeration array is required");
                    }
                    $value = $params['enum'][$value];
                    break;
                default:
                    throw new UnknownFieldTypeException("Unknown field type \"{$params['type']}\"");
                    break;
            }
            $itemData[$key] = $value;
        }
        $itemData['id'] = $itemData['Item'];
        return $itemData;
    }

    /**
     * Reads entire file, parses all its rows, and saves result to a dataStore
     */
    function __invoke()
    {
        $this->priceListFile->rewind();
        while (!$this->priceListFile->eof()) {
            $priceLine = trim($this->priceListFile->current());
            $itemData = $this->parseRow($priceLine);
            if (count($itemData)) {
                $this->dataStore->create($itemData, true);
            }
            $this->priceListFile->next();
        }
    }
}