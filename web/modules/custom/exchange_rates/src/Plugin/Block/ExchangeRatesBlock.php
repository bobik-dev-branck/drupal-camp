<?php

namespace Drupal\exchange_rates\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\exchange_rates\ExchangeRatesService;
use Psr\Container\ContainerInterface;

/**
 * Provides data about currency exchange rates.
 *
 * @Block(
 *   id = "exchange_rates",
 *   admin_label = @Translation("Exchange_rates"),
 * )
 */
class ExchangeRatesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor Exchange Rates Block.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\exchange_rates\ExchangeRatesService $exchange_rates
   *   The Exchange Rates Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExchangeRatesService $exchange_rates) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->exchangeRates = $exchange_rates;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('exchange_rates.service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $showBlock = $this->exchangeRates->getConfig('show_block');
    if (!$showBlock) {
      return;
    }

    if ($showBlock) {

      $url = $this->exchangeRates->getConfig('url');
      $url = $this->exchangeRates->buildUrl($url);

      if ($url) {
        $data = $this->exchangeRates->getExchangeRates($url);

        if (!empty($data)) {
          $mustShow = $this->exchangeRates->getConfig('currency');

          if ($mustShow) {
            foreach ($mustShow as $currency => $shows) {

              if ($shows) {
                $isShow[$currency] = $shows;

              }

            }

          }

          foreach ($data as $currency => $rate) {
            if (isset($isShow[$currency]) && $isShow[$currency]) {
              $validated[$currency] = $rate;

            }

          }

          if (!empty($validated)) {
            $i = 0;

            $renderable['#theme'][] = 'block_exchange_rates';
            $renderable['#attached']['library'][] = 'exchange_rates/exchange_rates_chart';

            foreach ($validated as $currency => $currencyData) {
              if (!isset($renderable['#attached']['drupalSettings']['exchange_rates']['currency_data']['date'])) {
                $renderable['#attached']['drupalSettings']['currency_data']['date'] = array_keys($currencyData);

              }

              $renderable['#attached']['drupalSettings']['exchange_rates'][$i]['label'] = $currency;
              $renderable['#attached']['drupalSettings']['exchange_rates'][$i]['borderWidth'] = 1;
              $renderable['#attached']['drupalSettings']['exchange_rates'][$i]['data'] = array_values($currencyData);

              $i++;
            }

            return $renderable;
          }

        }

      }

    }

  }

}
