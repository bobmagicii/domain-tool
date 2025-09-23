#! env php
<?php ##########################################################################
################################################################################

require(sprintf(
	'%s/vendor/autoload.php',
	dirname(__FILE__, 2)
));

$AppRoot = match(TRUE) {
	(Phar::Running() !== '')
	=> dirname(Phar::Running(FALSE), 1),

	default
	=> dirname(__FILE__, 2)
};

$Config = Nether\Common\Datastore::FromArray([]);

$Library = Nether\Common\Datastore::FromArray([
	'Common'   => new Nether\Common\Library($Config),
	'Database' => new Nether\Database\Library($Config)
]);

################################################################################
################################################################################

exit(Local\App::Realboot([
	'AppRoot' => $AppRoot
]));
