<?php

declare(strict_types=1);

use SymPress\Kernel\App;
use SymPress\Kernel\Kernel\SiteKernel;

if (!defined('ABSPATH') || wp_installing()) {
    return;
}

// This source is linked into public/wp-content/mu-plugins; __DIR__ stays here.
$project = __DIR__;
if (App::kernel() === null) {
    App::bootKernel(new SiteKernel($project, 'test', true));
}
