<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Normalizes a D7 external-URL link value into a valid D10 link uri.
 *
 * D7's link field accepted anything; six MMI news items carry values that
 * are not absolute URLs:
 * - node/N: an internal D7 link pasted into the box -> the offset node.
 * - sites/<dir>/files/...: a D7 file path -> the migrated file's URL via
 *   the legacy path repair (copying from the D7 tree on demand).
 * - free text (one row: a pasted headline) -> NULL; the following
 *   skip_on_empty drops the field item and the story renders as a normal
 *   local page, which is what D7's broken relative link amounted to.
 * Absolute URLs pass through untouched.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_external_url"
 * )
 */
class MmiExternalUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $url = trim((string) $value);
    if ($url === '') {
      return NULL;
    }
    if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $url)) {
      return $url;
    }
    if (preg_match('~^node/(\d+)$~', $url, $m)) {
      return 'internal:/node/' . ((int) $m[1] + MmiNidOffset::OFFSET);
    }
    if (preg_match('~^/?sites/(?:mmi7|mmi\.oregonstate\.edu|mmi|default)/files/~', $url)) {
      $rewritten = MmiLegacyFilePaths::rewriteUrl('/' . ltrim($url, '/'));
      return $rewritten ? 'internal:' . $rewritten : NULL;
    }
    return NULL;
  }

}
