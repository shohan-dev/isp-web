#!/usr/bin/env node
/**
 * CSS integrity check — run from the repo root:
 *
 *     node isp-core/public/assets/css/check-css-integrity.js
 *
 * Catches the class of bug that silently deleted the entire mobile shell:
 * a comment containing a token list written as "--z-*\/--fab-*". The "*"
 * immediately followed by "/" terminates the comment early, the remaining prose
 * falls into the stylesheet as code, and a CSS parser then consumes that garbage
 * as a rule prelude — swallowing the next "{...}" block whole. In responsive.css
 * that block was "@media (max-width: 1024px)", i.e. the off-canvas drawer,
 * full-width topbar, content-wrapper margin reset, mobile padding and FAB
 * clearance. Nothing else flagged it: PHP lint, `node -c` and a naive
 * brace-balance count all pass on a file with this defect.
 *
 * Exit code 0 = clean, 1 = problems (suitable for CI / a pre-commit hook).
 */
'use strict';

const fs = require('fs');
const path = require('path');

const CSS_ROOT = path.join(__dirname);

function walk(dir, out = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(p, out);
    else if (entry.name.endsWith('.css')) out.push(p);
  }
  return out;
}

/** Remove comments exactly as a CSS parser does: /* ... first *\/ wins. */
function stripComments(src) {
  let out = '';
  let i = 0;
  let inComment = false;
  while (i < src.length) {
    if (!inComment && src.startsWith('/*', i)) { inComment = true; i += 2; continue; }
    if (inComment && src.startsWith('*/', i)) { inComment = false; i += 2; continue; }
    if (!inComment) out += src[i];
    i++;
  }
  return { code: out, unterminated: inComment };
}

const lineAt = (s, idx) => s.slice(0, idx).split('\n').length;

let problems = 0;

for (const file of walk(CSS_ROOT)) {
  const rel = path.relative(CSS_ROOT, file).replace(/\\/g, '/');
  const src = fs.readFileSync(file, 'utf8');
  const { code, unterminated } = stripComments(src);

  const report = (msg) => {
    problems++;
    console.error(`  ${rel}: ${msg}`);
  };

  if (unterminated) report('unterminated /* comment');

  // A "*/" surviving comment-stripping means it had no opener: the comment
  // before it closed early.
  const stray = code.indexOf('*/');
  if (stray !== -1) {
    report(`stray "*/" at code line ~${lineAt(code, stray)} — a comment closed early`);
  }

  // Box-drawing characters only ever appear in this repo's comment banners.
  // Seeing one in code means comment prose leaked out.
  const boxChar = code.search(/[═─│┌┐└┘]/);
  if (boxChar !== -1) {
    report(`comment banner leaked into code at line ~${lineAt(code, boxChar)}`);
  }

  // Braces must balance outside comments, and must never go negative.
  let depth = 0;
  let negativeAt = -1;
  for (let i = 0; i < code.length; i++) {
    const c = code[i];
    if (c === '{') depth++;
    else if (c === '}') {
      depth--;
      if (depth < 0 && negativeAt === -1) negativeAt = i;
    }
  }
  if (negativeAt !== -1) report(`extra "}" at line ~${lineAt(code, negativeAt)}`);
  else if (depth !== 0) report(`unbalanced braces (ends at depth ${depth})`);
}

if (problems) {
  console.error(`\nCSS integrity: ${problems} problem(s) found.`);
  process.exit(1);
}

console.log('CSS integrity: clean (comments terminate correctly, braces balance).');
