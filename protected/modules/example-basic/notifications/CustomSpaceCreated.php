<?php

namespace acmeCorp\humhub\modules\notifications;

use humhub\modules\notification\components\BaseNotification;

/**
 * Notifies a user about the creation of a custom space.
 */
class CustomSpaceCreated extends BaseNotification
{
	// Module Id (required)
	public $moduleId = "example-basic";
	// Viewname (required)
	public $viewName = "custom-space-created";
}
