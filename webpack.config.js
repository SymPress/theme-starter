import Encore from '@symfony/webpack-encore';
import { writeDesignTokens } from './scripts/design-tokens.mjs';
import { fileURLToPath } from 'node:url';

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  .setOutputPath('build/')
  .setPublicPath('./')
  .setManifestKeyPrefix('')
  .addEntry('sympress-starter-app', './resources/js/app.js')
  .addStyleEntry('sympress-starter-editor', './resources/css/editor.css')
  .enablePostCssLoader()
  .configureCssMinimizerPlugin((options, MinimizerPlugin) => {
    options.minify = MinimizerPlugin.lightningCssMinify;
  })
  .disableSingleRuntimeChunk()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .cleanupOutputBeforeBuild();

export default Promise.resolve(Encore.getWebpackConfig()).then((config) => {
  config.plugins.push({ apply(compiler) {
    compiler.hooks.beforeCompile.tap('SymPressDesignTokens', writeDesignTokens);
    compiler.hooks.afterCompile.tap('SymPressDesignTokens', compilation => {
      compilation.fileDependencies.add(fileURLToPath(new URL('./theme.json', import.meta.url)));
    });
  } });
  // Entry manifests stay relative; lazy chunks resolve from the executing script URL.
  config.output.publicPath = 'auto';
  return config;
});
