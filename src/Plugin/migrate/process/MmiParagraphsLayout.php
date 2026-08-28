<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\osu_migrations_cas\Plugin\migrate\process\CasParagraphsLayout;

/**
 * Paragraphs -> Layout Builder sections for the MMI second input.
 *
 * Same machinery as the agsci run, reading the migrate_mmi source and the
 * mmi_* paragraph migration maps, plus the MMI-only bundles:
 * - 2_col_compound / 3_col_compound: text columns migrate like any other
 *   column paragraph; the embedded view references are not migrated (same
 *   treatment the agsci run gave 2_column_views).
 * - view: a placeholder section labeled with the D7 view name, so each
 *   embedded lab listing keeps its place in the layout and reads
 *   "Configure D7 view: mmi_news|block_1" in the LB UI until the D10
 *   group views exist and a view block is placed by hand.
 * - navigation_grid_paragraph / expedition are NOT handled here on purpose:
 *   they are on the hand-build list, and the missing-migration message each
 *   one logs is the breadcrumb for that work.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_paragraphs_layout"
 * )
 */
class MmiParagraphsLayout extends CasParagraphsLayout {

  /**
   * {@inheritdoc}
   */
  protected const MIGRATE_DB_KEY = 'migrate_mmi';

  /**
   * {@inheritdoc}
   */
  protected function menuMigrationIds(): array {
    return array_merge(parent::menuMigrationIds(), ['mmi_paragraph_menu']);
  }

  /**
   * {@inheritdoc}
   */
  protected function oneColMigrationIds(): array {
    return array_merge(parent::oneColMigrationIds(), ['mmi_paragraph_1_col']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSectionType(string $paragraphType): string {
    return match ($paragraphType) {
      '2_col_compound' => 'bootstrap_layout_builder:blb_col_2_two_equal_columns',
      '3_col_compound' => 'bootstrap_layout_builder:blb_col_3_three_equal_columns',
      default => parent::getSectionType($paragraphType),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function getRegionMigrationIds(string $type, array $map): array {
    if ($type === '2_col_compound') {
      return [
        $map['2_col_compound_left'] => 'blb_region_col_1',
        $map['2_col_compound_right'] => 'blb_region_col_2',
      ];
    }
    if ($type === '3_col_compound') {
      return [
        $map['3_col_compound_left'] => 'blb_region_col_1',
        $map['3_col_compound_mid'] => 'blb_region_col_2',
        $map['3_col_compound_right'] => 'blb_region_col_3',
      ];
    }
    return parent::getRegionMigrationIds($type, $map);
  }

  /**
   * {@inheritdoc}
   *
   * MMI's D7 site has no field_paragraph_label (an agsci-only field); the
   * placeholder labels for view paragraphs are set in createBundleSection().
   */
  protected function sectionLabel($itemId): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * The view bundle held one embedded D7 view (field_add_a_view, no stored
   * arguments — the lab context came from the page). The views themselves
   * are not migrated; the placeholder keeps the slot and names the view for
   * the post-section-9 backfill.
   */
  protected function createBundleSection(string $type, $itemId) {
    if ($type !== 'view') {
      return NULL;
    }
    $vname = $this->migrateDb->select('field_data_field_add_a_view', 'v')
      ->fields('v', ['field_add_a_view_vname'])
      ->condition('v.entity_type', 'paragraphs_item')
      ->condition('v.entity_id', $itemId)
      ->execute()
      ->fetchField();
    $settings = [];
    if (is_string($vname) && trim($vname) !== '') {
      $settings['label'] = 'D7 view: ' . trim($vname);
    }
    return $this->createSection('bootstrap_layout_builder:blb_col_1', [], $settings);
  }

}
