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
$this->registerModule(
    'Static Cache',
    'Blog pages static cache',
    'Olivier Meunier and contributors',
    '5.1',
    [
        'date'     => '2003-08-13T13:42:00+0100',
        'requires' => [['core', '2.36']],
        'type'     => 'plugin',

        'details'    => 'https://open-time.net/?q=staticCache',
        'support'    => 'https://github.com/franck-paul/staticCache',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/staticCache/main/dcstore.xml',
        'license'    => 'gpl2',
    ]
);
