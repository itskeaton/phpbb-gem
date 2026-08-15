# Tier 1: Registration Wizard

## How this actually gates registration

`register_wizard.php` is the **primary, linked-to entry point** - it is not
a hard-enforced checkpoint. Nothing stops a visitor who already knows the
URL from going straight to `ucp.php?mode=register` and skipping the wizard
entirely.

This was a deliberate choice, not an oversight: hard-gating would mean
patching `ucp_register.php`, a file this build has never seen. Guessing at
that file's structure risks exactly the kind of blind edit avoided
everywhere else in this build (memberlist, viewtopic, posting, search,
mcp - all patched only after seeing the real file).

## Setup: point your Register link here

Wherever your site currently links to `ucp.php?mode=register` (nav bar,
login box, etc.), change that link to `register_wizard.php` instead. This
is a template change in files this build hasn't seen, so it's a manual
edit on your end rather than something to patch blind.

## If you want a true hard gate later

Share `ucp_register.php` (the actual phpBB registration controller) the
same way `viewtopic.php` etc. were shared, and the gate can be added
precisely: check `phpbb_registration_progress` for the current
`session_id`, and confirm `completed = 1` before allowing the registration
form to process. Without that file, this can't be built confidently -
same reasoning as every other core-file patch in this build.
