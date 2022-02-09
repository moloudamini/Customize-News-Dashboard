<?php

namespace Drupal\uw_dashboard\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * A UW Inverted L Right layout.
 */
class UwInvertedLRightLayout extends UwColumnLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Get the config for this layout.
    $configuration = $this->getConfiguration();

    // The options for the column widths.
    $options = [
      'even-split' => $this->t('Even split (50%, 50%)'),
      'larger-left' => $this->t('Larger left (33%, 67%)'),
      'larger-right' => $this->t('Larger right (67%, 33%)'),
    ];

    // The form element for the column widths.
    $form['layout_settings']['column_class'] = [
      '#type' => 'select',
      '#title' => $this->t('Column widths for top row'),
      '#default_value' => !empty($configuration['column_class']) ? $configuration['column_class'] : 'even-split',
      '#options' => $options,
    ];

    return $form;
  }

}
