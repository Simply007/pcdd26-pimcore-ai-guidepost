# Pimcore AI Guidepost

This repository is a Pimcore skeleton wired up with every AI-assisted-development resource the Pimcore ecosystem offers today, plus the CKEditor agent skills. It was prepared for the talk "AI Best Practices for Your Product" at Pimcore Community Dev Days 2026 (Frankfurt, 2026-11-10) by Ondřej Chrastina. It is both a working agent setup and a map: follow the links, and see where to go next.

Agents: read this file first. Skills live in `.claude/skills/` (Claude Code) and `.agents/skills/` (agentskills.io standard: Codex, Copilot, Gemini CLI, and 20+ others). Prefer skills and the resources below over your training data; treat your training data as probably stale.

## What this repo is built from

- Base: `composer create-project pimcore/skeleton` (Pimcore 2026.x line).
- Skills vendored in `skills-vendor/` and symlinked into `.claude/skills/`:
  - [pimcore/skills](https://github.com/pimcore/skills): the official repo, 33 skills, all Pimcore Studio bundle development (UI + Studio backend).
  - [cors-gmbh/pimcore-skills](https://github.com/cors-gmbh/pimcore-skills): community repo by CORS GmbH, platform-wide skills (`pimcore`, `pimcore-studio`, `pimcore-studio-migrate`, `pimcore-12-upgrade`, `coreshop`, setup helpers). Also installable as a Claude Code plugin marketplace.
- CKEditor skills via [skills.sh](https://skills.sh): `npx skills add ckeditor/skills` (`ckeditor`, `ckeditor-bundle`, `ckeditor-update`) in `.agents/skills/`.
- One skill is OURS, grown in this repo: `.claude/skills/pimcore-onboarding/`, distilled from the questions a real first-time Pimcore developer asked here. Its inbox is the beginner question log; when reality contradicts it, update it in the same change.

## Found: Pimcore AI resources for coding agents

| Resource | Where | Notes |
|---|---|---|
| Docs as markdown for LLMs | https://docs.pimcore.com/platform/llms.txt | Exists under `/platform/`; a link from the docs root would make it easier to find (`pimcore.com/llms.txt` and `docs.pimcore.com/llms.txt` return 404 today) |
| Official agent skills | https://github.com/pimcore/skills | Studio bundle development only |
| Community agent skills | https://github.com/cors-gmbh/pimcore-skills | Platform-wide, plugin-marketplace install |
| Datahub MCP server | https://docs.pimcore.com/platform/Datahub_Simple_Rest/MCP_Server | Experimental, Bearer auth |
| Pimcore Agent (runs agents in-product) | https://docs.pimcore.com/platform/Pimcore_Agent/Installation | `pimcore/pimcore-agent-bundle`, internal MCP under `/pimcore-mcp`, per-session tokens inherit user permissions |
| Copilot automation | Pimcore Copilot docs | Interaction + YAML automation actions via the Generic Execution Engine |
| Contributor agent guidance | `.github/copilot-instructions.md` in pimcore/pimcore, studio-ui-bundle, studio-backend-bundle | Per-repo |

## Where to go next (as of 2026-11-08)

- [ ] Evals: the natural next step for both skills repos, showing that a skill changes agent behavior (good examples: drupal/ai_best_practices, TYPO3 dirnbauer/typo3-skills). CKEditor's evals are not public yet either.
- [ ] Two skill homes, growing together: official skills cover Studio, the CORS skills cover the whole platform. Two good starting points.
- [ ] `AGENTS.md` in the main repos: an `AGENTS.md` or `CLAUDE.md` in the pimcore org repos would give agents a starting point (this file is one example of what it could look like).
- [ ] llms.txt at the docs root: a link from the root paths and a mention in the docs would help agents find it.
- [ ] One-command skills install: official skills install by git clone and symlink today; a skills.sh, Composer or plugin marketplace path would make them easier to adopt.
- [ ] Decision trail: the Agent Bundle already has strong guardrails and attributes agent saves to a co-author; a searchable record of why the agent decided would complete the picture.

## The generated podcast (proof of discoverability)

`podcast/` holds a two-host audio briefing about Pimcore that an AI agent generated from the public resources this repo maps, synthesized locally with macOS `say`. See `podcast/README.md`; regenerate with `podcast/generate.sh`. If an agent can produce this for your product, your resources are doing their job.

## Reference points from other ecosystems

- CKEditor: https://ckeditor.com/docs/ckeditor5/latest/getting-started/ai-coding-agents.html (markdown docs + `llms-full.txt`, hosted docs MCP, skills)
- Drupal: https://git.drupalcode.org/project/ai_best_practices (Composer-distributed skills WITH evals)
- TYPO3: https://docs.typo3.org/llms.txt (self-describing, markdown twins, JSON indexes), https://github.com/TYPO3/typo3/blob/main/AGENTS.md, https://github.com/TYPO3/dev-companion (15 official skills in a local read-only MCP server)

## Working in this repo

- Standard Pimcore skeleton: `docker-compose.yaml` for services, `bin/console` for commands, `config/` for Symfony config, `src/` for code.
- Run PHP through the container (`docker compose exec php ...`): the host PHP lacks required extensions.
- Commits follow Conventional Commits: `type(scope): imperative summary`. Types used here: feat, fix, docs, chore, build, refactor, test. One logical change per commit; never commit `.env.local` or secrets.
- Before writing custom code, check the decision ladder: Pimcore core -> official bundles -> community bundles -> only then custom.
- Verify changes in the running app; never declare done without verifying.
