<?php
namespace AdamAveray\SalesforceUtils\Queries;

use Phpforce\SoapClient\ClientInterface;
use Phpforce\SoapClient\Result\QueryResult;
use Phpforce\SoapClient\Result\RecordIterator;

class Querier {
    /** @var ClientInterface $client */
    private $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client) {
        $this->client = $client;
    }

    /**
     * Builds a Query object suitable for calling multiple times with different arguments
     *
     * @param string $query The parameterised SOQL query
     * @param array|null $globalArgs Arguments to bind to every execution of the query
     * @return Query
     */
    public function prepare(string $query, array $globalArgs = null): Query {
        return new Query($this->client, $query, $globalArgs);
    }

    /**
     * @param string|Query $query The parameterised SOQL query, or a pre-prepared Query object
     * @param array|null $args Arguments to bind to the query
     * @return \Phpforce\SoapClient\Result\RecordIterator
     */
    public function query($query, array $args = null): RecordIterator {
        if (!$query instanceof Query) {
            $query = $this->prepare($query);
        }
        return $query->query($args);
    }

    /**
     * @param string|Query $query The parameterised SOQL query, or a pre-prepared Query object
     * @param array|null $args Arguments to bind to the query
     * @return \Phpforce\SoapClient\Result\QueryResult[]
     */
    public function queryAll($query, array $args = null): array {
        if (!$query instanceof Query) {
            $query = $this->prepare($query);
        }
        return $query->queryAll($args);
    }

    /**
     * @param string|Query $query The parameterised SOQL query, or a pre-prepared Query object
     * @param array|null $args Arguments to bind to the query
     * @return \Phpforce\SoapClient\Result\QueryResult|null
     */
    public function queryOne($query, array $args = null): ?QueryResult {
        if (!$query instanceof Query) {
            $query = $this->prepare($query);
        }
        return $query->queryOne($args);
    }

    /**
     * @param mixed $value The value to escape
     * @param bool $isLike Whether the value is for a LIKE comparison
     * @param bool $quote Whether to quote the value
     * @return SafeString
     */
    public function escape($value, $isLike = false, $quote = true): SafeString {
        if (!$value instanceof SafeString) {
            $value = SafeString::escape($value, $isLike, $quote);
        }
        return $value;
    }
}
