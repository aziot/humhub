<?php
/**
 * This file provides to overwrite the default HumHub / Yii configuration by your local common (Console and Web) environments
 * @see http://www.yiiframework.com/doc-2.0/guide-concept-configurations.html
 * @see http://docs.humhub.org/admin-installation-configuration.html
 * @see http://docs.humhub.org/dev-environment.html
 */
return [
	'bootstrap' => [
		'queue', // The component registers its own console commands
	],
	'components' => [
		'urlManager' => [
			'showScriptName' => false,
			'enablePrettyUrl' => true,
		],
		'redis' => [
			'class' => 'yii\redis\Connection',
			'hostname' => 'localhost',
			'port' => 6379,
			'database' => 0,
			'retries' => 1,
		],
		'queue' => [
			'class' => 'humhub\modules\queue\driver\Redis',
			// Other driver options
			'redis' => 'redis', // Redis connection component or its config
			'channel' => 'queue', // Queue channel key
		],
	]
];
