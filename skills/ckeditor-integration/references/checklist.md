# Verify checklist

Run in the real host, with real content. Do not declare the integration done
before every line is checked or listed as a known limitation.

## Slot and lifecycle
- [ ] Every rich text surface renders CKEditor (record fields, page regions, iframes, modals)
- [ ] Editor selection config switches editors with no rebuild; a missing editor falls back with a warning
- [ ] Opening a record and closing it without edits leaves it unmodified
- [ ] External value changes (host undo, reload, language switch) show up in the editor
- [ ] Closing the record destroys editors and shared contexts

## Data
- [ ] Round trip on legacy content: load, save, reload, diff is empty or explained
- [ ] Every toolbar feature survives the host's save (no silent sanitizer loss)
- [ ] Host reference attributes survive load and save; drop and pickers produce them

## Host features
- [ ] Link rewriting, dependency tracking, "used in" views still see the references
- [ ] Assets dropped or uploaded land in the host DAM with thumbnails as before
- [ ] Frontend, APIs, search and exports render the new markup

## Configuration and premium
- [ ] No key: `licenseKey: 'GPL'`, no premium chunk requested, CSP unchanged
- [ ] Key + gates: premium loads, token endpoint 200 for a logged-in user, 401 anonymous
- [ ] The access key appears in no browser response and no committed file
- [ ] Collaboration: two users on one field see each other; different fields and records never share a channel
- [ ] AI sees the intended fields and edits the field it names

## Shipping
- [ ] Fresh install from the package registry following only the README works
- [ ] The published build matches the source (build id or hash check in CI)
