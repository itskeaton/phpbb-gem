# Tier 1: Connections (character-to-character linking)

## Core model

A connection is a **directed** link, owned by the character (and therefore
the player) who created it - not a symmetric relationship requiring both
sides to agree. Character A's player can link to Character B without B's
involvement. If B's player wants to add their own view of the relationship,
they create their own separate entry (which can use a different category
and description - a one-sided crush shows up differently than a mutual
friendship, naturally, without any special-casing).

- **Categories are ACP-configurable** - name + colour per category, admin
  defines the list (Family, Friends, Rivals, etc.)
- **Colour shows as an avatar border** on the connected character's avatar,
  wherever connections are displayed - the category-colour-border concept
  from TGG's version.
- Only **active characters** can be connected to, or do the connecting -
  consistent with every other visibility rule in this build (archived/
  deactivated/pending/declined characters don't participate).
- A character can't connect to itself.
- Target character is resolved by **name lookup** (text input, server-side
  resolution), not a giant dropdown of every character on the site -
  consistent with how phpBB's own "add friend by username" pattern works.

## Data model

```
phpbb_connection_categories
  category_id    PK
  category_name
  color          (hex, no leading #)
  sort_order

phpbb_connections
  connection_id       PK
  character_id         the character this entry belongs to (the owner)
  connected_character_id  who they're connected to
  category_id
  description          optional - "how I see them"
  created_at
```

## Surfaces

1. **ACP** - category CRUD (name + colour), new mode on the existing Gem
   ACP module, same pattern as ticket categories.
2. **UCP** - new module. For each of the player's own characters: list
   existing connections, add new (name lookup + category + optional
   description), edit, delete. Only the owning player can manage their own
   character's connections - never someone else's, even for the "same"
   relationship from the other side.
3. **Character profile page** (`character_roster.php`, existing) - new
   Connections section: grid of connected characters, avatar with
   category-coloured border, category label, links to each connected
   character's own profile.

## Deliberately out of scope for this pass

- **No reciprocal-connection prompting.** When A connects to B, B's player
  isn't notified or nudged to add their own entry - ties into the same
  deferred notification-system work flagged back in the Ticketing System.
- **No connection count / relationship-web visualization.** Just a list on
  the profile page for now.
