<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\paragraphs_to_layout_builder\Plugin\migrate\process\MenuItem;

/**
 * Menu-bar paragraph items from the MMI D7 source.
 *
 * The parent reads link/icon data over its migrate connection and repairs
 * hardcoded file URLs with the agsci resolution; this variant reads the
 * migrate_mmi source and repairs /sites/mmi7|mmi/files/ URLs instead.
 */
#[MigrateProcess(
  id: 'mmi_menu_item',
  handle_multiples: TRUE
)]
class MmiMenuItem extends MenuItem {

  /**
   * {@inheritdoc}
   */
  protected const MIGRATE_DB_KEY = 'migrate_mmi';

  /**
   * {@inheritdoc}
   */
  protected function rewriteLegacyFileUrl(string $url): string {
    return MmiLegacyFilePaths::rewriteUrl($url);
  }

}
