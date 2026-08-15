# Tier 1: Registration Wizard

## Update: the hard gate now exists

This doc originally explained why `register_wizard.php` was NOT
hard-enforced (no access to `ucp_register.php` at the time). That file has
since been shared and the gate is now specced precisely - see
`docs/tier3-registration-hardgate.md` for the actual patch (block access to
the registration form until `phpbb_registration_progress.completed = 1`
for the session, applied at the top of `ucp_register.php`'s `main()`).

That patch hasn't necessarily been applied yet just because it's
documented - check `tier3-registration-hardgate.md` directly for status.

## Setup: point your Register link here

Wherever your site currently links to `ucp.php?mode=register` (nav bar,
login box, etc.), change that link to `register_wizard.php` instead. This
is a template change in files this build hasn't seen, so it's a manual
edit on your end rather than something to patch blind.

Once the hard-gate patch is applied, this becomes belt-and-suspenders
rather than the only thing keeping people on the intended path - but it's
still worth doing, since the gate alone would otherwise just bounce
visitors who land directly on `ucp.php?mode=register` without ever seeing
why.
