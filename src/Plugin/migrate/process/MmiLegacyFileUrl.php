<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\osu_migrations_cas\Plugin\migrate\process\CasLegacyFileUrl;

/**
 * Repairs hardcoded MMI D7 file URLs stored as whole link-field values.
 *
 * cas_legacy_file_url with the resolution retargeted at the MMI source via
 * MmiLegacyFilePaths. Appended to mmi_* link-field pipelines by
 * osu_migrations_mmi_migration_plugins_alter().
 */
#[MigrateProcess(
  id: 'mmi_legacy_file_url'
)]
class MmiLegacyFileUrl extends CasLegacyFileUrl {

  /**
   * {@inheritdoc}
   */
  protected const PATHS_CLASS = MmiLegacyFilePaths::class;

}
