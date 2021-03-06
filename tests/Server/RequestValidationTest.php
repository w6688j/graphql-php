<?php
namespace GraphQL\Tests\Server;

use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use PHPUnit\Framework\TestCase;

class RequestValidationTest extends TestCase
{
    public function testSimpleRequestShouldValidate()
    {
        $query = '{my q}';
        $variables = ['a' => 'b', 'c' => 'd'];
        $operation = 'op';

        $parsedBody = OperationParams::create([
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operation,
        ]);

        $this->assertValid($parsedBody);
    }

    public function testRequestWithQueryIdShouldValidate()
    {
        $queryId = 'some-query-id';
        $variables = ['a' => 'b', 'c' => 'd'];
        $operation = 'op';

        $parsedBody = OperationParams::create([
            'queryId' => $queryId,
            'variables' => $variables,
            'operationName' => $operation,
        ]);

        $this->assertValid($parsedBody);
    }

    public function testRequiresQueryOrQueryId()
    {
        $parsedBody = OperationParams::create([
            'variables' => ['foo' => 'bar'],
            'operationName' => 'op',
        ]);

        $this->assertInputError(
            $parsedBody,
            'GraphQL Request must include at least one of those two parameters: "query" or "queryId"'
        );
    }

    public function testFailsWhenBothQueryAndQueryIdArePresent()
    {
        $parsedBody = OperationParams::create([
            'query' => '{my query}',
            'queryId' => 'my-query-id',
        ]);

        $this->assertInputError(
            $parsedBody,
            'GraphQL Request parameters "query" and "queryId" are mutually exclusive'
        );
    }

    public function testFailsWhenQueryParameterIsNotString()
    {
        $parsedBody = OperationParams::create([
            'query' => ['t' => '{my query}']
        ]);

        $this->assertInputError(
            $parsedBody,
            'GraphQL Request parameter "query" must be string, but got {"t":"{my query}"}'
        );
    }

    public function testFailsWhenQueryIdParameterIsNotString()
    {
        $parsedBody = OperationParams::create([
            'queryId' => ['t' => '{my query}']
        ]);

        $this->assertInputError(
            $parsedBody,
            'GraphQL Request parameter "queryId" must be string, but got {"t":"{my query}"}'
        );
    }

    public function testFailsWhenOperationParameterIsNotString()
    {
        $parsedBody = OperationParams::create([
            'query' => '{my query}',
            'operationName' => []
        ]);

        $this->assertInputError(
            $parsedBody,
            'GraphQL Request parameter "operation" must be string, but got []'
        );
    }

    /**
     * @see https://github.com/webonyx/graphql-php/issues/156
     */
    public function testIgnoresNullAndEmptyStringVariables()
    {
        $query = '{my q}';
        $parsedBody = OperationParams::create([
            'query' => $query,
            'variables' => null
        ]);
        $this->assertValid($parsedBody);

        $variables = "";
        $parsedBody = OperationParams::create([
            'query' => $query,
            'variables' => $variables
        ]);
        $this->assertValid($parsedBody);
    }

    public function testFailsWhenVariablesParameterIsNotObject()
    {
        $parsedBody = OperationParams::create([
            'query' => '{my query}',
            'variables' => 0
        ]);

        $this->assertInputError(
            $parsedBody,
            'GraphQL Request parameter "variables" must be object or JSON string parsed to object, but got 0'
        );
    }

    private function assertValid($parsedRequest)
    {
        $helper = new Helper();
        $errors = $helper->validateOperationParams($parsedRequest);
        $this->assertEmpty($errors, isset($errors[0]) ? $errors[0]->getMessage() : '');
    }

    private function assertInputError($parsedRequest, $expectedMessage)
    {
        $helper = new Helper();
        $errors = $helper->validateOperationParams($parsedRequest);
        if (!empty($errors[0])) {
            $this->assertEquals($expectedMessage, $errors[0]->getMessage());
        } else {
            $this->fail('Expected error not returned');
        }
    }
}
