<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rewrites a D7 menu link_path into the MMI id namespace.
 *
 * - node/N for a migrated node -> node/(N + 400000).
 * - node/N for a D7 GROUP node (lab home links) -> group/<migrated id>.
 * - node/N for the parent_unit -> skip (not migrated).
 * - user/U -> node/<profile nid> via mmi_profiles, or skip.
 * - everything else (aliases, external urls, <front>) passes through and
 *   resolves at render time like the agsci menu links.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_menu_link_path"
 * )
 */
class MmiMenuLinkPath extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   */
  protected MigrateLookupInterface $migrateLookup;

  /**
   * D7 nid => 'group'|'parent_unit' for the non-node-migrated types.
   *
   * @var array|null
   */
  protected ?array $nonNodeTypes = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('migrate.lookup'));
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $path = (string) $value;

    if (preg_match('~^node/(\d+)$~', $path, $m)) {
      $nid = (int) $m[1];
      if ($this->nonNodeTypes === NULL) {
        $this->nonNodeTypes = Database::getConnection('default', 'migrate_mmi')
          ->query("SELECT nid, type FROM {node} WHERE type IN ('group', 'parent_unit')")
          ->fetchAllKeyed();
      }
      $special = $this->nonNodeTypes[$nid] ?? NULL;
      if ($special === 'group') {
        $ids = $this->migrateLookup->lookup('mmi_node_og_group', [$nid]);
        $first = !empty($ids) ? reset($ids) : NULL;
        $group_id = is_array($first) ? reset($first) : NULL;
        if ($group_id) {
          return 'group/' . $group_id;
        }
        throw new MigrateSkipRowException(sprintf('Menu link to unmigrated group node %d.', $nid));
      }
      if ($special === 'parent_unit') {
        throw new MigrateSkipRowException(sprintf('Menu link to parent_unit node %d (not migrated).', $nid));
      }
      return 'node/' . ($nid + MmiNidOffset::OFFSET);
    }

    if (preg_match('~^user/(\d+)$~', $path, $m)) {
      $ids = $this->migrateLookup->lookup('mmi_profiles', [(int) $m[1]]);
      $first = !empty($ids) ? reset($ids) : NULL;
      $profile_nid = is_array($first) ? reset($first) : NULL;
      if ($profile_nid) {
        return 'node/' . $profile_nid;
      }
      throw new MigrateSkipRowException(sprintf('Menu link to user %d with no profile node.', $m[1]));
    }

    return $value;
  }

}
