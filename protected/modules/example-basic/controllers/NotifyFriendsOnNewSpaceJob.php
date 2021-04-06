<?php

namespace acmeCorp\humhub\modules\controllers;

use Yii;
use acmeCorp\humhub\modules\notifications\CustomSpaceCreated;
use humhub\modules\space\models\Space;
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
		Yii::error('user id : ' . $this->user_id, __METHOD__);
		$sender = User::find()->where(['id' => $this->user_id])->one();
		Yii::error('Sender username : ' . $sender->username, __METHOD__);
		# $receiver = Yii::$app->db->createCommand('SELECT * FROM user WHERE id=2')->queryOne();  # test user
		$receiver = User::find()->where(['id' => 2])->one();  # test user
		Yii::error('Receiver username: ' . $receiver->username, __METHOD__);
		# $space = Yii::$app->db->createCommand('SELECT * FROM space WHERE id=:id')->bindValue(':id', $this->space_id)->queryOne();
		$space = Space::find()->where(['id' => $this->space_id])->one();
		Yii::error('Space info: ' . $space->description, __METHOD__);
		# Yii::getLogger().flush();
		CustomSpaceCreated::instance()->from($sender)->about($space)->send($receiver);
                Yii::$app->queue->push(new SendNotification(['notification' => $CustomSpaceCreated::instance(), 'recipientId' => $receiver->id]));
	}
}
