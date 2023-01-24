<?php

namespace Drupal\exchange_rates;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides functionality for fetching exchange rates via API.
 */
class ExchangeRatesService {

  use StringTranslationTrait;

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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger, Connection $database) {
    $this->client = $client;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->database = $database;
  }

  /**
   * Gets the value of the config name of the configuration object to construct.
   *
   * @param string $configName
   *   The config name.
   *
   * @return string|bool|null
   *   The default settings for block.
   */
  public function getConfig($configName) {
    $config = $this->configFactory->get('exchange_rates.settings');

    return $config->get($configName);
  }

  /**
   * Builds URL with all get parameters.
   *
   * @param string $url
   *   The static part of URL for API.
   * @param string|null $startRange
   *   Start of range in parameters.
   * @param string|null $endRange
   *   End of range in parameters.
   *
   * @return string
   *   The full URL for API.
   */
  public function buildUrl($url, $startRange = NULL, $endRange = NULL) {
    if (!$startRange) {
      $startRange = date('Ymd');
    }

    if (!$endRange) {
      $endRange = date('Ymd');
    }

    $tail = '&sort=exchangedate&order=desc&json';

    $fullUrl = $url . '?start=' . $startRange . '&end=' . $endRange . $tail;

    return $fullUrl;
  }

  /**
   * Gets Exchange Rates with API.
   *
   * @param string $url
   *   The url to API Exchange Rates.
   *
   * @return array|null
   *   The data with API, NULL otherwise.
   */
  public function getExchangeRates($url) {
    try {
      $request = $this->client->get($url)->getBody();
      $exchangeRates = json_decode($request);
      $i = 0;

      foreach ($exchangeRates as $exchangeRate) {
        $data[$i]['currency'] = $exchangeRate->cc;
        $data[$i]['date'] = strtotime($exchangeRate->exchangedate);
        $data[$i]['rate'] = $exchangeRate->rate;

        $i++;
      }

    }
    catch (\Exception $e) {
      $this->sendLog($e);
      return;
    }

    return $data;
  }

  /**
   * Checks API status code.
   *
   * @param string $url
   *   The API URL.
   *
   * @return bool
   *   Return API Status code.
   */
  public function checkRequest($url) {
    try {
      $check = $this->client->get($url)->getStatusCode();
      $check == 200 ? $result = TRUE : $result = FALSE;
    }
    catch (\Exception $e) {
      $this->sendLog($e);
      return FALSE;
    }

    return $result;
  }

  /**
   * Sends logs message.
   *
   * @param object $error
   *   The Exceptions.
   */
  public function sendLog($error) {
    $message = $this->t('API isn\'t available - @error', [
      '@error' => $error->getMessage(),
    ]);

    $this->logger->get('exchange_rates')->notice($message);
  }

  /**
   * Saves data got with API to Database.
   *
   * @param int $startOfRange
   *   Start of range in query.
   * @param int $endOfRange
   *   End of range in query.
   */
  public function saveExchangeRates($startOfRange = NULL, $endOfRange = NULL) {
    if (!$startOfRange) {
      $startOfRange = date('Ymd');

    }

    if (!$endOfRange) {
      $endOfRange = date('Ymd');

    }

    $url = $this->buildUrl($this->getConfig('url'), $startOfRange, $endOfRange);
    $data = $this->getExchangeRates($url);
    $fields = ['currency', 'date', 'rate'];

    $query = $this->database->insert('exchange_rates')->fields($fields);
    foreach ($data as $record) {
      $query->values($record);
    }

    $query->execute();

  }

  /**
   * Gets data saved in Database.
   *
   * @return array
   *   The data from Database.
   */
  public function getSavedExchangeRates() {
    $fields = ['currency', 'date', 'rate'];
    $startOfRange = strtotime($this->getConfig('date'));
    $endOfRange = time();

    $query = $this->database->select('exchange_rates', 'e')->fields('e', $fields);
    $query->condition('date', [$startOfRange, $endOfRange], 'BETWEEN');

    // Gets enabled currency.
    $mustShow = $this->getConfig('currency');
    if ($mustShow) {
      foreach ($mustShow as $currency => $shows) {

        if ($shows) {
          $isShow[] = $currency;

        }

      }

    }

    $query->condition('currency', $isShow, 'IN');
    $query->orderBy('date', 'DESK');

    return $query->execute()->fetchAll();

  }

  /**
   * Returns the minimum date in the database for which has exchange rates in timestamp format.
   *
   * @return int
   *   The minimum date in the database.
   */
  public function getStartRangeDate () {
    $select = $this->database->select('exchange_rates', 'e');
    $select->addExpression('MIN(date)');
    $date = $select->execute()->fetchField();

    return $date;
  }

  /**
   * Returns the maximum date in the database for which has exchange rates in timestamp format.
   *
   * @return int
   *   The maximum date in the database.
   */
  public function getEndRangeDate () {
    $select = $this->database->select('exchange_rates', 'e');
    $select->addExpression('MAX(date)');
    $date = $select->execute()->fetchField();

    return $date;
  }

  /**
   * Runs Save Data With 'Exchange rates API URL' Form.
   *
   * @param int $startOfRange
   *   Start of range save data.
   */
  public function runSaveDataWithForm($startOfRange) {
    $savedExchangeRatesOnDate = $this->getStartRangeDate();
    if (!$savedExchangeRatesOnDate) {
      $endOfRange = date('Ymd');

    }
    else {
      $endOfRange = date('Ymd', $savedExchangeRatesOnDate);

    }

    if ($startOfRange <= $endOfRange) {

      if ($startOfRange < $endOfRange) {
        $endOfRange = $endOfRange - 1;

      }

      $this->saveExchangeRates($startOfRange, $endOfRange);
    }

  }

  /**
   * Runs auto-update ExchangeRate.
   *
   */
  public function autoUpdateExchangeRate() {
    $hasDataOn = $this->getEndRangeDate();
    $currentDate = date('Ymd');

    if ($hasDataOn < strtotime($currentDate)) {
      $startOfRange = date('Ymd', $hasDataOn) + 1;
      $this->saveExchangeRates($startOfRange, $currentDate);

    }

  }

}
