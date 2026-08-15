# Tier 3: Registration Hard-Gate + Precise Welcome Bonus (ucp_register.php)

Reference material was your actual `ucp_register.php`/`ucp_register.html` -
not edited, not deployed. This documents the equivalent patch for Gem's own
copy. Two separate, independent changes in the same file.

## Finding: standard phpBB core, two real documented events nearby

No TGG-specific customization in the registration flow logic itself (the
template has your site's own notice banner and username-format guidance,
but that's content, not structure). Two genuine core events exist right at
the two points that matter here - `core.ucp_register_requests_after` (top
of `main()`) and `core.ucp_register_user_row_after` (right before
`user_add()`) - but since this build does direct core-file patches rather
than event-listener extensions, the changes below insert directly rather
than hooking those events.

## 1. Hard-gate: block the registration form until the wizard is complete

**Where:** top of `main()`, immediately after the existing
`UCP_REGISTER_DISABLE` check (~line 40), before anything else runs. This
blocks the form entirely - viewing it, not just submitting it - until the
wizard's been completed for this session.

```php
global $config, $db, $user, $template, $phpbb_root_path, $phpEx;
global $request, $phpbb_container, $phpbb_dispatcher, $table_prefix;

if ($config['require_activation'] == USER_ACTIVATION_DISABLE ||
	(in_array($config['require_activation'], array(USER_ACTIVATION_SELF, USER_ACTIVATION_ADMIN)) && !$config['email_enable']))
{
	trigger_error('UCP_REGISTER_DISABLE');
}

// Gem: block registration until the wizard is completed for this session
$sql = 'SELECT completed FROM ' . $table_prefix . "registration_progress
		WHERE session_id = '" . $db->sql_escape($user->data['session_id']) . "'";
$result = $db->sql_query($sql);
$wizard_completed = (bool) $db->sql_fetchfield('completed');
$db->sql_freeresult($result);

if (!$wizard_completed)
{
	redirect(append_sid("{$phpbb_root_path}register_wizard.{$phpEx}"));
}
```

Note the added `$table_prefix` to the existing `global` line - it wasn't
already in scope in this function.

## 2. Precise welcome bonus - right after the account actually exists

**Where:** immediately after the existing `NO_USER` failure check (~line
427), once `$user_id` is confirmed real.

```php
require_once($phpbb_root_path . 'includes/gem/points_helper.' . $phpEx);

// Register user...
$user_id = user_add($user_row, $cp_data);

// This should not happen, because the required variables are listed above...
if ((bool) $user_id === false)
{
	trigger_error('NO_USER', E_USER_ERROR);
}

// Gem: precise welcome bonus, right at actual account creation
gem_maybe_award_registration_bonus((int) $user_id);
```

(The `require_once` line only needs to happen once - put it near the top
of the file alongside phpBB's own includes, not literally inline every
time, that's just showing where in the flow it's needed relative to.)

## Why the existing fallback in the 5 UCP controllers stays

`gem_maybe_award_registration_bonus()` is idempotent - it checks the
ledger before awarding anything. With this patch applied, the bonus fires
the moment the account is created, and the fallback checks already in
`ucp_characters.php`/`ucp_tickets.php`/`ucp_connections.php`/
`ucp_wanted.php`/`ucp_shop.php` become harmless no-ops for anyone who went
through normal registration - they'll already show as "already awarded."

That redundancy is intentional, not sloppy: `ucp_register.html` references
`PROVIDER_TEMPLATE_FILE` (OAuth/social registration), a code path this
review didn't trace through to confirm it also calls `user_add()` the same
way. Keeping the fallback means OAuth registrants (or any registration
path this patch doesn't cover) still get the bonus on their first Gem UCP
visit, instead of silently missing out. Don't remove the fallback calls
when applying this patch.
