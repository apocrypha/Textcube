<?
Timezone::set(isset($blog['timezone']) ? $blog['timezone'] : $service['timezone']);
mysql_query('SET time_zone = \'' . Timezone::getCanonical() . '\'');
Locale::setDirectory(ROOT . '/language');
Locale::setLocale(isset($blog['language']) ? $blog['language'] : $service['language']);
?>