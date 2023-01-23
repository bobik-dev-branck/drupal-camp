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

    // Auto update Exchange Rate.
    $this->exchangeRates->autoUpdateExchangeRate();

    // Preparing data to render in the Exchange Rates block.
    $exchangeRates = $this->exchangeRates->getSavedExchangeRates();
    foreach ( $exchangeRates as $exchangeRate )  {
      $toRender[$exchangeRate->currency][date('d.m.Y', $exchangeRate->date)] = $exchangeRate->rate;

    }

    if (!empty($toRender)) {
      $i = 0;

      $renderable['#theme'][] = 'block_exchange_rates';
      $renderable['#attached']['library'][] = 'exchange_rates/exchange_rates_chart';


      foreach ($toRender as $currency => $currencyData) {
        if ($renderable['#attached']['drupalSettings']['exchange_rates']['currency_data']['date']) {
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
