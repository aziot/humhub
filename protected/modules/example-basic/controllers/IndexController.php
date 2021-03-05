<?php

namespace acmeCorp\humhub\modules\controllers;

use Yii;
use acmeCorp\humhub\modules\models\BookForm;
use acmeCorp\humhub\modules\models\SpaceForm;
use humhub\components\Controller;

class IndexController extends Controller
{
	
	const HUMHUB_SERVICE_URL = 'https://www.ziotopoulos.space/api/v1/space';
	
	/**
	 * Sets the options for a curl object to be used for querying the API.
	 *
	 * @return boolean
	 */
	public function setOptionsForCurlObject($url, &$ch)
	{
		if (curl_setopt_array($ch, array(CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC)) ) {
			return true;
		}

		return false;
	}
	
	/**
	 * Form the URL to query for the Knowledge Graph.
	 *
	 * @return string
	 */
	public function getUrlToQueryKG($title)
	{
		require '/var/www/humhub/protected/modules/example-basic/controllers/.api_key';
		$params = array(
			'query' => $title, 
			'limit' => 10, 
			'indent' => TRUE, 
			'key' => $api_key);
		return self::HUMHUB_SERVICE_URL . '?' . http_build_query($params);
	}
	
	/**
	 * Query the knowledge graph for information on this book.
	 * Return true if the query is successful, else false.
	 *
	 * @return boolean
	 */
	public function queryKG(&$model)
	{
		$url = $this->getUrlToQueryKG($model->title);
		$ch = curl_init();
		if (!$this->setOptionsForCurlObject($url, $ch)) {
			return false;
		}
		$response = json_decode(curl_exec($ch), true);
		curl_close($ch);

		if(array_key_exists('itemListElement', $response)) {
			foreach($response['itemListElement'] as $element) {
				$result = $element['result'];
				if (array_key_exists('name', $result)) {
					$model->name = $result['name'];
				}
				if (array_key_exists('description', $result)) {
					$model->description = $result['description'];
				}
				if (array_key_exists('url', $result)) {
					$model->url = $result['url'];
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Call the HUMHUB REST API.
	 *
	 * @return 
	 */
	public function callRestApi($data, &$ch)
	{
		if (!$this->setOptionsForCurlObject(self::HUMHUB_SERVICE_URL, $ch)) {
			return;
		}
		require '/var/www/humhub/protected/modules/example-basic/controllers/.rest_api'; 
		curl_setopt($ch, CURLOPT_USERPWD, "admin:$rest_api");
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$response = json_decode(curl_exec($ch), true);
		curl_close($ch);
	}
	
	/**
	 * Adds a user to a space.
	 *
	 * @return
	 */
	public function addUserToSpace($space_id, $user_id, &$ch)
	{
		$data = array(
			'id' => $space_id,
			'userId' => $user_id);
		$this->callRestApi($data, $ch);
	}

	/**
	 * Creates a space related to the book.
	 *
	 * @return boolean
	 */
	public function createBookSpace($title, &$space_model)
	{
		$ch = curl_init();
		$data = array(
			'name' => $title,
			'visibility' => 1,
		        'join_policy' => 1);
		$this->callRestApi($data, $ch);
		
		if(array_key_exists('id', $response)) {
			$space_model->id = $response['id'];
			$space_model->guid = $response['guid'];
			$space_model->name = $response['name'];
			$space_model->description = $response['description'];
			$space_model->url = $response['url'];

			// Add the current user to the newly created space
		        // TODO(aziot) Check if we can create the space directly on behalf of the user.
			curl_reset($ch);
	                $this->addUserToSpace($space_model->id, Yii::$app->user->identity->getId(), $ch);

			return true;
		}
		return false;
	}
	
	/**
	 * Renders the index view for the module
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		$model = new BookForm();

		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			if ($this->queryKG($model)) {
				$space_model = new SpaceForm();
				if ($this->createBookSpace($model->title, $space_model)) {
					return $this->render('space', ['model' => $space_model]);
				} else {
					return $this->render('book-confirm', ['model' => $model]);
				}
			} else {
				// TODO(aziot) put a page with an error that the space could not be created.
				;
			}
		} else {
			// either the page is initially displayed or there is some validation error
			return $this->render('book', ['model' => $model]);
		}
	}
}

