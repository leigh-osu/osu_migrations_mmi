<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Slices a multi-value source array.
 *
 * Used where a D7 multi-value field splits across two D10 fields — e.g. the
 * news thumbnails: delta 0 becomes the story cover image (cardinality 1) and
 * the remainder lands in field_story_media. Configuration:
 * - offset: first index to keep (default 0).
 * - length: number of items to keep (default: to the end).
 *
 * @code
 * _extra_thumbnails:
 *   - plugin: get
 *     source: field_news_thumbnail
 *   - plugin: mmi_array_slice
 *     offset: 1
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_array_slice",
 *   handle_multiples = TRUE
 * )
 */
class MmiArraySlice extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      return [];
    }
    return array_slice(
      array_values($value),
      (int) ($this->configuration['offset'] ?? 0),
      $this->configuration['length'] ?? NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  public function multiple(): bool {
    return TRUE;
  }

}
