<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\osu_migrations_cas\Plugin\migrate\source\CasMediaAltBackfill;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MMI variant of the media alt/title backfill.
 *
 * Reads the D7 per-delta field alt/title from the MMI source database and
 * maps fids through the MMI image-media map, so only MMI media are touched.
 * The agsci sweep already ran during its rebuild; the shared parent only
 * yields media that still need a change, so both stay convergent.
 *
 * @MigrateSource(
 *   id = "mmi_media_alt_backfill",
 *   source_module = "media"
 * )
 */
class MmiMediaAltBackfill extends CasMediaAltBackfill {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      Database::getConnection('default', 'migrate_mmi'),
      Database::getConnection('default', 'default'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function mediaImageMapTable(): string {
    return 'migrate_map_mmi_media_images';
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'mmi_media_alt_backfill';
  }

}
