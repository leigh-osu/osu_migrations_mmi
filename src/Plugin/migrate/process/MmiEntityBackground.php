<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs_to_layout_builder\Plugin\migrate\process\EntityBackground;

/**
 * Entity-background field collections from the MMI D7 source.
 *
 * Reads the eb_* collection tables over migrate_mmi and resolves the
 * background image through the MMI media map (MMI has no private-scheme
 * media, so there is no second lookup).
 */
#[MigrateProcess(
  id: 'mmi_entity_background',
)]
class MmiEntityBackground extends EntityBackground {

  /**
   * {@inheritdoc}
   */
  protected const MIGRATE_DB_KEY = 'migrate_mmi';

  /**
   * {@inheritdoc}
   */
  protected function getMediaMigrations(): array {
    return ['mmi_media_images'];
  }

  /**
   * {@inheritdoc}
   *
   * The stock plugin's fallthrough (an empty collection — MMI has 4 with no
   * eb_selection row) returns the raw source item, which the layout stage
   * would then misread as a "media_id,type" value. Resolve to nothing
   * instead.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $result = parent::transform($value, $migrate_executable, $row, $destination_property);
    return is_array($result) ? NULL : $result;
  }

}
