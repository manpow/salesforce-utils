<?php
namespace AdamAveray\SalesforceUtils\Client;

use Phpforce\SoapClient\Soap\SoapClientFactory;
use Phpforce\SoapClient\Plugin\LogPlugin;

class ClientBuilder extends \Phpforce\SoapClient\ClientBuilder {
    /**
     * @return Client
     */
    public function build() {
        $soapClientFactory = new SoapClientFactory();
        $soapClient        = $soapClientFactory->factory($this->wsdl, $this->soapOptions);

        $client = new Client($soapClient, $this->username, $this->password, $this->token);

        if ($this->log) {
            $logPlugin = new LogPlugin($this->log);
            $client->getEventDispatcher()->addSubscriber($logPlugin);
        }

        return $client;
    }
}
