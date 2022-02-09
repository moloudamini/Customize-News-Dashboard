<?php

namespace Drupal\uw_dashboard\Plugin\LayoutBuilderRestriction;

use Drupal\layout_builder_restrictions\Plugin\LayoutBuilderRestriction\EntityViewModeRestriction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for restricting blocks/layouts for UW dashboard.
 *
 * @LayoutBuilderRestriction(
 *   id = "uw_dashboard_restrict_blocks_layouts",
 *   title = @Translation("UW Dashboard restrict blocks/layouts"),
 *   description = @Translation("Restrict blocks/layouts for UW dashboard"),
 * )
 */
class UwDashboardRestrictBlocksLayouts extends EntityViewModeRestriction {

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function alterBlockDefinitions(array $definitions, array $context) {

    // An array of allowed blocks on our dashboard.
    $allowed_blocks = [
      'uw_news_block_uwnews',
      'uw_news_block_uwstories',
      'uw_news_block_global',
      'uw_news_block_events',
      'uw_news_block_important_dates',
    ];

    // Step through each of the definitions and if it is not
    // in our array of allowed blocks, unset that definition.
    foreach ($definitions as $key => $def) {
      if (!(in_array($key, $allowed_blocks))) {
        unset($definitions[$key]);
      }
    }

    // Return the updated definitions.
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function alterSectionDefinitions(array $definitions, array $context) {

    // The allowed layouts for our dashboard.
    $allowed_layouts = [
      'uw_1_column',
      'uw_2_column',
      'uw_3_column',
      'uw_inverted_l_left',
      'uw_inverted_l_right',
    ];

    // Step through each layout and remove any ones that are
    // not in our allowed dashboard array.
    foreach ($definitions as $key => $def) {

      // If the layout is not in allowed layouts array, remove it.
      if (!in_array($key, $allowed_layouts)) {
        unset($definitions[$key]);
      }
    }

    // Return the allowed layouts.
    return $definitions;
  }

}
