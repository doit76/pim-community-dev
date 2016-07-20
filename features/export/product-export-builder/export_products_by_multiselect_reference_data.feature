@javascript
Feature: Export products according to multi select reference data values
  In order to use the enriched product data
  As a product manager
  I need to be able to export the products according to their reference data values

  Background:
    Given the "footwear" catalog configuration
    And the following reference data:
      | type   | code         | label        |
      | fabric | cashmerewool | Cashmerewool |
      | fabric | neoprene     |              |
      | fabric | silk         | Silk         |
    And the following products:
      | sku    | family | name-en_US | sole_fabric            |
      | HEEL-1 | heels  | The heel 1 | cashmerewool           |
      | HEEL-2 | heels  | The heel 2 | cashmerewool           |
      | HEEL-3 | heels  | The heel 3 | cashmerewool, neoprene |
      | HEEL-4 | heels  | The heel 4 | neoprene               |
      | HEEL-5 | heels  | The heel 5 | neoprene               |
      | HEEL-6 | heels  | The heel 6 | silk                   |
      | HEEL-7 | heels  | The heel 7 | silk                   |
      | HEEL-8 | heels  | The heel 8 |                        |
      | HEEL-9 | heels  | The heel 9 |                        |
    And the following jobs:
      | connector            | type   | alias              | code               | label              |
      | Akeneo CSV Connector | export | csv_product_export | csv_product_export | CSV product export |
    And the following job "csv_product_export" configuration:
      | filePath | %tmp%/product_export/product_export.csv |

  Scenario: Export only the product values with selected reference data value
    Given I am logged in as "Julia"
    And I am on the "csv_product_export" export job edit page
    And I visit the "Content" tab
    And I add available attributes Sole fabric
    And I filter by "sole_fabric.code" with operator "In list" and value "Cashmerewool"
    And I press the "Save" button
    When I launch the export job
    And I wait for the "csv_product_export" job to finish
    Then exported file of "csv_product_export" should contain:
      """
      sku;categories;color;description-en_US-mobile;enabled;family;groups;heel_color;manufacturer;name-en_US;price-EUR;price-USD;side_view;size;sole_color;sole_fabric;top_view
      HEEL-1;;;;1;heels;;;;"The heel 1";;;;;;cashmerewool;
      HEEL-2;;;;1;heels;;;;"The heel 2";;;;;;cashmerewool;
      HEEL-3;;;;1;heels;;;;"The heel 3";;;;;;cashmerewool,neoprene;
      """

  Scenario: Export only the product values with multiple selected reference data values
    Given I am logged in as "Julia"
    And I am on the "csv_product_export" export job edit page
    And I visit the "Content" tab
    And I add available attributes Sole fabric
    And I filter by "sole_fabric.code" with operator "In list" and value "Cashmerewool,Silk"
    And I press the "Save" button
    When I launch the export job
    And I wait for the "csv_product_export" job to finish
    Then exported file of "csv_product_export" should contain:
      """
      sku;categories;color;description-en_US-mobile;enabled;family;groups;heel_color;manufacturer;name-en_US;price-EUR;price-USD;side_view;size;sole_color;sole_fabric;top_view
      HEEL-1;;;;1;heels;;;;"The heel 1";;;;;;cashmerewool;
      HEEL-2;;;;1;heels;;;;"The heel 2";;;;;;cashmerewool;
      HEEL-3;;;;1;heels;;;;"The heel 3";;;;;;cashmerewool,neoprene;
      HEEL-6;;;;1;heels;;;;"The heel 6";;;;;;silk;
      HEEL-7;;;;1;heels;;;;"The heel 7";;;;;;silk;
      """

  Scenario: Export only the product values without reference data values
    Given I am logged in as "Julia"
    And I am on the "csv_product_export" export job edit page
    And I visit the "Content" tab
    And I add available attributes Sole fabric
    And I filter by "sole_fabric.code" with operator "Empty" and value ""
    And I press the "Save" button
    When I launch the export job
    And I wait for the "csv_product_export" job to finish
    Then exported file of "csv_product_export" should contain:
      """
      sku;categories;color;description-en_US-mobile;enabled;family;groups;heel_color;manufacturer;name-en_US;price-EUR;price-USD;side_view;size;sole_color;sole_fabric;top_view
      HEEL-8;;;;1;heels;;;;"The heel 8";;;;;;;
      HEEL-9;;;;1;heels;;;;"The heel 9";;;;;;;
      """

  Scenario: Export all the product values when no reference data is provided with operator IN LIST
    Given I am logged in as "Julia"
    And I am on the "csv_product_export" export job edit page
    And I visit the "Content" tab
    And I add available attributes Sole fabric
    And I filter by "sole_fabric.code" with operator "In list" and value ""
    And I press the "Save" button
    When I launch the export job
    And I wait for the "csv_product_export" job to finish
    Then exported file of "csv_product_export" should contain:
      """
      sku;categories;color;description-en_US-mobile;enabled;family;groups;heel_color;manufacturer;name-en_US;price-EUR;price-USD;side_view;size;sole_color;sole_fabric;top_view
      HEEL-1;;;;1;heels;;;;"The heel 1";;;;;;cashmerewool;
      HEEL-2;;;;1;heels;;;;"The heel 2";;;;;;cashmerewool;
      HEEL-3;;;;1;heels;;;;"The heel 3";;;;;;cashmerewool,neoprene;
      HEEL-4;;;;1;heels;;;;"The heel 4";;;;;;neoprene;
      HEEL-5;;;;1;heels;;;;"The heel 5";;;;;;neoprene;
      HEEL-6;;;;1;heels;;;;"The heel 6";;;;;;silk;
      HEEL-7;;;;1;heels;;;;"The heel 7";;;;;;silk;
      HEEL-8;;;;1;heels;;;;"The heel 8";;;;;;;
      HEEL-9;;;;1;heels;;;;"The heel 9";;;;;;;
      """
