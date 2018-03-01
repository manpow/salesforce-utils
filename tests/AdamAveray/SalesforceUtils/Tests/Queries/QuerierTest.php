<?php
namespace AdamAveray\SalesforceUtils\Tests\Queries;

use AdamAveray\SalesforceUtils\Queries\Querier;
use AdamAveray\SalesforceUtils\Queries\Query;
use AdamAveray\SalesforceUtils\Queries\SafeString;
use Phpforce\SoapClient\ClientInterface;
use Phpforce\SoapClient\Result\QueryResult;
use Phpforce\SoapClient\Result\RecordIterator;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \AdamAveray\SalesforceUtils\Queries\Querier
 */
class QuerierTest extends \PHPUnit\Framework\TestCase {
    /**
     * @param ClientInterface|null $client
     * @return Querier
     */
    private function getQuerier(ClientInterface $client = null): Querier {
        return new Querier($client ?? $this->getClient());
    }

    /**
     * @param array|null $methods
     * @return MockObject|ClientInterface
     */
    private function getClient(array $methods = null): MockObject {
        $builder = $this->getMockBuilder(ClientInterface::class);
        if ($methods !== null) {
            $builder->setMethods($methods);
        }
        return $builder->getMockForAbstractClass();
    }

    /**
     * @covers ::prepare
     * @covers ::<!public>
     */
    public function testPrepare() {
        $query  = 'TEST QUERY';
        $args   = ['a', 'b', 'c'];
        $client = $this->getClient();
        $expected = new Query($client, $query, $args);

        $querier = $this->getQuerier($client);
        $result  = $querier->prepare($query, $args);

        $this->assertEquals($expected, $result, 'A complete Query object should be generated');
    }

    /**
     * @covers ::__construct
     * @covers ::query
     * @covers ::queryAll
     * @covers ::queryOne
     * @covers ::<!public>
     * @dataProvider queriesDataProvider
     */
    public function testQueries(string $method, $expected) {
        $args    = ['a', 'b', 'c'];
        $client  = $this->getClient();
        $querier = $this->getQuerier($client);

        $query = $this
            ->getMockBuilder(Query::class)
            ->setMethods([$method])
            ->disableOriginalConstructor()
            ->getMock();
        $query
            ->expects($this->exactly(2)) // Called for both tests
            ->method($method)
            ->with($args)
            ->willReturn($expected);

        // Prebuilt query
        $result = $querier->{$method}($query, $args);
        $this->assertEquals($expected, $result, 'The Query method "'.$method.'" result should be passed through');

        // Raw query string
        $string  = 'TEST SOQL';
        /** @var Querier|MockObject $querier */
        $querier = $this
            ->getMockBuilder(Querier::class)
            ->setMethods(['prepare'])
            ->setConstructorArgs([$client])
            ->getMock();
        $querier
            ->expects($this->once())
            ->method('prepare')
            ->with($string)
            ->willReturn($query);

        $result = $querier->{$method}($string, $args);
        $this->assertEquals($expected, $result, 'The Query method "'.$method.'" result should be passed through');
    }

    public function queriesDataProvider(): array {
        return [
            'Method `query`'    => [
                'query',
                $this->getMockBuilder(RecordIterator::class)->disableOriginalConstructor()->getMock(),
            ],
            'Method `queryAll`' => [
                'queryAll',
                ['a', 'b', 'c'],
            ],
            'Method `queryOne`' => [
                'queryOne',
                $this->getMockBuilder(QueryResult::class)->disableOriginalConstructor()->getMock(),
            ],
        ];
    }

    /**
     * @covers ::escape
     * @covers ::<!public>
     * @dataProvider escapeDataProvider
     */
    public function testEscape(string $input, $isLike = null, $quote = null) {
        $isLike = $isLike ?? false;
        $quote  = $quote ?? true;

        $expected = SafeString::escape($input, $isLike, $quote);

        $querier = $this->getQuerier();
        $result  = $querier->escape($input, $isLike, $quote);

        $this->assertEquals($expected, $result, 'A complete Query object should be generated');
    }

    public function escapeDataProvider():array {
        return [
            'Plain' => [
                '""test value""',
            ],
            'Like' => [
                '""test value""',
                true,
            ],
            'No-Quote' => [
                '""test value""',
                false,
                true,
            ],
        ];
    }
}
