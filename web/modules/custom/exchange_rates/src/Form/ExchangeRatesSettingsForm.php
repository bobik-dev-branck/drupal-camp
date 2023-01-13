<?php

namespace Drupal\exchange_rates\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Exchange rates settings for this site.
 */
class ExchangeRatesSettingsForm extends ConfigFormBase {

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
      '#default_value' => $config->get('url') ?? ' ',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('exchange_rates.settings')
      ->set('show_block', $form_state->getValue('show_block'))
      ->set('url', $form_state->getValue('url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
