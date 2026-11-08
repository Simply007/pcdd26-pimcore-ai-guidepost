<?php

declare(strict_types=1);

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Page;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Seeds the Home document (id 1) with the Pimcore AI Guidepost content from AGENTS.md.
 *
 * Idempotent: every run writes the same canonical content, so it can be re-run at any
 * time to restore the page. Note that it therefore overwrites manual edits made in the
 * admin; the canonical source is AGENTS.md.
 */
#[AsCommand(
    name: 'app:guidepost:seed',
    description: 'Seed the Pimcore AI Guidepost page (Home document) with content derived from AGENTS.md',
)]
class GuidepostSeedCommand extends AbstractCommand
{
    private const CONTROLLER = 'App\\Controller\\GuidepostController::guidepostAction';

    private const TEMPLATE = 'guidepost/guidepost.html.twig';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $home = Document::getById(1);

        if (!$home instanceof Page) {
            $this->io->error('Document id 1 (Home) not found or not a Page document. Is Pimcore installed?');

            return self::FAILURE;
        }

        $home->setController(self::CONTROLLER);
        $home->setTemplate(self::TEMPLATE);
        $home->setTitle('Pimcore AI Guidepost');
        $home->setDescription(
            'Every AI-assisted-development resource the Pimcore ecosystem offers today, '
            . 'with ideas for where to go next. Prepared for Pimcore Community Dev Days 2026.'
        );

        $this->setInput($home, 'headline', 'Pimcore AI Guidepost');

        foreach ($this->wysiwygContent() as $name => $html) {
            $this->setWysiwyg($home, $name, $html);
        }

        $home->setPublished(true);
        $home->save();

        $this->io->success(sprintf(
            'Seeded document %d (%s): controller %s, template %s, editables: headline, %s',
            $home->getId(),
            $home->getRealFullPath(),
            self::CONTROLLER,
            self::TEMPLATE,
            implode(', ', array_keys($this->wysiwygContent())),
        ));

        return self::SUCCESS;
    }

    private function setInput(Page $page, string $name, string $text): void
    {
        $editable = new Editable\Input();
        $editable->setDocument($page);
        $editable->setName($name);
        $editable->setRealName($name);
        $editable->setDataFromEditmode($text);

        $page->setEditable($editable);
    }

    private function setWysiwyg(Page $page, string $name, string $html): void
    {
        $editable = new Editable\Wysiwyg();
        $editable->setDocument($page);
        $editable->setName($name);
        $editable->setRealName($name);
        $editable->setDataFromEditmode($html);

        $page->setEditable($editable);
    }

    /**
     * The guidepost body, derived from AGENTS.md (same links, same tables).
     * Inline code uses span.code because the Pimcore wysiwyg sanitizer strips <code> elements.
     *
     * @return array<string, string>
     */
    private function wysiwygContent(): array
    {
        return [
            'intro' => <<<'HTML'
<p>This repository is a Pimcore skeleton wired up with every AI-assisted-development resource the Pimcore ecosystem offers today, plus the CKEditor agent skills. It was prepared for the talk <strong>"AI Best Practices for Your Product"</strong> at Pimcore Community Dev Days 2026 (Frankfurt, 2026-11-10) by Ond&#345;ej Chrastina. It is both a working agent setup and a map: follow the links, and see where to go next.</p>
<p>Agents: read <span class="code">AGENTS.md</span> first. Skills live in <span class="code">.claude/skills/</span> (Claude Code) and <span class="code">.agents/skills/</span> (agentskills.io standard: Codex, Copilot, Gemini CLI, and 20+ others). Prefer skills and the resources below over your training data; treat your training data as probably stale.</p>
<p><strong>What this repo is built from:</strong></p>
<ul>
<li>Base: <span class="code">composer create-project pimcore/skeleton</span> (Pimcore 2026.x line).</li>
<li>Skills vendored in <span class="code">skills-vendor/</span> and symlinked into <span class="code">.claude/skills/</span>: <a href="https://github.com/pimcore/skills" target="_blank" rel="noopener">pimcore/skills</a> (the official repo, 33 skills, all Pimcore Studio bundle development) and <a href="https://github.com/cors-gmbh/pimcore-skills" target="_blank" rel="noopener">cors-gmbh/pimcore-skills</a> (community repo by CORS GmbH, platform-wide skills, also installable as a Claude Code plugin marketplace).</li>
<li>CKEditor skills via <a href="https://skills.sh" target="_blank" rel="noopener">skills.sh</a>: <span class="code">npx skills add ckeditor/skills</span> in <span class="code">.agents/skills/</span>.</li>
<li>One skill is <strong>ours</strong>, grown in this repo: <span class="code">pimcore-onboarding</span>, distilled from the questions a real first-time Pimcore developer asked here. Its inbox is the beginner question log; when reality contradicts it, update it in the same change.</li>
</ul>
HTML,
            'found_resources' => <<<'HTML'
<table>
<thead>
<tr><th>Resource</th><th>Where</th><th>Notes</th></tr>
</thead>
<tbody>
<tr><td>Docs as markdown for LLMs</td><td><a href="https://docs.pimcore.com/platform/llms.txt" target="_blank" rel="noopener">docs.pimcore.com/platform/llms.txt</a></td><td>Exists under <span class="code">/platform/</span>; a link from the docs root would make it easier to find</td></tr>
<tr><td>Official agent skills</td><td><a href="https://github.com/pimcore/skills" target="_blank" rel="noopener">github.com/pimcore/skills</a></td><td>Studio bundle development only</td></tr>
<tr><td>Community agent skills</td><td><a href="https://github.com/cors-gmbh/pimcore-skills" target="_blank" rel="noopener">github.com/cors-gmbh/pimcore-skills</a></td><td>Platform-wide, plugin-marketplace install</td></tr>
<tr><td>Datahub MCP server</td><td><a href="https://docs.pimcore.com/platform/Datahub_Simple_Rest/MCP_Server" target="_blank" rel="noopener">Datahub Simple REST: MCP Server</a></td><td>Experimental, Bearer auth</td></tr>
<tr><td>Pimcore Agent (runs agents in-product)</td><td><a href="https://docs.pimcore.com/platform/Pimcore_Agent/Installation" target="_blank" rel="noopener">Pimcore Agent: Installation</a></td><td><span class="code">pimcore/pimcore-agent-bundle</span>, internal MCP under <span class="code">/pimcore-mcp</span>, per-session tokens inherit user permissions</td></tr>
<tr><td>Copilot automation</td><td>Pimcore Copilot docs</td><td>Interaction + YAML automation actions via the Generic Execution Engine</td></tr>
<tr><td>Contributor agent guidance</td><td><span class="code">.github/copilot-instructions.md</span> in pimcore/pimcore, studio-ui-bundle, studio-backend-bundle</td><td>Per-repo</td></tr>
</tbody>
</table>
HTML,
            'marked_gaps' => <<<'HTML'
<ul>
<li>&#9744; <strong>Evals:</strong> the natural next step for both skills repos, showing that a skill changes agent behavior (good examples: drupal/ai_best_practices, TYPO3 dirnbauer/typo3-skills). CKEditor's evals are not public yet either.</li>
<li>&#9744; <strong>Two skill homes, growing together:</strong> official skills cover Studio, the CORS skills cover the whole platform. Two good starting points.</li>
<li>&#9744; <strong><span class="code">AGENTS.md</span> in the main repos:</strong> an <span class="code">AGENTS.md</span> or <span class="code">CLAUDE.md</span> in the pimcore org repos would give agents a starting point (this page is one example of what it could look like).</li>
<li>&#9744; <strong>llms.txt at the docs root:</strong> a link from the root paths and a mention in the docs would help agents find it.</li>
<li>&#9744; <strong>One-command skills install:</strong> official skills install by git clone and symlink today; a skills.sh, Composer or plugin marketplace path would make them easier to adopt.</li>
<li>&#9744; <strong>Decision trail:</strong> the Agent Bundle already has strong guardrails and attributes agent saves to a co-author; a searchable record of why the agent decided would complete the picture.</li>
</ul>
HTML,
            'podcast_note' => <<<'HTML'
<p>The <span class="code">podcast/</span> folder of this repo holds a two-host audio briefing about Pimcore that an AI agent generated from the public resources this page maps, synthesized locally with macOS <span class="code">say</span>. See <span class="code">podcast/README.md</span>; regenerate with <span class="code">podcast/generate.sh</span>. If an agent can produce this for your product, your resources are doing their job.</p>
HTML,
            'reference_points' => <<<'HTML'
<ul>
<li><strong>CKEditor:</strong> <a href="https://ckeditor.com/docs/ckeditor5/latest/getting-started/ai-coding-agents.html" target="_blank" rel="noopener">AI coding agents guide</a> (markdown docs + <span class="code">llms-full.txt</span>, hosted docs MCP, skills)</li>
<li><strong>Drupal:</strong> <a href="https://git.drupalcode.org/project/ai_best_practices" target="_blank" rel="noopener">ai_best_practices</a> (Composer-distributed skills WITH evals)</li>
<li><strong>TYPO3:</strong> <a href="https://docs.typo3.org/llms.txt" target="_blank" rel="noopener">docs.typo3.org/llms.txt</a> (self-describing, markdown twins, JSON indexes), <a href="https://github.com/TYPO3/typo3/blob/main/AGENTS.md" target="_blank" rel="noopener">TYPO3 AGENTS.md</a>, <a href="https://github.com/TYPO3/dev-companion" target="_blank" rel="noopener">dev-companion</a> (15 official skills in a local read-only MCP server)</li>
</ul>
HTML,
        ];
    }
}
