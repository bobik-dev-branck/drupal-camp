<?php

namespace Drupal\exchange_rates;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 *  Provided Exchange Rates Service.
 */
class ExchangeRatesService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an ExchangeRatesService object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $config_factory) {
    $this->client = $client;
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the value of the config name of the configuration object to construct.
   *
   * @param $configName
   *  The config name.
   *
   * @return string|boolean|null
   */
  public function getConfig($configName) {
    $config = $this->configFactory->get('exchange_rates.settings');

    return $config->get($configName);
  }

  /**
   * Gets Exchange Rates with API.
   *
   * @param string $url
   *  The url to API Exchange Rates.
   *
   * @return array|null
   */

  public function getExchangeRates ($url) {
    try {
      $request = $this->client->get($url)->getBody();
      $exchangeRates = json_decode($request);
      foreach ($exchangeRates as $exchangeRate) {
        $data[$exchangeRate->cc] = $exchangeRate->rate;
      }
    }catch (RequestException $e) {
      return;
    }

    return $data;
  }

}
