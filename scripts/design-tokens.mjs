import { readFileSync, writeFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

export function writeDesignTokens() {
  const theme = JSON.parse(readFileSync(new URL('../theme.json', import.meta.url), 'utf8'));
  const { palette } = theme.settings.color;
  const fonts = theme.settings.typography.fontFamilies;
  const declarations = [
    ...palette.map(({ slug, color }) => `  --color-${slug}: ${color};`),
    ...fonts.map(({ slug, fontFamily }) => `  --font-${slug}: ${fontFamily};`),
  ];
  const css = `/* Generated from theme.json by scripts/design-tokens.mjs. */\n:root {\n${declarations.join('\n')}\n}\n`;
  const target = new URL('../resources/css/tokens.css', import.meta.url);
  let previous = '';
  try { previous = readFileSync(target, 'utf8'); } catch (error) { if (error.code !== 'ENOENT') throw error; }
  if (previous !== css) writeFileSync(target, css);
  const tailwind = `/* Generated from theme.json. */\n@theme {\n${palette.map(({ slug, color }) => `  --color-${slug}: ${color};`).join('\n')}\n}\n`;
  const themeTarget = new URL('../resources/css/tailwind-tokens.css', import.meta.url);
  let previousTheme = '';
  try { previousTheme = readFileSync(themeTarget, 'utf8'); } catch (error) { if (error.code !== 'ENOENT') throw error; }
  if (previousTheme !== tailwind) writeFileSync(themeTarget, tailwind);
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) writeDesignTokens();
