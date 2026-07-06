import fs from 'fs';
import path from 'path';

const skillsDir = '.agents/skills';
const rulesDir = '.cursor/rules';

function parseSkillFrontmatter(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n([\s\S]*)$/);
  if (!match) return { description: '', body: content };
  const fm = match[1];
  const descMatch = fm.match(/^description:\s*(.+)$/m);
  return {
    description: descMatch ? descMatch[1].trim() : '',
    body: match[2],
  };
}

function toMdc(description, body) {
  return `---
description: ${description}
alwaysApply: false
---

${body.replace(/\r\n/g, '\n')}`;
}

function rewriteImpeccablePaths(body) {
  const refNames = [
    'adapt', 'animate', 'audit', 'bolder', 'brand', 'clarify', 'codex', 'colorize',
    'craft', 'critique', 'delight', 'distill', 'document', 'extract', 'harden', 'hooks',
    'init', 'interaction-design', 'layout', 'live', 'onboard', 'optimize', 'overdrive',
    'polish', 'product', 'quieter', 'shape', 'typeset',
  ];

  let out = body
    .replace(/reference\/<command>\.md/g, '.cursor/rules/impeccable-<command>.mdc')
    .replace(/reference\/<action>\.md/g, '.cursor/rules/impeccable-<action>.mdc')
    .replace(/reference\/([a-z-]+)\.md/g, '.cursor/rules/impeccable-$1.mdc');

  for (const name of refNames) {
    const rule = `impeccable-${name}.mdc`;
    out = out
      .replace(new RegExp(`\\[${name}\\.md\\]\\(${name}\\.md\\)`, 'g'), `[${rule}](${rule})`)
      .replace(new RegExp(`\\[codex\\.md\\]\\(${name}\\.md\\)`, 'g'), `[${rule}](${rule})`)
      .replace(new RegExp(`(?<![\\w-])${name}\\.md(?![\\w-])`, 'g'), rule);
  }

  return out;
}

fs.mkdirSync(rulesDir, { recursive: true });

const simpleSkills = ['frontend-design', 'mybb-plugin-dev', 'mybb-theme-dev', 'web-reference'];
for (const name of simpleSkills) {
  const skillPath = path.join(skillsDir, name, 'SKILL.md');
  const content = fs.readFileSync(skillPath, 'utf8');
  const { description, body } = parseSkillFrontmatter(content);
  fs.writeFileSync(path.join(rulesDir, `${name}.mdc`), toMdc(description, body));
  console.log(`Created ${name}.mdc`);
}

const impeccableSkill = fs.readFileSync(path.join(skillsDir, 'impeccable', 'SKILL.md'), 'utf8');
const { description: impDesc, body: impBodyRaw } = parseSkillFrontmatter(impeccableSkill);
const impBody = rewriteImpeccablePaths(impBodyRaw);
fs.writeFileSync(path.join(rulesDir, 'impeccable.mdc'), toMdc(impDesc, impBody));
console.log('Created impeccable.mdc');

const descMap = {
  craft: 'Impeccable craft: shape and build a frontend feature end-to-end',
  shape: 'Impeccable shape: plan UX/UI before writing code',
  init: 'Impeccable init: set up PRODUCT.md, DESIGN.md, and project context',
  document: 'Impeccable document: generate DESIGN.md from existing project code',
  extract: 'Impeccable extract: pull reusable tokens and components into design system',
  critique: 'Impeccable critique: UX design review with heuristic scoring',
  audit: 'Impeccable audit: technical quality checks (a11y, perf, responsive)',
  polish: 'Impeccable polish: final quality pass before shipping',
  bolder: 'Impeccable bolder: amplify safe or bland designs',
  quieter: 'Impeccable quieter: tone down aggressive or overstimulating designs',
  distill: 'Impeccable distill: strip to essence, remove complexity',
  harden: 'Impeccable harden: production-ready errors, i18n, edge cases',
  onboard: 'Impeccable onboard: first-run flows, empty states, activation',
  animate: 'Impeccable animate: add purposeful animations and motion',
  colorize: 'Impeccable colorize: add strategic color to monochromatic UIs',
  typeset: 'Impeccable typeset: improve typography hierarchy and fonts',
  layout: 'Impeccable layout: fix spacing, rhythm, and visual hierarchy',
  delight: 'Impeccable delight: add personality and memorable touches',
  overdrive: 'Impeccable overdrive: push past conventional limits',
  clarify: 'Impeccable clarify: improve UX copy, labels, and error messages',
  adapt: 'Impeccable adapt: adapt for different devices and screen sizes',
  optimize: 'Impeccable optimize: diagnose and fix UI performance',
  live: 'Impeccable live: visual variant mode in the browser',
  hooks: 'Impeccable hooks: manage design detector hook',
  brand: 'Impeccable brand register: marketing, landing pages, portfolios',
  product: 'Impeccable product register: app UI, dashboards, tools',
  'interaction-design': 'Impeccable interaction design patterns',
  codex: 'Impeccable codex-specific anti-patterns and fixes',
};

const refDir = path.join(skillsDir, 'impeccable', 'reference');
for (const file of fs.readdirSync(refDir).filter((f) => f.endsWith('.md'))) {
  const base = file.replace('.md', '');
  const body = rewriteImpeccablePaths(fs.readFileSync(path.join(refDir, file), 'utf8'));
  const description = descMap[base] ?? `Impeccable reference: ${base}`;
  fs.writeFileSync(path.join(rulesDir, `impeccable-${base}.mdc`), toMdc(description, body));
  console.log(`Created impeccable-${base}.mdc (${body.split('\n').length} lines)`);
}

console.log('Done. Total rules:', fs.readdirSync(rulesDir).filter((f) => f.endsWith('.mdc')).length);
