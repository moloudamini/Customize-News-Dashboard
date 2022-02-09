<?php

namespace Drupal\uw_dashboard\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * A UW two column layout.
 */
class Uw2ColumnLayout extends UwColumnLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Get the config for this layout.
    $configuration = $this->getConfiguration();

    // The options for the column widths.
    $options = [
      'even-split' => $this->t('Even split (50%, 50%)'),
      'larger-left' => $this->t('Larger left (67%, 33%)'),
      'larger-right' => $this->t('Larger right (33%, 67%)'),
    ];

    // The form element for the column widths.
    $form['layout_settings']['column_class'] = [
      '#type' => 'select',
      '#title' => $this->t('Column widths'),
      '#default_value' => !empty($configuration['column_class']) ? $configuration['column_class'] : 'even-split',
      '#options' => $options,
    ];

    return $form;
  }

}
