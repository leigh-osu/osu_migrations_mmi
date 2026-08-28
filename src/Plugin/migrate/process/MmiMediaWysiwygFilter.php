<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\osu_migrations\Plugin\migrate\process\OsuMediaWysiwygFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * D7 media token to drupal-media transform for MMI text values.
 *
 * Identical to osu_media_wysiwyg_filter except the fid lookup goes through
 * MmiMediaEmbed, i.e. the mmi_* media maps rather than the agsci ones.
 */
#[MigrateProcess(
  id: 'mmi_media_wysiwyg_filter'
)]
class MmiMediaWysiwygFilter extends OsuMediaWysiwygFilter {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('osu_migrations_mmi.media_embed'),
    );
  }

}
