<?php

namespace acmeCorp\humhub\modules\controllers;

use Yii;
use acmeCorp\humhub\modules\models\BookForm;
use acmeCorp\humhub\modules\models\SpaceForm;
use humhub\components\Controller;
use humhub\modules\space\models\Space;

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
	private function setOptionsForCurlHandle($url, &$handle)
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
		if (!$this->setOptionsForCurlHandle($url, $handle)) {
			return false;
		}
		$response = json_decode(curl_exec($handle), true);
		curl_close($handle);

		if(array_key_exists('itemListElement', $response)) {
			foreach($response['itemListElement'] as $element) {
				if (!array_key_exists('result', $element)) {
					continue;
				}
				$result = $element['result'];
				if (!array_key_exists('@type', $result) or !in_array("Book", $result['@type'])) {
					continue;
				}
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
		if (!$this->setOptionsForCurlHandle(self::HUMHUB_SERVICE_URL, $handle)) {
			$this->console_log('Cannot set options for curl object to call the Humhub API');
			return;
		}
		require '/var/www/humhub/protected/modules/example-basic/controllers/.rest_api'; 
		curl_setopt($handle, CURLOPT_USERPWD, "admin:$rest_api");
		curl_setopt($handle, CURLOPT_POST, true);
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
		$url = 'https://www.ziotopoulos.space/api/v1/space/'.$space_id.'/membership/'.$user_id;
		$handle = curl_init();
		if (!$this->setOptionsForCurlHandle($url, $handle)) {
			return;
		}

		require '/var/www/humhub/protected/modules/example-basic/controllers/.rest_api';
		curl_setopt($handle, CURLOPT_USERPWD, "admin:$rest_api");
		curl_setopt($handle, CURLOPT_POST, true);

		$response = json_decode(curl_exec($handle), true);
		curl_close($handle);
	}
	
	/** 
	 * Add a user as admin to a space.
	 */
	private function makeUserAnAdmin($space_id, $user_id)
	{
		$url = 'https://www.ziotopoulos.space/api/v1/space/'.$space_id.'/membership/'.$user_id.'/role';
		$handle = curl_init();
		if (!$this->setOptionsForCurlHandle($url, $handle)) {
			return;
		}

		require '/var/www/humhub/protected/modules/example-basic/controllers/.rest_api';
		curl_setopt($handle, CURLOPT_USERPWD, "admin:$rest_api");
		curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PATCH');
		curl_setopt($handle, CURLOPT_POSTFIELDS, array('role' => 'admin'));

		$response = json_decode(curl_exec($handle), true);
		curl_close($handle);
	}
	
	/**
	 * Add tag to space.
	 *
	 * @return
	 */
	private function addTagToSpace($tag, $space_model)
	{
		$handle = curl_init();

		$data = array("tags" => "book");
		$dataEncoded = http_build_query($data);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($handle, CURLOPT_POSTFIELDS, $dataEncoded);
		$username = 'admin';
		require '/var/www/humhub/protected/modules/example-basic/controllers/.rest_api';
		$password = $rest_api;
		curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($handle, CURLOPT_USERPWD, "$username:$password");

		$headers = array(
			"Content-Type: application/x-www-form-urlencoded",
			"Accept: */*",
			"Content-Length: " . strlen($dataEncoded)
		);
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

		$url = self::HUMHUB_SERVICE_URL.'/'.$space_model->id;
		curl_setopt($handle, CURLOPT_URL, $url);

		$response = json_decode(curl_exec($handle), true);
		curl_close($handle);
	}

	/**
	 * Creates a space related to the book.
	 *
	 * @return boolean
	 */
	private function createBookSpace($title, $description)
	{
		$data = array(
			'name' => $title,
			'description' => $description,
			'visibility' => Space::VISIBILITY_REGISTERED_ONLY,
		        'join_policy' => Space::JOIN_POLICY_APPLICATION);
		$response = $this->callRestApi($data);
		
		if(array_key_exists('id', $response)) {
			$space_model = new SpaceForm();
			$space_model->id = $response['id'];
			$space_model->guid = $response['guid'];
			$space_model->name = $response['name'];
			$space_model->description = $response['description'];
			$space_model->url = $response['url'];

			// Add the 'book' tag to the newly created space.
			$this->addTagToSpace('book', $space_model);

			// TODO(aziot) Check if we can create the space directly on behalf of the user.
			$this->addUserToSpace($response['id'], Yii::$app->user->id);
			$this->makeUserAnAdmin($response['id'], Yii::$app->user->id);

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
		$book_model = new BookForm();

		if ($book_model->load(Yii::$app->request->post()) && $book_model->validate()) {
			if ($this->queryKG($book_model)) {
				$space_model = $this->createBookSpace($book_model->title, $book_model->description);
				if ($space_model) {
				       $form = $this->render('space', ['model' => $space_model]);
				       Yii::$app->queue->push(new NotifyFriendsOnNewSpaceJob(Yii::$app->user->id, $space_model->id));
				       return $form;
				} else {
					return $this->render('book-confirm', ['model' => $book_model]);
				}
			} else {
				// TODO(aziot) put a page with an error that the space could not be created.
			}
		} else {
			// either the page is initially displayed or there is some validation error
			return $this->render('book', ['model' => $book_model]);
		}
	}
}

