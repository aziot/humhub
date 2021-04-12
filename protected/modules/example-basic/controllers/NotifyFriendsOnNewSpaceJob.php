<?php

namespace acmeCorp\humhub\modules\controllers;

use Yii;
use humhub\modules\friendship\models\Friendship;
use humhub\modules\space\models\Space;
use humhub\modules\space\notifications\Invite;
use humhub\modules\user\models\User;

/**
 *  Notification class for a new space
 *
 * @property integer $user_id
 * @property integer $space_id
 */
class NotifyFriendsOnNewSpaceJob extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
	public $user_id = -1;
	public $space_id = -1;

	public function __construct(int $user_id, int $space_id)
	{
		$this->user_id = $user_id;
		$this->space_id = $space_id;
	}

	public function execute($queue)
	{ 
		# $sender = Yii::$app->db->createCommand('SELECT * FROM user WHERE id=5')->queryOne();  # aziot for the demo
		Yii::info('user id : ' . $this->user_id, __METHOD__);
		$sender = User::find()->where(['id' => $this->user_id])->one();
		Yii::info('Sender username : ' . $sender->username, __METHOD__);
		$friends_interested_in_books = Friendship::getFriendsQuery($sender)->andWhere("user.tags LIKE '%book%'")->all();
		$space = Space::find()->where(['id' => $this->space_id])->one();
		Yii::info('Space info: ' . $space->description, __METHOD__);
		Invite::instance()->from($sender)->about($space)->sendBulk($friends_interested_in_books);
		# Yii::getLogger().flush();
	}
}
