<?php

namespace rollun\amazonItemSearch\Client\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\ApaiIO;

/**
 * Class ApaiIOFactory
 *
 * <code>
 * 'ApaiIO' => [
 *     'country' => 'com', // The country could be one of the following: de, com, co.uk, ca, fr, co.jp, it, cn, es, in, com.br, com.mx, com.au
 *     'access_key' => '',
 *     'secret_key' => '',
 *     'associate_tag' => '',
 * ],
 * </code>
 *
 * @package rollun\amazonItemSearch\Client\Factory
 */
class ApaiIOFactory implements  FactoryInterface
{
    const APAIIO_KEY = 'ApaiIO';

    const COUNTRY_KEY = 'country';

    const ACCESS_KEY_KEY = 'access_key';

    const SECRET_KEY_KEY = 'secret_key';

    const ASSOCIATE_TAG_KEY = 'associate_tag';

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // These can to be removed to config and/or be used via own factories
        $conf = new GenericConfiguration();
        $client = new \GuzzleHttp\Client();
        $req = new \ApaiIO\Request\GuzzleRequest($client);
        $responseTransformer = new \ApaiIO\ResponseTransformer\XmlToArray();

        $config = $container->get('config');
        if (!isset($config[static::APAIIO_KEY])) {
            throw new ServiceNotFoundException("There is no config for the ApaiIO client in the config");
        }
        $serviceConfig = $config[static::APAIIO_KEY];
        if (
            !isset($serviceConfig[static::COUNTRY_KEY]) ||
            !isset($serviceConfig[static::ACCESS_KEY_KEY]) ||
            !isset($serviceConfig[static::SECRET_KEY_KEY]) ||
            !isset($serviceConfig[static::ASSOCIATE_TAG_KEY])
        ) {
            throw new ServiceNotCreatedException("The service wasn't created because required parameters weren't found");
        }

        $conf
            ->setCountry($serviceConfig[static::COUNTRY_KEY])
            ->setAccessKey($serviceConfig[static::ACCESS_KEY_KEY])
            ->setSecretKey($serviceConfig[static::SECRET_KEY_KEY])
            ->setAssociateTag($serviceConfig[static::ASSOCIATE_TAG_KEY])
            ->setRequest($req)
            ->setResponseTransformer($responseTransformer);
        $instance = new ApaiIO($conf);
        return $instance;
    }
}