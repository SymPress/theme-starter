import { readFileSync, existsSync } from 'node:fs';
import assert from 'node:assert/strict';

const theme = JSON.parse(readFileSync(new URL('../../theme.json', import.meta.url)));
const available = new Set();
for (const [type, values] of [
  ['color', theme.settings.color.palette],
  ['font-family', theme.settings.typography.fontFamilies],
  ['font-size', theme.settings.typography.fontSizes],
  ['spacing', theme.settings.spacing.spacingSizes],
]) {
  for (const { slug } of values ?? []) available.add(`--wp--preset--${type}--${slug}`);
}
const css = readFileSync(new URL('../../resources/css/tailwind-theme.css', import.meta.url), 'utf8');
const references = [...css.matchAll(/var\((--wp--preset--[a-z0-9-]+)\)/g)];
assert(references.length > 0, 'Tailwind must reference native WordPress presets.');
for (const [, variable] of references) assert(available.has(variable), `Missing theme.json preset: ${variable}`);
for (const obsolete of ['tokens.css', 'tailwind-tokens.css']) {
  assert(!existsSync(new URL(`../../resources/css/${obsolete}`, import.meta.url)), `Obsolete generated file: ${obsolete}`);
}
console.log(`PASS: ${references.length} native preset aliases; no obsolete generated token files.`);
