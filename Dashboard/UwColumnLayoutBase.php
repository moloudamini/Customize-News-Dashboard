<?php

namespace Drupal\uw_dashboard\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * A column layout base.
 */
class UwColumnLayoutBase extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    // Set the column class in the config.
    $this->configuration['column_class'] = $form_state->getValue(
      ['layout_settings', 'column_class'],
      NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {

    // Build the render array as usual.
    $build = parent::build($regions);

    // Retrieve the config for the layout.
    $configuration = $this->getConfiguration();

    // Set the column class to be used in the layout template.
    $build['#settings']['column_class'] = $configuration['column_class'];

    return $build;
  }

}
