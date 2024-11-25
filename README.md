# Entity Base

Entity Base is a content analysis plugin that uses the TextRazor Natural Language Processor to extract entities from post content.

Entities are stored in a new Entity post type, tagged with thier DBPedia and Freebase types. Individual posts associated with an entity store the relationship in its post meta.

## Settings

The settings page can be found under the "Entities" section in the WordPress admin menu.

- **API Key**: Configure the API key for the TextRazor service. Free API keys are available from textrazor.com.
- **Entity Allowlist**: Entities that should always be included in the analysis, one per line.
- **Entity Blocklist**: Entities that should always be excluded from the analysis, one per line.
- **DBPedia Filters**: DBPedia types to include in the analysis, one per line.
- **Freebase Filters**: Freebase types to include in the analysis, one per line.
- **DBPedia Blocklist**: DBPedia types to exclude from the analysis, one per line.
- **Freebase Blocklist**: Freebase types to exclude from the analysis, one per line.
- **Minimum Confidence Score**: Set the minimum confidence score required for an entity to be considered valid.
- **Minimum Relevance Score**: Set the minimum relevance score required for an entity to be considered valid.

## Processing content

Individual posts are analysed on save. This includes saving as a draft, or publishing content.

Posts can be processed in bulk using WP CLI with the following command:

```sh
wp entity-base analyse
```

The following options are available:

- `posts_per_page`: Number of posts to analyse at a time. Default: `100`.
- `max_requests`: Total number of posts to analyse, `0` for all. Default: `100`.
- `post_types`: Comma separated list of all post types to process. Default: `post`.
- `post_id`: Single post ID to process.
- `initial_page`: Skip pages, useful to resume analysis. Default: `1`.

### Filtering

All entites are returned from TextRazor, then filtered during processing. Some of these entities do not have a DBPedia type or Freebase type; entities must match at least one allowed type to be considered valid.

### Caching

The TextRazor response is stored as a transient for each post. The cache key is hashed using the data that is sent for processing, meaning any changes to content will require a fresh analysis from TextRazor.

## Data relationships

When a post is analysed, the extracted entities are saved to the post's meta data with the following keys:

- `_entity_{entity_slug}`: Confidence score for each entity.
- `_entity_rel_{entity_slug}`: Relevance score for each entity.
- `_entities`: Array of all extracted entites and associated data from TextRazor.

The posts associated with an entity can be queried using a meta key EXISTS query. Or by using the utility function `EntityBase\Utils\query_connected_posts`.

Example code:

```php
$connected_posts = EntityBase\Utils\query_connected_posts( $entity_post );

// Or

$connected_posts = new WP_Query( [
	'post_type' => 'any',
	'post_status' => 'publish',
	'meta_key' => mb_strcut( '_entity_' . $entity->post_name, 0, 256 ),
	'meta_compare' => 'EXISTS',
] );
```

When an entity is deleted, the confidence and relevence scores attached to posts are also deleted.

The connected post count is stored in the comment_count column so you can query and order entities based on this value in a performant way.

To query for all entities attached to a post, you can use the utility function `get_entities_for_post` which will return the entities ordered by relevance score.

```
$entities = EntityBase\Utils\get_entities_for_post( WP_Post $post );
```

## Exporting entities

Entities can be exported through the WordPress admin or via WP CLI. The export contains individual URLs for posts associated with an entity. The number of URLs returned for each entitiy can be configured using the `max_urls` parameter.

A CSV export using default settings can be downloaded from the settings page.

Entities can be exported using WP CLI with the following command:

```sh
wp entity-base export
```

The following options are available:

- `format`: The data format to get results in, `json` or `csv`. Default: `json`.
- `max_urls`: Number of URLs/posts to return per entity. Default: `100`.
- `chunk_size`: Number of records to return per chunk. Default: `300`.

## Available hooks

Filters

- `entitybase_allowed_post_types`: Specifiy which posts types to analyse on save.
- `entitybase_filter_post_content`: Modify post content before it is sent to TextRazor.

Actions

- `entitybase_entity_created`: Fires after a new Entity has been created.
