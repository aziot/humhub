<?php

namespace acmeCorp\humhub\modules\controllers;

use Yii;
use acmeCorp\humhub\modules\models\BookForm;
use humhub\components\Controller;

class IndexController extends Controller
{

    /**
     * Renders the index view for the module
     *
     * @return string
     */
    public function actionIndex()
    {
	    $model = new BookForm();

	    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
		    // query the knowledge graph for information on this book.
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
				    break;
			    }
		    }

		    return $this->render('book-confirm', ['model' => $model]);
	    } else {
		    // either the page is initially displayed or there is some validation error
		    return $this->render('book', ['model' => $model]);
	    }
    }

}

