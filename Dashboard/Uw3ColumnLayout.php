<?php

namespace Drupal\uw_dashboard\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * A UW three column layout.
 */
class Uw3ColumnLayout extends UwColumnLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Get the config for this layout.
    $configuration = $this->getConfiguration();

    // The options for the column widths.
    $options = [
      'even-split' => $this->t('Even split (33%, 34%, 33%)'),
      'larger-left' => $this->t('Larger left (50%, 25%, 25%)'),
      'larger-middle' => $this->t('Larger middle (25%, 50%, 25%)'),
      'larger-right' => $this->t('Larger right (25%, 25%, 50%)'),
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
