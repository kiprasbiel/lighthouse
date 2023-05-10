<?php declare(strict_types=1);

namespace Tests\Unit\Schema\Directives;

use Tests\TestCase;

class HideDirectiveTest extends TestCase
{
    public function testHiddenOnTestingEnv(): void
    {
        $this->schema = /** @lang GraphQL */ '
        type Query {
            shownField: String! @mock
            hiddenField: String! @mock @hide(env:["testing"])
        }
        ';

        $introspectionQuery = /** @lang GraphQL */ '
        query  {
            __schema {
                queryType {
                    fields {
                        name
                    }
                }
            }
        }
        ';

        $this->graphQL($introspectionQuery)
            ->assertJsonCount(1, 'data.__schema.queryType.fields')
            ->assertJsonPath('data.__schema.queryType.fields.0.name', 'shownField');

        $query = /** @lang GraphQL */ '
        query  {
            hiddenField
        }
        ';

        $this->graphQL($query)->assertGraphQLErrorMessage('Cannot query field "hiddenField" on type "Query". Did you mean "shownField"?');
    }

    public function testHiddenWhenManuallySettingEnv(): void
    {
        $this->schema = /** @lang GraphQL */ '
        type Query {
            hiddenField: String! @mock @hide(env:["prod"])
        }
        ';

        $introspectionQuery = /** @lang GraphQL */ '
        query  {
            __schema {
                queryType {
                    fields {
                        name
                    }
                }
            }
        }
        ';

        app()->instance('env', 'prod');
        $this->graphQL($introspectionQuery)
            ->assertJsonCount(0, 'data.__schema.queryType.fields');
    }

    public function testShownOnAnotherEnv(): void
    {
        $this->schema = /** @lang GraphQL */ '
        type Query {
            hiddenField: String! @mock @hide(env:["prod"])
        }
        ';

        $introspectionQuery = /** @lang GraphQL */ '
        query  {
            __schema {
                queryType {
                    fields {
                        name
                    }
                }
            }
        }
        ';

        $this->graphQL($introspectionQuery)
            ->assertJsonCount(1, 'data.__schema.queryType.fields')
            ->assertJsonPath('data.__schema.queryType.fields.0.name', 'hiddenField');
    }
}
