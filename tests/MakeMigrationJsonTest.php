<?php

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
  public function testParseSchema(Array $jsonArray)
  {
    // Execute method
    $results = $this->makeMigrationJson->parseSchema($jsonArray);

    // Make sure we "users" got turned into "create_users_table" and has values
    $this->assertFalse(empty($results['create_users_table']), '"users" was not converted to "create_users_table"');

    // Make sure "remove_city_from_users_table" has been left intact
    $this->assertTrue(isset($results['remove_city_from_users_table']), '"remove_city_from_users_table" should be in the json array but it is not set');
  }

}
