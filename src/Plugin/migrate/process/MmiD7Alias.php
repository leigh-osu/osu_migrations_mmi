<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Looks up a D7 node's url_alias over the migrate_mmi connection.
 *
 * Used by mmi_groups to carry the D7 group aliases (group/main, wtg, ...)
 * onto the group entities — mmi_url_alias deliberately skips group-node
 * sources because groups are not nodes on D10.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_d7_alias"
 * )
 */
class MmiD7Alias extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $alias = Database::getConnection('default', 'migrate_mmi')
      ->queryRange("SELECT alias FROM {url_alias} WHERE source = :src ORDER BY pid DESC", 0, 1, [':src' => 'node/' . (int) $value])
      ->fetchField();
    return $alias ? '/' . ltrim($alias, '/') : NULL;
  }

}
