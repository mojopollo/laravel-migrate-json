<?php
namespace Tests;

use Mojopollo\Schema\MakeMigrationJson;
use Illuminate\Filesystem\Filesystem;

class MakeMigrationJsonTest extends \PHPUnit_Framework_TestCase
{
    /**
    * MakeMigrationJson instance
    *
    * @var MakeMigrationJson
    */
    protected $makeMigrationJson;

    /**
    * The path to the json file
    *
    * @var string
    */
    protected $jsonFilePath;

    /**
    * This will run at the beginning of every test method
    */
    public function setUp()
    {
        // Parent setup
        parent::SetUp();

        // Set MakeMigrationJson instance
        $this->makeMigrationJson = new MakeMigrationJson;

        // Set json file path
        $this->jsonFilePath = 'tests/json/test.json';
    }

    /**
    * This will run at the end of every test method
    */
    public function tearDown()
    {
        // Parent teardown
        parent::tearDown();

        // Unset Arr class
        $this->makeMigrationJson = null;
    }

    /**
    * Test jsonFileToArray()
    *
    * @return array $jsonArray
    */
    public function testJsonFileToArray()
    {
        // Execute method
        $jsonArray = $this->makeMigrationJson->jsonFileToArray($this->jsonFilePath);

        // Make sure contents are of type array
        $this->assertTrue(is_array($jsonArray), 'json file contents do not return an array');

        // Return json array for more testing
        return $jsonArray;
    }

    /**
    * Test parseSchema()
    *
    * @depends testJsonFileToArray
    * @return void
    */
    public function testParseSchema(array $jsonArray)
    {
        // Execute method
        $results = $this->makeMigrationJson->parseSchema($jsonArray);

        // Make sure we "users" got turned into "create_users_table" and has values
        $this->assertFalse(empty($results['create_users_table']), '"users" was not converted to "create_users_table"');

        // Make sure "remove_city_from_users_table" has been left intact
        $this->assertTrue(
            isset($results['remove_city_from_users_table']),
            '"remove_city_from_users_table" should be in the json array but it is not set'
        );

        // Make sure our pivot test schema definition got correctly set
        $this->assertTrue(isset($results['posts_tags_pivot']), 'migration "posts_tags_pivot" is missing');

        // Make sure our pivot test schema definition has the table names properly parsed out
        $this->assertEquals($results['posts_tags_pivot'], 'posts tags');
    }

    /**
    * Test parseSchema() with $only parameter
    *
    * @return void
    */
    public function testParseSchemaWithOnlyParameter()
    {
        // Set json array
        $jsonArray = [
            'dogs' => [
                'field1' => 'schema1',
            ],
            'cats' => [
                'field1' => 'schema1',
            ],
            'birds' => [
                'field1' => 'schema1',
            ],
        ];

        // Set only array
        $only = [
            'cats',
            'birds'
        ];

        // Execute method
        $results = $this->makeMigrationJson->parseSchema($jsonArray, $only);

        // Expected results
        $expected = [
            'create_cats_table' => 'field1:schema1',
            'create_birds_table' => 'field1:schema1',
        ];

        // We should only get back cats and birds
        $this->assertEquals($results, $expected);
    }

    /**
    * Test setMigrationName()
    *
    * @return void
    */
    public function testSetMigrationName()
    {
        // Make sure table names are converted into their proper migration name
        $tableName = 'users';
        $this->assertEquals($this->makeMigrationJson->setMigrationName($tableName), "create_{$tableName}_table");

        // Make sure migration names are not converted
        $tableName = 'remove_city_from_users_table';
        $this->assertEquals($this->makeMigrationJson->setMigrationName($tableName), $tableName);
    }

    /**
    * Test isValidColumnType()
    *
    * @return void
    */
    public function testIsValidColumnType()
    {
        // The following column type should fail
        $types = [
            'purpleRain',
            'masterBlaster',
        ];
        foreach ($types as $type) {
            $this->assertFalse(
                $this->makeMigrationJson->isValidColumnType($type),
                "'{$type}' should not be a valid column type"
            );
        }

        // The following column types should pass
        $types = [
            'string',
            'integer',
            'bigInteger',
            'morphs',
            'mediumText',
            'timestamp',
        ];
        foreach ($types as $type) {
            $this->assertTrue(
                $this->makeMigrationJson->isValidColumnType($type),
                "'{$type}' should be a valid column type"
            );
        }
    }

    /**
    * Test validateSchema()
    *
    * @return void
    */
    public function testValidateSchema()
    {
        // Set the json array schema
        $schemaArray = [
            'dogs' => [
                'name' => 'string:unique',
                'paws' => 'yesTheyHaveThemSometimes:index',
                'canines' => 'boolean',
                'hair' => 'string(50):index',
                'ears_invalid' => 'string:thisIsMyInvalidModifier',
                'ears_valid' => "string:after('id')",
            ],
            'create_cats_table' => [
                'hair' => 'boolean',
            ],
            'posts_tags_pivot' => null,
        ];

        // Validate schema
        $errors = $this->makeMigrationJson->validateSchema($schemaArray);

        // The 'paws' section should come back with invalid column type error
        $this->assertTrue(
            isset($errors['dogs']['paws']['columnType']),
            'columnType: "paws" was supposed to come back with a column type error, instead we got: '
            . json_encode($errors)
        );

        // The 'ears_invalid' section should come back with invalid column type error
        $this->assertTrue(
            isset($errors['dogs']['ears_invalid']['columnModifier']),
            'columnModifier: "ears_invalid" was supposed to come back with a column modifier error, instead we got: '
            . json_encode($errors)
        );

        // The 'hair' section should not come back with errors because of its optional column type parameters
        $this->assertFalse(
            isset($errors['dogs']['hair']),
            'columnType: "hair:string(50):index" should be allowed to be validated as a "string", not as "string(50)": '
        );

        // The 'ears_valid' section should not come back with errors because it is a valid column modifier or index
        $this->assertFalse(
            isset($errors['dogs']['ears_valid']),
            'columnType: "ears_valid" should not come back with errors because it is a valid column modifier or index'
        );
    }
}
