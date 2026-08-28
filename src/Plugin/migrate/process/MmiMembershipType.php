<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves a D7 uid to the person's membership_types term.
 *
 * MMI's functional_group term (Principal Investigator, Ph.D. Student, ...)
 * lives on the osu_employee profile2 (79 of 104 people carry one) and lands
 * on the profile-in-group relationship as field_membership_type, resolved
 * through the mmi_membership_terms map (adopted or created live tids).
 * People without a functional group yield nothing — the field stays empty,
 * joining the untyped associations the membership-type backfill tracks.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_membership_type"
 * )
 */
class MmiMembershipType extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $tid = Database::getConnection('default', 'migrate_mmi')
      ->queryRange("SELECT fg.functional_group_tid FROM {field_data_functional_group} fg
        JOIN {profile} p ON p.pid = fg.entity_id AND fg.entity_type = 'profile2'
        WHERE p.uid = :uid AND fg.deleted = 0", 0, 1, [':uid' => (int) $value])
      ->fetchField();
    if (!$tid) {
      return NULL;
    }
    $ids = $this->migrateLookup->lookup('mmi_membership_terms', [(int) $tid]);
    $first = !empty($ids) ? reset($ids) : NULL;
    return is_array($first) ? reset($first) : NULL;
  }

}
