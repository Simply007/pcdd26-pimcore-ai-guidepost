# 5 and 6. Configuration and shipping

## Configuration layers

There are usually three layers. Document their precedence (later wins):

1. Integration defaults (toolbar preset, plugin set, GHS rules).
2. Host-level config of the integration (the platform's config file or
   settings screen): editor selection, toolbar, license key, premium gates.
3. Per-field config from the host (field definition, template options), which
   may be shaped for the previous editor. Pass through genuine CKEditor
   options; map or ignore the rest, and say which.

Toolbar items need a plugin in the shipped build. Unknown items are skipped
with a console warning; adding a plugin means a rebuild of the integration.

## Runtime injection

Settings reach the browser at page load, from the host backend: a config
payload on the admin shell page, a settings endpoint, or the host's own
"app config" mechanism. Consequences:

- Changing the key, toolbar or gates needs a cache clear, not a rebuild.
- The committed or published build contains no key and no instance values.

## License key and premium gates

- No key: `licenseKey: 'GPL'`, open source plugins only.
- Key + gate on: `licenseKey` from host config, premium plugins loaded through
  `import('ckeditor5-premium-features')` (and its CSS) so the bundler puts them
  in async chunks. Pin `ckeditor5-premium-features` to the exact `ckeditor5`
  version.
- A gate whose prerequisites are missing logs a warning and keeps the free
  editor running. A misconfiguration never breaks editing.
- Verify the split statically: the default load path (entry chunks, the
  module federation manifest if used) must contain no premium chunk.
- Some premium features constrain free ones (for example source editing next to
  real-time collaboration needs an explicit opt-in). Check the live docs per
  feature combination.

## Token endpoint (collaboration and AI)

A host backend route that returns a Cloud Services JWT:

- Behind the host's admin authentication; anonymous requests get 401.
- Signed server side with the Cloud Services access key, which never reaches
  the browser.
- `sub` and `user` from the host user; collaboration roles and AI permissions
  mapped from host permissions. Grant narrowly in production; a demo "writer on
  every channel" scope is not a production default.
- Returns a clear error (for example 503) when the environment is not
  configured. Check claim names and AI permission scopes in the live Cloud
  Services docs: a misspelled scope signs fine and grants nothing.

## Content Security Policy

Admin UIs often send a strict CSP. Premium features call Cloud Services over
HTTPS and WebSocket, and license checks call a CKEditor endpoint. Extend
`connect-src` (and anything else the live docs list) only while premium is
active, through the host's CSP mechanism.

A CSP block looks like a network problem: AI request failures with an empty
original error, or Cloud Services "initial connection attempts exceeded", with
the blocked requests missing from the network log. Check the CSP header first.

## Premium traps worth checking before the first live run

- CKEditor AI needs a `collaboration.channelId` even without real-time
  collaboration, and a missing one can take the whole editor down. Always
  derive one (see `data-and-collaboration.md`), with a per-mount fallback.
- A failed premium init may log the full editor config to the console,
  license key included. Tell users not to paste such logs publicly.

## Shipping

- Package for the host ecosystem (Composer bundle, npm plugin, marketplace
  module), with the host's install and enable steps documented.
- **Who builds the frontend?** Hosts that load prebuilt plugin code (module
  federation remotes, prebuilt admin extensions) need a committed build, a
  build in CI on release tags, or a release archive the host extracts. A
  package manager like Composer runs no Node build.
- Pin the host SDK version on both the backend and the frontend side to the
  same line.
- Licensing: CKEditor open source is GPL-2.0-or-later or commercial; premium
  features are commercial. State what the integration's own license is and how
  it combines with the host's license when someone distributes the result. Not
  legal advice; point to the CKEditor licensing page.
