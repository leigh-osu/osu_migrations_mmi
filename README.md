# OSU Migrations - MMI

Second-input migration of the Marine Mammal Institute Drupal 7 site into the
live OSU College of Agricultural Sciences Drupal 10 install.

The agsci D7→D10 migration is finished and its database is durable state, so
MMI arrives as a namespaced second input rather than a re-run:

- **Own source connection** — every migration reads the `migrate_mmi` database
  key (the `mmi` D7 database), never the agsci `migrate` connection. The agsci
  migration map tables are preserved untouched as provenance.
- **Own migration namespace** — `mmi_*` migration ids in the `mmi_content`,
  `mmi_media` and `mmi_groups` groups, giving MMI its own map tables and
  high-water marks.
- **Fixed id offset** — MMI node and revision ids are offset by +400,000
  (`MmiNidOffset::OFFSET`), keeping them clear of the live id space while
  staying readable: MMI D7 node 4876 is D10 node 404876. The same constant
  drives the alias, redirect and menu-link rewrites.
- **Users match by ONID username**, never by uid: the `mmi_content` group
  resolves authorship through the `mmi_users` migration with `no_stub`, so
  content migrations hard-fail until user reconciliation has run.

Orchestrated by `scripts-dev/mmi_migrate.sh` in the osu_cas project, which
runs the whole sequence reproducibly against a freeze of the live database.
Plan and rationale: the "MMI Migration Audit" (2026-08-19).
