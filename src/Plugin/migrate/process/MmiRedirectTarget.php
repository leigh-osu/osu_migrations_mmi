<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rewrites a migrated D7 redirect target into the MMI id namespace.
 *
 * Runs after core's d7_path_redirect built the uri. Target shapes
 * (1,286 MMI redirects):
 * - internal:/node/N (313) -> the offset node.
 * - internal:/user/U (95) -> the user's osu_profile node via mmi_profiles.
 * - internal:/file/F (875) -> D7 file-entity pages have no D10 page, so the
 *   redirect goes straight to the migrated file's real URL via the
 *   mmi_files map.
 * - everything else (external urls, plain paths) passes through.
 * Unresolvable node/user/file targets skip the row with a message.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_redirect_target"
 * )
 */
class MmiRedirectTarget extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   */
  protected MigrateLookupInterface $migrateLookup;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file URL generator.
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateLookupInterface $migrate_lookup, EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrateLookup = $migrate_lookup;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate.lookup'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $uri = (string) $value;

    if (preg_match('~^internal:/node/(\d+)$~', $uri, $m)) {
      $nid = (int) $m[1];
      // Group-node targets (e.g. the D7 "wtg" short-url redirects) point at
      // the migrated GROUP, not a node; parent_unit is not migrated at all.
      $special = $this->nonNodeType($nid);
      if ($special === 'group') {
        $ids = $this->migrateLookup->lookup('mmi_node_og_group', [$nid]);
        $first = !empty($ids) ? reset($ids) : NULL;
        $group_id = is_array($first) ? reset($first) : NULL;
        if ($group_id) {
          return 'internal:/group/' . $group_id;
        }
        throw new MigrateSkipRowException(sprintf('Redirect target is unmigrated group node %d.', $nid));
      }
      if ($special === 'parent_unit') {
        throw new MigrateSkipRowException(sprintf('Redirect target is parent_unit node %d (not migrated).', $nid));
      }
      return 'internal:/node/' . ($nid + MmiNidOffset::OFFSET);
    }

    if (preg_match('~^internal:/user/(\d+)$~', $uri, $m)) {
      $ids = $this->migrateLookup->lookup('mmi_profiles', [(int) $m[1]]);
      if (!empty($ids)) {
        $first = reset($ids);
        return 'internal:/node/' . reset($first);
      }
      throw new MigrateSkipRowException(sprintf('Redirect target user/%d has no profile node.', $m[1]));
    }

    if (preg_match('~^internal:/file/(\d+)$~', $uri, $m)) {
      $ids = $this->migrateLookup->lookup('mmi_files', [(int) $m[1]]);
      $first = !empty($ids) ? reset($ids) : NULL;
      $fid = is_array($first) ? reset($first) : NULL;
      $file = $fid ? $this->entityTypeManager->getStorage('file')->load($fid) : NULL;
      if ($file) {
        return 'internal:' . $this->fileUrlGenerator->generateString($file->getFileUri());
      }
      throw new MigrateSkipRowException(sprintf('Redirect target file/%d did not migrate.', $m[1]));
    }

    return $value;
  }

  /**
   * Whether a D7 nid is a group or parent_unit node (cached).
   *
   * @param int $nid
   *   The D7 nid.
   *
   * @return string|null
   *   'group', 'parent_unit', or NULL for an ordinary node.
   */
  protected function nonNodeType(int $nid): ?string {
    static $types = NULL;
    if ($types === NULL) {
      $types = \Drupal\Core\Database\Database::getConnection('default', 'migrate_mmi')
        ->query("SELECT nid, type FROM {node} WHERE type IN ('group', 'parent_unit')")
        ->fetchAllKeyed();
    }
    return $types[$nid] ?? NULL;
  }

}
