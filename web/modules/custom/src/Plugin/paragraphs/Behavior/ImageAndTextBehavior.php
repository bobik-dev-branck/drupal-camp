<?php

namespace Drupal\newsweek_paragraphs_behavior\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Custom behavior for paragraph Image and text.
 *
 * @ParagraphsBehavior (
 *   id = "newsweek_paragraphs_behavior_image_and_text",
 *   label = @Translation("Image and text settings"),
 *   description = @Translation("Settings for paragraphs type"),
 *   weight = 0,
 * )
 *
 */

class ImageAndTextBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'image_and_text';
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $image_position = $paragraph->getBehaviorSetting($this->getPluginId(),
      'image_position', 'left');

    // Generating class name for bundle 'image_and_text'.
    $bem_block = 'paragraph-' . $paragraph->bundle();

    $build['#attributes']['class'][] =
      Html::getClass($bem_block . '--image_position-' . $image_position);
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image position'),
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(),
        'image_position', 'left'),
    ];

    return $form;
  }

}
