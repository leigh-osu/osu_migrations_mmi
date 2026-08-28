<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\osu_migrations_cas\Plugin\migrate\process\CasOgMembershipRole;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group role for MMI D7 OG memberships, read over migrate_mmi.
 *
 * Same collapse as the agsci run: any held OG role (group manager, group
 * author — 42 of MMI's 97 user memberships) becomes the one D10 working
 * role; plain members yield nothing and keep bare membership.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_og_membership_role"
 * )
 */
class MmiOgMembershipRole extends CasOgMembershipRole {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      Database::getConnection('default', 'migrate_mmi'),
    );
  }

}
