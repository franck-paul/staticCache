<?php

/**
 * @brief staticCache, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Olivier
 * @author Franck Paul
 *
 * @copyright Olivier Meunier
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Static Cache',
        'Blog pages static cache',
        'Olivier Meunier and contributors',
        '7.0',
        [
            'date'     => '2026-08-03T10:11:47+0200',
            'requires' => [['core', '2.40']],
            'type'     => 'plugin',

            'details'    => 'https://open-time.net/?q=staticCache',
            'support'    => 'https://github.com/franck-paul/staticCache',
            'repository' => 'https://raw.githubusercontent.com/franck-paul/staticCache/main/dcstore.xml',
            'license'    => 'gpl2',
        ]
    );
}
