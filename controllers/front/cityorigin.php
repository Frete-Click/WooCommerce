<?php
	$fc_city_orign = "https://api.freteclick.com.br/carrier/search-city-origin.json";
    function initContent(){
		global $fc_city_orign;
        $arrRetorno = array();
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fc_city_orign . '?' . http_build_query($_GET));
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($ch);
            curl_close($ch);
            echo filterJson($resp);
            exit;
        } catch (Exception $ex) {
            $arrRetorno = $this->module->addError($ex->getMessage());
            echo json_encode($arrRetorno);
            exit;
        }
    };

    function filterJson($json) {
        $arrJson = json_decode($json);
        if (!$arrJson) {
            fc_error('Erro ao recuperar dados');
        }
        if ($arrJson->response->success === false) {
            if ($arrJson->response->error) {
                foreach ($arrJson->response->error as $error) {
                    fc_error($error->message);
                }
            }
            fc_error('Erro ao recuperar dados');
        }
        return json_encode($arrJson);
    };

	function fc_error($value){
		echo json_encode(array("Erro"=>$value));
		exit;
	};
	
	initContent();