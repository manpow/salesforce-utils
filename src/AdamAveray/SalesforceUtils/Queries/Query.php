<?php
namespace AdamAveray\SalesforceUtils\Queries;

use Phpforce\SoapClient\ClientInterface;
use Phpforce\SoapClient\Result\QueryResult;
use Phpforce\SoapClient\Result\RecordIterator;

class Query {
    /** @var ClientInterface $client */
    private $client;
    /** @var string $rawQuery The unprocessed SOQL query with parameter placeholders intact */
    private $rawQuery;
    /** @var array $parts */
    private $parts;
    /** @var array $globalArgs Arguments to bind to every execution of the query */
    private $globalArgs;

    /**
     * @param ClientInterface $client
     * @param string $query The parameterised SOQL query
     * @param array|null $globalArgs Global arguments to bind to every execution
     */
    public function __construct(ClientInterface $client, string $query, array $globalArgs = null) {
        $this->client     = $client;
        $this->rawQuery   = $query;
        $this->globalArgs = (array)$globalArgs;
        $this->parts      = $this->parseQuery($query);
    }

    /**
     * @param string $query The parameterised SOQL query
     * @return array Query fragments of either raw strings or arrays matching [{param}, 'quote' => bool] for placeholders
     */
    private function parseQuery(string $query): array {
        $placeholderIndex = 0;

        $parts = [];
        foreach (preg_split('~(::?\w+|\\?)~', $query, -1, \PREG_SPLIT_DELIM_CAPTURE) as $i => $part) {
            if (($i % 2) === 1) {
                // Pattern
                $quote = true;
                if ($part === '?') {
                    $key = $placeholderIndex;
                    $placeholderIndex++;
                } else {
                    $key = substr($part, 1);
                    if ($key[0] === ':') {
                        // Unquoted string
                        $key   = substr($key, 1);
                        $quote = false;
                    }
                }
                $part = [$key, 'quote' => $quote];
            }

            $parts[] = $part;
        }
        return $parts;
    }

    /**
     * @param array|null $args Arguments to bind before executing
     * @return \Phpforce\SoapClient\Result\RecordIterator
     */
    public function query(array $args = null): RecordIterator {
        return $this->client->query($this->build($args));
    }

    /**
     * @param array|null $args Arguments to bind before executing
     * @return \Phpforce\SoapClient\Result\QueryResult[]
     */
    public function queryAll(array $args = null): array {
        // Phpforce library's `->queryAll` method is incorrectly identical to `->query` - manually convert to array
        $iterator = $this->client->query($this->build($args));
        return iterator_to_array($iterator);
    }

    /**
     * @param array|null $args Arguments to bind before executing
     * @return QueryResult|null The first result from the query, or null if no results
     */
    public function queryOne(array $args = null): ?QueryResult {
        $result = $this->query($args);
        if (count($result) === 0) {
            return null;
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $result->current();
    }

    /**
     * @param array|null $args Arguments to bind to the query
     * @return string The complete SOQL query with bound arguments
     * @throws \OutOfBoundsException An expected query parameter was not provided in $args
     */
    private function build(array $args = null): string {
        $args = array_merge($this->globalArgs, (array)$args);

        $out = '';
        foreach ($this->parts as $part) {
            if (is_array($part)) {
                // Parameter
                $key   = $part[0];
                $quote = $part['quote'] ?? true;
                if (!array_key_exists($key, $args)) {
                    throw new \OutOfBoundsException('Undefined query parameter "'.$key.'"');
                }

                $part = $args[$key];
                if (!$part instanceof SafeString) {
                    // Escape value
                    $part = SafeString::escape($part, false, $quote);
                }
            }

            $out .= $part;
        }
        return $out;
    }
}
