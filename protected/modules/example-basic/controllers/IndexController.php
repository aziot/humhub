<?php

namespace acmeCorp\humhub\modules\controllers;

use Yii;
use acmeCorp\humhub\modules\models\BookForm;
use acmeCorp\humhub\modules\models\SpaceForm;
use humhub\components\Controller;

class IndexController extends Controller
{
	/**
	 * Query the knowledge graph for information on this book.
	 * Return true if the query is successful, else false.
	 *
	 * @return boolean
	 */
	public function queryKG(&$model)
	{
		require '/var/www/humhub/protected/modules/example-basic/controllers/.api_key';
		$service_url = 'https://kgsearch.googleapis.com/v1/entities:search';
		$params = array(
			'query' => $model->title, 
			'limit' => 10, 
			'indent' => TRUE, 
			'key' => $api_key);
		$url = $service_url . '?' . http_build_query($params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
	 * Creates a space related to the book.
	 *
	 * @return string
	 */
	public function createBookSpace($model, &$space_model)
	{
		$service_url = 'https://www.ziotopoulos.space/api/v1/space';
		$params = array(
			'foo' => 'bar');
		$url = $service_url . '?' . http_build_query($params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, "admin:Q]Gc=5gffjS.a9UX");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$response = json_decode(curl_exec($ch), true);
		curl_close($ch);
		
		if(array_key_exists('total', $response)) {
			$space_model->total = $response['total'];
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
				if ($this->createBookSpace($model, $space_model)) {
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

