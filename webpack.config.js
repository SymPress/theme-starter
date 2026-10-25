import Encore from '@symfony/webpack-encore';

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
  .cleanupOutputBeforeBuild()
  // The asset loader only accepts local relative manifest paths, so the dev server
  // writes builds to disk and only pushes full reloads over its websocket.
  .configureDevServerOptions((options) => {
    options.devMiddleware = { writeToDisk: true };
    options.hot = false;
    options.liveReload = true;
    options.watchFiles = ['*.php', 'resources/views/**/*.twig', 'src/**/*.php', 'patterns/**/*.php', 'theme.json'];
    // Sites outside localhost (e.g. *.ddev.site) must be listed; never "all", which disables DNS-rebinding checks.
    const allowedHosts = (process.env.ENCORE_DEV_SERVER_ALLOWED_HOSTS ?? '').split(',').map((host) => host.trim()).filter(Boolean);
    options.allowedHosts = allowedHosts.length > 0 ? allowedHosts : 'auto';
    options.client = {
      overlay: false,
      webSocketURL: process.env.ENCORE_DEV_SERVER_WS_URL || `ws://localhost:${options.port}/ws`,
    };
  });

export default Promise.resolve(Encore.getWebpackConfig()).then((config) => {
  // Entry manifests stay relative; lazy chunks resolve from the executing script URL.
  config.output.publicPath = 'auto';
  return config;
});
