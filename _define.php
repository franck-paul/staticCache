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

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Static Cache",                     // Name
    "Blog pages static cache",          // Description
    "Olivier Meunier and contributors", // Author
    '1.0',                              // Version
    [
        'requires' => [['core', '2.13']], // Dependencies
        'type'     => 'plugin'           // Type
    ]
);
