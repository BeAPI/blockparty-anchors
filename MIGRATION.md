# Migrating from `beapi/anchor` to `blockparty-anchors`

This document describes how to move from the old BeAPI anchor blocks to `beapi/blockparty-anchors`.

## Package swap

```bash
composer require beapi/blockparty-anchors
```

Activate `blockparty-anchors` and deactivate the previous anchor plugin.

## Block model changes

| Old | New |
|---|---|
| `beapi/anchor` | `blockparty/anchor` |
| `beapi/anchor-list` | `blockparty/anchors-list` |
| CSS `.wp-block-beapi-anchor` | CSS `.wp-block-blockparty-anchor` |
| CSS `.wp-block-beapi-anchor-list` | CSS `.wp-block-blockparty-anchors-list` |

Attributes (`title`, `slug`, `className`, and any extra attrs) are kept as-is.

Old blocks are typically void / self-closing. After migration they are serialized as empty self-closing `blockparty/*` comments:

```
<!-- wp:beapi/anchor {"title":"My section","slug":"my-section"} /-->
<!-- wp:beapi/anchor-list /-->
```

becomes:

```
<!-- wp:blockparty/anchor {"title":"My section","slug":"my-section"} /-->
<!-- wp:blockparty/anchors-list /-->
```

## Content migration (WP-CLI)

After activating `blockparty-anchors`:

```bash
# Preview (current site)
wp blockparty-anchors migrate-from-anchor-block --dry-run

# Apply on the current site
wp blockparty-anchors migrate-from-anchor-block

# Limit post types
wp blockparty-anchors migrate-from-anchor-block --post-type=post,page
```

The command always targets **one site only**. On multisite, repeat it yourself with `--url=` for each site:

```bash
wp blockparty-anchors migrate-from-anchor-block --url=https://example.com/
wp blockparty-anchors migrate-from-anchor-block --url=https://example.com/site-2/
```

The command:

1. Scans `post_content` for `beapi/anchor` (covers `beapi/anchor` and `beapi/anchor-list`)
2. Parses blocks, converts markup recursively (including nested group/columns), serializes back
3. Updates the post with `wp_slash()` on content (required so `\u002d` escapes for `--` in block comments are not stripped by `wp_update_post()`)
4. Logs migrated / skipped counts

If `wp_update_post()` fails with `invalid_page_template` (orphaned `_wp_page_template` meta), the command does not abort. It updates `post_content` only via `$wpdb->update()` (unslashed), then calls `clean_post_cache()`. Those posts are logged as `[update-fallback]`.

### Revisions

Only the scanned post types are migrated (public REST post types + `wp_block` by default). **Revisions are not included**, so parent posts are updated while historical revisions (and trash) can still contain the old `beapi/anchor` / `beapi/anchor-list` markup. That is expected. To migrate revisions as well, pass them explicitly, for example:

```bash
wp blockparty-anchors migrate-from-anchor-block --post-type=revision
```

### Duplicate Post (Yoast)

If [Yoast Duplicate Post](https://wordpress.org/plugins/duplicate-post/) is active, updating a Rewrite & Republish **copy** via `wp_update_post()` (especially without `--user=`) can call `wp_die( 'You are not allowed to republish this post.' )` and abort the whole WP-CLI run.

Preferred workaround: skip that plugin for the migration only (global WP-CLI flag):

```bash
wp --skip-plugins=duplicate-post blockparty-anchors migrate-from-anchor-block --url=https://example.com/
```

## Front-end checklist

- Re-register custom block styles on `blockparty/anchor` / `blockparty/anchors-list`
- Update theme SCSS selectors from `.wp-block-beapi-anchor` / `.wp-block-beapi-anchor-list` to `.wp-block-blockparty-anchor` / `.wp-block-blockparty-anchors-list`
- Update block patterns / allowlists
- Smoke-test in-page links, the anchors list, and nested anchors inside groups/columns
