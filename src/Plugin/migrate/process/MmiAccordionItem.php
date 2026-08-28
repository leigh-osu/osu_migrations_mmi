<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\paragraphs_to_layout_builder\Plugin\migrate\process\AccordionItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Accordion paragraph items from the MMI D7 source.
 *
 * The parent reads the accordion group collections over its migrate
 * connection and resolves media tokens through the agsci media embed
 * service; this variant reads migrate_mmi and resolves through the MMI
 * media maps. Text repair swaps the agsci defaults for the MMI legacy
 * file-path rewrite (no larch classes exist in MMI content — doug_fir
 * shipped none into body text).
 */
#[MigrateProcess(
  id: 'mmi_accordion_item',
  handle_multiples: TRUE
)]
class MmiAccordionItem extends AccordionItem {

  /**
   * {@inheritdoc}
   */
  protected const MIGRATE_DB_KEY = 'migrate_mmi';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('uuid'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('migrate.lookup'),
      $container->get('osu_migrations_mmi.media_embed')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function repairRichText(string $text): string {
    return MmiLegacyFilePaths::rewriteText($text);
  }

}
