<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Maps MMI D7 role ids to D10 roles, reading names from the MMI database.
 *
 * osu_user_role_map (osu_user_accounts) does the same job but hardcodes the
 * 'migrate' connection, so against an MMI row it would resolve rids in the
 * agsci role table. Same name mapping as the agsci run, with two deliberate
 * differences: every role a user holds is mapped (not just the first row the
 * query returns), and 'administrator' maps to nothing -- both MMI holders are
 * ONID-adopted live accounts whose roles never migrate, and a created account
 * must not arrive with site-admin rights.
 *
 * @code
 * roles:
 *   plugin: mmi_user_role_map
 *   source: roles
 * @endcode
 */
#[MigrateProcess(
  id: 'mmi_user_role_map'
)]
class MmiUserRoleMap extends ProcessPluginBase {

  private const NAME_MAP = [
    'earl' => 'earl',
    'architect' => 'architect',
    'author' => 'content_authors',
    'manager' => 'manage_site_configuration',
    'group user' => 'group_content_author',
  ];

  /**
   * {@inheritDoc}
   *
   * The pipeline hands over ONE rid per call: the get plugin prepended for
   * 'source: roles' flags the array as multiple, so migrate iterates the
   * elements over this plugin. Return a scalar role id (or NULL to drop),
   * never an array -- a per-element array nests and the destination silently
   * discards every role. Arrays are still accepted for direct calls.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $rids = array_filter(is_array($value) ? $value : [$value]);
    if (!$rids) {
      return NULL;
    }
    $names = Database::getConnection('default', 'migrate_mmi')
      ->query('SELECT [name] FROM {role} WHERE [rid] IN ( :rids[] )', [':rids[]' => $rids])
      ->fetchCol();
    $roles = [];
    foreach ($names as $name) {
      if (isset(self::NAME_MAP[$name])) {
        $roles[] = self::NAME_MAP[$name];
      }
    }
    $roles = array_values(array_unique($roles));
    if (!$roles) {
      return NULL;
    }
    return is_array($value) ? $roles : $roles[0];
  }

}
