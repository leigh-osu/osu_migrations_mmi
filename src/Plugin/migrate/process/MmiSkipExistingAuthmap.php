<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Skips the row when the uid already has a CAS authmap entry.
 *
 * The ONID-adopted accounts are live agsci users whose authmap rows predate
 * this migration. Rewriting them would be harmless -- but it would also put
 * them in the mmi_user_authmap map, and a later rollback would DELETE their
 * login mappings. Skipping records the row as ignored, so rollback can never
 * touch a mapping this migration did not create.
 *
 * @code
 * uid:
 *   - plugin: migration_lookup
 *     ...
 *   - plugin: mmi_skip_existing_authmap
 * @endcode
 */
#[MigrateProcess(
  id: 'mmi_skip_existing_authmap'
)]
class MmiSkipExistingAuthmap extends ProcessPluginBase {

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value && \Drupal::database()
      ->query("SELECT 1 FROM {authmap} WHERE uid = :uid AND provider = 'cas'", [':uid' => $value])
      ->fetchField()) {
      throw new MigrateSkipRowException('', TRUE);
    }
    return $value;
  }

}
