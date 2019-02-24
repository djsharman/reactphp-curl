<?php

namespace KHR\React\Curl;

use MCurl\Client;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use \React\Promise\Deferred;
use \React\Promise\Promise;

class Curl {

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var TimerInterface
     */
    private $loop_timer;

    /**
     * @var Client
     */
    private $client;

    /**
     * Timeout: check curl resource
     * @var float
     */
    private $timeout = 0.01;

    public function __construct($loop, Client $client=null) {
        $this->loop = $loop;

        if($client == null) {
            $client = new Client();
            $client->isSelect(false);
            $client->setClassResult('\\KHR\\React\Curl\\Result');
        }

        $this->client = $client;
    }

    /**
     * @param $url
     * @param array $opts
     * @return Promise
     */
    public function get($url, $opts = array()) {
        $opts[CURLOPT_URL] = $url;
        $promise = $this->add($opts);
        $this->run();
        return $promise;
    }

    /**
     * @param $url
     * @param array $data
     * @param array $opts
     * @return Promise
     */
    public function post($url, $data = array(), $opts = array()) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $data;
        return $this->get($url, $opts);
    }

    /**
     * @param $opts
     * @param array $params
     * @return Promise
     */
    public function add($opts, $params = []) {
        $params['__deferred'] = $deferred = new Deferred();
        $this->client->add($opts, $params);
        return $deferred->promise();
    }

    public function run() {
        $Client = $this->client;
        $Client->run();

        while($Client->has()) {
            /**
             * @var Result $result
             */
            $result = $Client->next();
            $deferred = $result->shiftDeferred();

            if (!$result->hasError()) {
                $deferred->resolve($result);
            } else {
                $deferred->reject(new Exception($result));
            }
        }

        if (!isset($this->loop_timer)) {
            $this->loop_timer = $this->loop->addPeriodicTimer($this->timeout, function() {
                $this->run();
                if (!($this->client->run() || $this->client->has())) {
                    $this->loop->cancelTimer($this->loop_timer);
                    $this->loop_timer = null;
                }
            });
        }
    }

    /**
     * @return Client
     */
    public function getClient() {
        return $this->client;
    }


}
