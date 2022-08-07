<?php

	$iniFile = "config.ini";
	$iniFileDefault = "default.config.ini";

	if (!file_exists($iniFile) || !is_readable($iniFile)) {
		fwrite(STDERR, 'Not Configured.' . PHP_EOL);
		fwrite(STDERR, 'Rename ' . $iniFileDefault . ' to ' . $iniFile . ' and alter item IDs, base currency, etc. as needed.' . PHP_EOL);
		exit(1);
	}

	$config = parse_ini_file('./' . $iniFile, TRUE);
