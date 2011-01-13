<?php
/**
 * @copyright Copyright (c) 2008, Vladimir Sibirov. All rights reserved. Distributed under BSD License.
 */

$db_modlist = 'sed_modlist';
file_exists($cfg['plugins_dir']."/modlist/lang/modlist.$lang.lang.php") ? require_once($cfg['plugins_dir']."/modlist/lang/modlist.$lang.lang.php") : require_once($cfg['plugins_dir'].'/modlist/lang/modlist.en.lang.php');
?>