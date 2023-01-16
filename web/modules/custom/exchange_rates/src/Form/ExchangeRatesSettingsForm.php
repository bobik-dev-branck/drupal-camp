<?php

namespace Drupal\exchange_rates\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\exchange_rates\ExchangeRatesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Exchange rates settings for this site.
 */
class ExchangeRatesSettingsForm extends ConfigFormBase {

  /**
   * The Constructor for Exchange Rates Settings Form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\exchange_rates\ExchangeRatesService $exchange_rates
   *   The Exchange Rates Service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExchangeRatesService $exchange_rates) {
    parent::__construct($config_factory);
    $this->exchangeRates = $exchange_rates;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('exchange_rates.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exchange_rates_exchange_rates';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['exchange_rates.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('exchange_rates.settings');

    $form['show_block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do you want to show block?'),
      '#default_value' => $config->get('show_block') ?? FALSE,
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exchange rates API'),
      '#description' => $this->t('WARNING! Use only JSON API'),
      '#default_value' => $config->get('url') ?? '',
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    // If API set and return data will show this fieldset.
    $url = $config->get('url') ?? '';
    if (!empty($url)) {
      $checkUrl = $this->exchangeRates->checkRequest($url);

      if ($checkUrl) {
        $form['currency'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Choose currency will show'),
          '#tree' => TRUE,
        ];

        $data = $this->exchangeRates->getExchangeRates($config->get('url'));
        $defaultValue = $config->get('currency');
        foreach (array_keys($data) as $currency) {
          $form['currency'][$currency] = [
            '#type' => 'checkbox',
            '#title' => $currency,
            '#default_value' => $defaultValue[$currency] ?? FALSE,
          ];
        }

      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('url')) {
      $checkLink = $this->exchangeRates->checkRequest($form_state->getValue('url'));
      if (!$checkLink) {
        $form_state->setErrorByName('url', $this->t('Wrong link or API don\'t work'));
      }

    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('exchange_rates.settings')
      ->set('show_block', $form_state->getValue('show_block'))
      ->set('url', $form_state->getValue('url'))
      ->set('currency', $form_state->getValue('currency'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
