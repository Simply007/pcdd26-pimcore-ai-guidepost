# Skills end-to-end test

This repository is the showcase for the `ckeditor-integration` skill
(`skills/ckeditor-integration/`) and its Pimcore worked example, the
`ckeditor-pimcore` skill that ships inside `simply007/ckeditor-studio-bundle`.
The end-to-end test checks that the skills change what an agent actually does:
an agent gets a task in this project with the CKEditor integration removed,
works only from the installed skills, and the result is graded by the same
checks a human would run.

## Scenarios

| Scenario | Graded by | What it proves |
|---|---|---|
| [`01-pimcore-add-ckeditor`](scenarios/01-pimcore-add-ckeditor.md) | `bin/skills-e2e check` (scripted) | From a project without the bundle, the agent finds and installs the existing integration, configures it at runtime, keeps keys out of git, and leaves a working Studio |
| [`02-host-integration-plan`](scenarios/02-host-integration-plan.md) | rubric (reviewer or LLM judge) | On a host the skill knows nothing about, the agent plans the integration along the seven points and routes editor setup to the `ckeditor` skill |

## Run scenario 01

Needs the running stack (README "Quick start"), a clean git tree on `main`,
and the `claude` CLI.

```bash
bin/skills-e2e prepare   # branch e2e/<timestamp>, removes the bundle, cache:clear
bin/skills-e2e run       # the agent works on the scenario prompt (transcript in var/skills-e2e/)
bin/skills-e2e check     # grades the result, exit code 0 = pass
bin/skills-e2e reset     # back to the branch you started from, bundle reinstalled
```

`run` starts the agent non-interactively with edits allowed in this
repository. Read the scenario prompt first; run it only on a local stack with
no real keys in `.env.local`.

## Baseline

Record a run without the skills (`bin/skills-e2e run --no-skills` hides
`.claude/skills/ckeditor-*` and `.agents/skills/ckeditor-integration` for the
run) to see what the skills add. Notes from the first baselines (2026-11-18,
plan-only, no stack) are in the scenario files.
