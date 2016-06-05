<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'MCupic',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'MCupic\MultifileDownloadsHelper' => 'system/modules/downloads_multifile/classes/MultifileDownloadsHelper.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'ce_downloads_multifile' => 'system/modules/downloads_multifile/templates',
));
