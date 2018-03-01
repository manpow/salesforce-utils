<?php
namespace AdamAveray\SalesforceUtils;

use AdamAveray\SalesforceUtils\Exceptions\SaveFailureException;
use Phpforce\SoapClient\ClientInterface;
use Phpforce\SoapClient\Result\SaveResult;
use Phpforce\SoapClient\Result\SObject;

class Writer {
    /** @var ClientInterface $client */
    private $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client) {
        $this->client = $client;
    }

    /**
     * @param string $type The type of object to create
     * @param array|SObject $values The values for the object, or the object itself
     * @return SaveResult
     * @throws SaveFailureException The create was unsuccessful
     */
    public function create(string $type, $values): SaveResult {
        if ($values instanceof SObject) {
            $object = $values;
        } else {
            $object = $this->buildSObject(null, $values);
        }

        $result = $this->client->create([$object], $type)[0];
        return $this->handleResult($result);
    }

    /**
     * @param string $type The type of object to update
     * @param SObject|string $base The base object or object ID
     * @param array $updates The values to update
     * @return SaveResult
     * @throws SaveFailureException The update was unsuccessful
     */
    public function update(string $type, $base, array $updates): SaveResult {
        if ($base instanceof SObject) {
            $baseId = $base->getId();
        } else {
            $baseId = (string)$base;
        }

        $object = $this->buildSObject($baseId, $updates);
        $result = $this->client->update([$object], $type)[0];
        return $this->handleResult($result);
    }

    /**
     * @param string $id The ID for the object
     * @param array $values Additional values to assign to the object
     * @return SObject
     */
    public function buildSObject(?string $id, array $values): SObject {
        $object = new SObject();
        if ($id !== null) {
            $object->Id = $id;
        }
        foreach ($values as $key => $value) {
            if (is_object($value)) {
                $value = (string)$value;
            }
            $object->{$key} = $value;
        }
        return $object;
    }

    /**
     * @param SaveResult $result The result to handle
     * @return SaveResult
     * @throws SaveFailureException The given $result was not successful
     */
    private function handleResult(SaveResult $result): SaveResult {
        if (!$result->isSuccess()) {
            throw new SaveFailureException($result);
        }
        return $result;
    }
}
