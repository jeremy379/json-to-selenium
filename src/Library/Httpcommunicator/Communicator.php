<?php

namespace App\Library\Jeremy379\JsonToSelenium\HttpCommunicator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

/**
 * Implement Guzzle stuff to send request to API
 * Class Communicator
 * @package App\Libraries\Famous\HttpCommunicator
 */
class Communicator
{
    protected $_client = null;
    protected $_response = null;
    protected $_path = null;
    protected $_data = [];
    protected $_json = false;
    protected $_files = false;
    protected $_base_uri = 'api';
    protected $_hasError = false;
    protected $_errors;

    /**
     * Communicator constructor.
     * @param string $baseUri
     * @param array $headers
     */
    public function __construct($baseUri='', array $headers = [])
    {
        $this->_base_uri = $baseUri;
        $this->buildClient( $headers );
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    protected function buildClient(array $headers = []) {
        $this->_client = new Client([
            'base_uri'  =>  $this->_base_uri,
            'headers'      => $headers
        ]);
        return $this;
    }

    /**
     * @param $path (relative path only, not host part)
     * @param array $data
     * @return Psr7\Response
     */
    public function get( $path, array $data = []) {
        $this->_path = $path;
        $this->_data = $data;
        return $this->call('GET');
    }

    /**
     * @param string $path (relative path only, not host part)
     * @param array $data ['key' => 'value']
     * @return bool|\Psr\Http\Message\ResponseInterface
     */
    public function post( $path, array $data = []) {
        $this->_path = $path;
        $this->_data = $data;
        return $this->call('POST');
    }

    /**
     * @param $path
     * @param array $data
     * @return Psr7\Response
     */
    public function put( $path, array $data = []) {
        $this->_path = $path;
        $this->_data = $data;
        return $this->call('PUT');
    }

    /**
     * @param $path
     * @param array $data
     * @return Psr7\Response
     */
    public function delete( $path, array $data = []) {
        $this->_path = $path;
        $this->_data = $data;
        return $this->call('DELETE');
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function useJson( $value = true) {
        $this->_json = $value;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function useFiles( $value = true) {
        $this->_files = $value;
        return $this;
    }

    /**
     * @param $method
     * @return bool|Psr7\Response
     */
    protected function call( $method) {

        $requestData = $this->getRequestData($method);

        try {
            $this->_response = $this->_client->request($method, $this->_path, $requestData);
        } catch (ClientException $e) {
            $this->setError($e);
            $this->_hasError = true;
        } catch (RequestException $e) {
            $this->setError($e);
            $this->_hasError = true;
        }

        if(!$this->_hasError) {
            $response = $this->_response;
            $this->reset();
            return $response;
        } else {
            return false;
        }
    }

    protected function setError($e) {
        $this->_errors = $e;
    }

    public function getError($echoError = null) {
        if(is_null($echoError))
            $echoError = env('APP_DEBUG');


        if($this->_errors instanceof ClientException) {
            if($echoError) {
                echo Psr7\str($this->_errors->getRequest());
                echo Psr7\str($this->_errors->getResponse());
            }

            \Log::error(Psr7\str($this->_errors->getRequest()));
            \Log::error(Psr7\str($this->_errors->getResponse()));
        }
        if($this->_errors instanceof  RequestException) {
            if($echoError) {
                echo Psr7\str($this->_errors->getRequest());
                if ($this->_errors->hasResponse()) {
                    echo Psr7\str($this->_errors->getResponse());
                }
            }

            \Log::error(Psr7\str($this->_errors->getRequest()));
            if ($this->_errors->hasResponse()) {
                \Log::error(Psr7\str($this->_errors->getResponse()));
            }
        }
    }
    /**
     * @param $method
     * @return array
     */
    protected function getRequestData( $method) {

        if($method == 'GET') {
            $term = 'query';
            $data = $this->_data;
        } else {
            if($this->_json) {
                $term = 'json';
                $data = empty($this->_data) ? null : $this->_data;
            } elseif($this->_files) {
                $term = 'multipart';

                $data = [];

                foreach($this->_data as $key=>$value) {
                    $data[] = [
                        'name'      => $key,
                        'content'   => $value
                    ];
                }
            } else {
                $term = 'form_params';
                $data = $this->_data;
            }
        }

        return [
            $term => $data
        ];
    }

    /**
     * Reset the library to be reused as it
     */
    protected function reset() {
        $this->_response = null;
        $this->_path = null;
        $this->_data = [];
        $this->_json = false;
        $this->_files = false;
    }
}