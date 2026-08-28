<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\osu_migrations_cas\Plugin\migrate\process\CasLegacyFilePaths;

/**
 * Repairs hardcoded D7 file URLs in MMI rich text.
 *
 * Same resolution pipeline as cas_legacy_file_paths, retargeted at the MMI
 * D7 source: editors there hardcoded /sites/mmi7/files/, /sites/mmi/files/
 * and (rarely) /sites/mmi.oregonstate.edu/files/ or a sites/default URL on
 * an mmi host (214+150+2+1 occurrences across 163 text rows, surveyed
 * 2026-08-28). On disk mmi and mmi.oregonstate.edu are symlinks to mmi7, so
 * one source tree covers all of them.
 *
 * Unlike agsci, MMI files keep their D7-relative paths verbatim (mmi_files
 * does no year-directory relocation), so relocatedPath() is the identity.
 * The rewritten prefix is the same shared public filesystem the whole
 * distribution serves from, so NEW_PREFIX is inherited unchanged.
 *
 * URLs pointing at other OSU sites' files (marinestudies, hr, hmsc, ...)
 * fall outside SITE_DIRS and are left untouched, as are the handful of
 * agscid7 references to agsci files (those hosts fail the HOST_NEEDLE check
 * only for sites/default; /sites/agscid7|agsci/ simply is not in this
 * class's alternation).
 */
#[MigrateProcess(
  id: 'mmi_legacy_file_paths'
)]
class MmiLegacyFilePaths extends CasLegacyFilePaths {

  /**
   * {@inheritdoc}
   *
   * mmi7 before mmi so the longer directory wins; the dotted directory
   * before mmi so the alternation cannot stop at its prefix.
   */
  protected const SITE_DIRS = 'mmi7|mmi\.oregonstate\.edu|mmi|default';

  /**
   * {@inheritdoc}
   */
  protected const HOST_NEEDLE = 'mmi';

  /**
   * {@inheritdoc}
   */
  protected const D7_FILES_SETTING = 'mmi_migrate_d7_files_path';

  /**
   * {@inheritdoc}
   */
  protected const D7_FILES_DEFAULT = '/var/www/d7/sites/mmi7/files';

  /**
   * {@inheritdoc}
   */
  protected const LOGGER_CHANNEL = 'osu_migrations_mmi';

  /**
   * {@inheritdoc}
   *
   * mmi_files preserves D7 uris verbatim; nothing is relocated.
   */
  protected static function relocatedPath(string $rel): string {
    return $rel;
  }

}
