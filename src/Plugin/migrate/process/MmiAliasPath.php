<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves a D7 url_alias source path to its D10 system path.
 *
 * The MMI alias table holds exactly four source shapes (8,677 rows):
 * - node/N (1,501): the migrated node at N + 400000.
 * - user/U (175): people pages; the D10 page is the user's osu_profile NODE,
 *   resolved through the mmi_profiles map (keyed by D7 uid). A user whose
 *   profile did not migrate logs a message.
 * - file/F (6,976): D7 file-entity pages with no D10 counterpart — media is
 *   embedded inline. Skipped silently by design.
 * - taxonomy/term/T (25): functional_groups/group_types term pages; neither
 *   has a public D10 page. Skipped silently by design.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_alias_path"
 * )
 */
class MmiAliasPath extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   */
  protected MigrateLookupInterface $migrateLookup;

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
   * D7 nids that are not migrated as nodes: group and parent_unit nodes.
   *
   * Their aliases must not become /node/<nid+offset> aliases to nonexistent
   * nodes — group aliases ride the group entities (mmi_groups path/alias).
   *
   * @var int[]|null
   */
  protected ?array $nonNodeNids = NULL;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = (string) $value;

    if (preg_match('~^node/(\d+)$~', $source, $m)) {
      if ($this->nonNodeNids === NULL) {
        $this->nonNodeNids = array_map('intval', \Drupal\Core\Database\Database::getConnection('default', 'migrate_mmi')
          ->query("SELECT nid FROM {node} WHERE type IN ('group', 'parent_unit', 'navigation_grid', 'expedition', 'highlight')")
          ->fetchCol());
      }
      if (in_array((int) $m[1], $this->nonNodeNids, TRUE)) {
        // Group entity aliases are set by mmi_groups; the other types are
        // not migrated (nav grids and the expedition dropped by decision,
        // highlights hand-built as blocks), so an offset alias would point
        // at a node that never exists.
        throw new MigrateSkipRowException();
      }
      return '/node/' . ((int) $m[1] + MmiNidOffset::OFFSET);
    }

    if (preg_match('~^user/(\d+)$~', $source, $m)) {
      $ids = $this->migrateLookup->lookup('mmi_profiles', [(int) $m[1]]);
      if (!empty($ids)) {
        $first = reset($ids);
        return '/node/' . reset($first);
      }
      throw new MigrateSkipRowException(sprintf('No profile node for D7 user %d; alias not migrated.', $m[1]));
    }

    // file/* and taxonomy/term/* — no D10 page; skip without a message.
    throw new MigrateSkipRowException();
  }

}
