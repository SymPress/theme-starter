import Encore from '@symfony/webpack-encore';
import { writeDesignTokens } from './scripts/design-tokens.mjs';

writeDesignTokens();

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
  // Entry manifests stay relative; lazy chunks resolve from the executing script URL.
  config.output.publicPath = 'auto';
  return config;
});
