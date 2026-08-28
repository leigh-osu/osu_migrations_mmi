<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves a D7 MMI group nid to its migrated group's menu name.
 *
 * Configuration:
 * - d7_group_nid: use this fixed D7 group nid instead of the source value
 *   (the main-menu migration pins nid 1, the Main group).
 *
 * The basic_group type auto-creates a group menu on group creation
 * (group_content_type_0903efc098d94, auto_create_group_menu), so the menu
 * exists as soon as mmi_groups has run.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_group_menu_name"
 * )
 */
class MmiGroupMenuName extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   */
  protected MigrateLookupInterface $migrateLookup;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateLookupInterface $migrate_lookup, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrateLookup = $migrate_lookup;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $d7_nid = (int) ($this->configuration['d7_group_nid'] ?? $value);
    return $this->menuNameForD7Group($d7_nid);
  }

  /**
   * The D10 group menu name for a D7 group nid.
   *
   * @param int $d7_nid
   *   The D7 group node id.
   *
   * @return string
   *   The menu machine name (group-menu-<id>).
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   When the group or its menu cannot be resolved.
   */
  protected function menuNameForD7Group(int $d7_nid): string {
    $ids = $this->migrateLookup->lookup('mmi_node_og_group', [$d7_nid]);
    $first = !empty($ids) ? reset($ids) : NULL;
    $group_id = is_array($first) ? reset($first) : NULL;
    $group = $group_id ? $this->entityTypeManager->getStorage('group')->load($group_id) : NULL;
    if (!$group) {
      throw new MigrateSkipRowException(sprintf('No migrated group for D7 group %d.', $d7_nid));
    }
    $group_menus = group_content_menu_get_menus_per_group($group);
    $group_menu = reset($group_menus);
    if (empty($group_menu)) {
      throw new MigrateSkipRowException(sprintf('Group %s has no group menu.', $group->label()));
    }
    $menu_id = $group_menu->get('entity_id')->first()->getValue()['target_id'];
    return GroupContentMenuInterface::MENU_PREFIX . $menu_id;
  }

}
