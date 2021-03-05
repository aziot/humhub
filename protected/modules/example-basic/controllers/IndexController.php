<?php

namespace acmeCorp\humhub\modules\controllers;

use Yii;
use acmeCorp\humhub\modules\models\BookForm;
use acmeCorp\humhub\modules\models\SpaceForm;
use humhub\components\Controller;

class IndexController extends Controller
{
	private function console_log($output, $with_script_tags = true) {
		$js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . 
			');';
		if ($with_script_tags) {
			$js_code = '<script>' . $js_code . '</script>';
		}
		echo $js_code;
	}
	
	const HUMHUB_SERVICE_URL = 'https://www.ziotopoulos.space/api/v1/space';
	const KG_SERVICE_URL = 'https://kgsearch.googleapis.com/v1/entities:search';
	
	/**
	 * Sets the options for a curl object to be used for querying the API.
	 *
	 * @return boolean
	 */
	private function setOptionsForCurlObject($url, &$handle)
	{
		return curl_setopt_array($handle, array(CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC));
	}
	
	/**
	 * Form the URL to query for the Knowledge Graph.
	 *
	 * @return string
	 */
	private function getUrlToQueryKG($title)
	{
		require '/var/www/humhub/protected/modules/example-basic/controllers/.api_key';
		$params = array(
			'query' => $title, 
			'limit' => 10, 
			'indent' => TRUE, 
			'key' => $api_key);
		return self::KG_SERVICE_URL . '?' . http_build_query($params);
	}
	
	/**
	 * Query the knowledge graph for information on this book.
	 * Return true if the query is successful, else false.
	 *
	 * @return boolean
	 */
	private function queryKG(&$model)
	{
		$url = $this->getUrlToQueryKG($model->title);
		$handle = curl_init();
		if (!$this->setOptionsForCurlObject($url, $handle)) {
			return false;
		}
		$response = json_decode(curl_exec($handle), true);
		curl_close($handle);

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
	private function callRestApi($data)
	{
		$handle = curl_init();
		if (!$this->setOptionsForCurlObject(self::HUMHUB_SERVICE_URL, $handle)) {
			$this->console_log('Cannot set options for curl object to call the Humhub API');
			return;
		}
		require '/var/www/humhub/protected/modules/example-basic/controllers/.rest_api'; 
		curl_setopt($handle, CURLOPT_USERPWD, "admin:$rest_api");
		curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
		$this->console_log('Humhub query: '.http_build_query($data));
		$response = json_decode(curl_exec($handle), true);
		curl_close($handle);

		return $response;
	}
	
	/**
	 * Adds a user to a space.
	 *
	 * @return
	 */
	private function addUserToSpace($space_id, $user_id)
	{
		$data = array(
			'id' => $space_id,
			'userId' => $user_id);
		$response = $this->callRestApi($data);
	}

	/**
	 * Creates a space related to the book.
	 *
	 * @return boolean
	 */
	private function createBookSpace($title)
	{
		$data = array(
			'name' => $title,
			'visibility' => 1,
		        'join_policy' => 1);
		$response = $this->callRestApi($data);
		
		if(array_key_exists('id', $response)) {
			$space_model = new SpaceForm();
			$space_model->id = $response['id'];
			$space_model->guid = $response['guid'];
			$space_model->name = $response['name'];
			$space_model->description = $response['description'];
			$space_model->url = $response['url'];

			// Add the current user to the newly created space
			// TODO(aziot) Check if we can create the space directly on behalf of the user.
			$this->addUserToSpace($response['id'], Yii::$app->user->identity->getId());

			return $space_model;
		} else {
			$this->console_log('Response missing id');
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
				$space_model = $this->createBookSpace($model->title);
				if ($space_model) {
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

