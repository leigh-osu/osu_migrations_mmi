<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Skips an alias that already belongs to a different live path.
 *
 * D10 aliases are global — path_alias has no domain column — so an MMI alias
 * that duplicates an agsci alias would shadow the agsci page (newest row
 * wins the lookup). Six MMI aliases collide with the live site (survey
 * 2026-08-28: /alumni, /employee-resources, ...); each skip is logged as the
 * hand-review breadcrumb. An existing row pointing at the SAME path (this
 * migration's own earlier import) passes through, so re-runs are safe.
 *
 * Expects [alias-with-leading-slash, resolved-path] as its source.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_skip_existing_alias"
 * )
 */
class MmiSkipExistingAlias extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$alias, $path] = $value;
    $existing = \Drupal::database()->select('path_alias', 'pa')
      ->fields('pa', ['path'])
      ->condition('pa.alias', $alias)
      ->execute()
      ->fetchCol();
    foreach ($existing as $existing_path) {
      if ($existing_path !== $path) {
        throw new MigrateSkipRowException(sprintf(
          'Alias "%s" already belongs to live path "%s"; MMI alias for "%s" not migrated.',
          $alias, $existing_path, $path
        ));
      }
    }
    return $alias;
  }

}
