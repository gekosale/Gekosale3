<?php

namespace Gekosale\Plugin\Product\Model\ORM\Map;

use Gekosale\Plugin\Product\Model\ORM\ProductPhoto;
use Gekosale\Plugin\Product\Model\ORM\ProductPhotoQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\InstancePoolTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Map\TableMapTrait;


/**
 * This class defines the structure of the 'product_photo' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class ProductPhotoTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;
    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Gekosale.Plugin.Product.Model.ORM.Map.ProductPhotoTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'product_photo';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Gekosale\\Plugin\\Product\\Model\\ORM\\ProductPhoto';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Gekosale.Plugin.Product.Model.ORM.ProductPhoto';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 5;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 5;

    /**
     * the column name for the ID field
     */
    const COL_ID = 'product_photo.ID';

    /**
     * the column name for the PRODUCT_ID field
     */
    const COL_PRODUCT_ID = 'product_photo.PRODUCT_ID';

    /**
     * the column name for the PHOTO_ID field
     */
    const COL_PHOTO_ID = 'product_photo.PHOTO_ID';

    /**
     * the column name for the IS_MAIN_PHOTO field
     */
    const COL_IS_MAIN_PHOTO = 'product_photo.IS_MAIN_PHOTO';

    /**
     * the column name for the IS_VISIBLE field
     */
    const COL_IS_VISIBLE = 'product_photo.IS_VISIBLE';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('Id', 'ProductId', 'PhotoId', 'IsMainPhoto', 'IsVisible', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'productId', 'photoId', 'isMainPhoto', 'isVisible', ),
        self::TYPE_COLNAME       => array(ProductPhotoTableMap::COL_ID, ProductPhotoTableMap::COL_PRODUCT_ID, ProductPhotoTableMap::COL_PHOTO_ID, ProductPhotoTableMap::COL_IS_MAIN_PHOTO, ProductPhotoTableMap::COL_IS_VISIBLE, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID', 'COL_PRODUCT_ID', 'COL_PHOTO_ID', 'COL_IS_MAIN_PHOTO', 'COL_IS_VISIBLE', ),
        self::TYPE_FIELDNAME     => array('id', 'product_id', 'photo_id', 'is_main_photo', 'is_visible', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'ProductId' => 1, 'PhotoId' => 2, 'IsMainPhoto' => 3, 'IsVisible' => 4, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'productId' => 1, 'photoId' => 2, 'isMainPhoto' => 3, 'isVisible' => 4, ),
        self::TYPE_COLNAME       => array(ProductPhotoTableMap::COL_ID => 0, ProductPhotoTableMap::COL_PRODUCT_ID => 1, ProductPhotoTableMap::COL_PHOTO_ID => 2, ProductPhotoTableMap::COL_IS_MAIN_PHOTO => 3, ProductPhotoTableMap::COL_IS_VISIBLE => 4, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID' => 0, 'COL_PRODUCT_ID' => 1, 'COL_PHOTO_ID' => 2, 'COL_IS_MAIN_PHOTO' => 3, 'COL_IS_VISIBLE' => 4, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'product_id' => 1, 'photo_id' => 2, 'is_main_photo' => 3, 'is_visible' => 4, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * Initialize the table attributes and columns
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('product_photo');
        $this->setPhpName('ProductPhoto');
        $this->setClassName('\\Gekosale\\Plugin\\Product\\Model\\ORM\\ProductPhoto');
        $this->setPackage('Gekosale.Plugin.Product.Model.ORM');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, 10, null);
        $this->addForeignKey('PRODUCT_ID', 'ProductId', 'INTEGER', 'product', 'ID', true, 10, null);
        $this->addForeignKey('PHOTO_ID', 'PhotoId', 'INTEGER', 'file', 'ID', false, 10, null);
        $this->addColumn('IS_MAIN_PHOTO', 'IsMainPhoto', 'INTEGER', true, 10, 1);
        $this->addColumn('IS_VISIBLE', 'IsVisible', 'INTEGER', true, null, 1);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('File', '\\Gekosale\\Plugin\\File\\Model\\ORM\\File', RelationMap::MANY_TO_ONE, array('photo_id' => 'id', ), 'CASCADE', null);
        $this->addRelation('Product', '\\Gekosale\\Plugin\\Product\\Model\\ORM\\Product', RelationMap::MANY_TO_ONE, array('product_id' => 'id', ), 'CASCADE', 'CASCADE');
    } // buildRelations()

    /**
     * Retrieves a string version of the primary key from the DB resultset row that can be used to uniquely identify a row in this table.
     *
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, a serialize()d version of the primary key will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     */
    public static function getPrimaryKeyHashFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        // If the PK cannot be derived from the row, return NULL.
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
    }

    /**
     * Retrieves the primary key from the DB resultset row
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, an array of the primary key columns will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return mixed The primary key of the row
     */
    public static function getPrimaryKeyFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {

            return (int) $row[
                            $indexType == TableMap::TYPE_NUM
                            ? 0 + $offset
                            : self::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)
                        ];
    }
    
    /**
     * The class that the tableMap will make instances of.
     *
     * If $withPrefix is true, the returned path
     * uses a dot-path notation which is translated into a path
     * relative to a location on the PHP include_path.
     * (e.g. path.to.MyClass -> 'path/to/MyClass.php')
     *
     * @param boolean $withPrefix Whether or not to return the path with the class name
     * @return string path.to.ClassName
     */
    public static function getOMClass($withPrefix = true)
    {
        return $withPrefix ? ProductPhotoTableMap::CLASS_DEFAULT : ProductPhotoTableMap::OM_CLASS;
    }

    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param array  $row       row returned by DataFetcher->fetch().
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                 One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     * @return array (ProductPhoto object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = ProductPhotoTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = ProductPhotoTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + ProductPhotoTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = ProductPhotoTableMap::OM_CLASS;
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            ProductPhotoTableMap::addInstanceToPool($obj, $key);
        }

        return array($obj, $col);
    }

    /**
     * The returned array will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @param DataFetcherInterface $dataFetcher
     * @return array
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function populateObjects(DataFetcherInterface $dataFetcher)
    {
        $results = array();
    
        // set the class once to avoid overhead in the loop
        $cls = static::getOMClass(false);
        // populate the object(s)
        while ($row = $dataFetcher->fetch()) {
            $key = ProductPhotoTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = ProductPhotoTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                ProductPhotoTableMap::addInstanceToPool($obj, $key);
            } // if key exists
        }

        return $results;
    }
    /**
     * Add all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be added to the select list and only loaded
     * on demand.
     *
     * @param Criteria $criteria object containing the columns to add.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function addSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->addSelectColumn(ProductPhotoTableMap::COL_ID);
            $criteria->addSelectColumn(ProductPhotoTableMap::COL_PRODUCT_ID);
            $criteria->addSelectColumn(ProductPhotoTableMap::COL_PHOTO_ID);
            $criteria->addSelectColumn(ProductPhotoTableMap::COL_IS_MAIN_PHOTO);
            $criteria->addSelectColumn(ProductPhotoTableMap::COL_IS_VISIBLE);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.PRODUCT_ID');
            $criteria->addSelectColumn($alias . '.PHOTO_ID');
            $criteria->addSelectColumn($alias . '.IS_MAIN_PHOTO');
            $criteria->addSelectColumn($alias . '.IS_VISIBLE');
        }
    }

    /**
     * Returns the TableMap related to this object.
     * This method is not needed for general use but a specific application could have a need.
     * @return TableMap
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap(ProductPhotoTableMap::DATABASE_NAME)->getTable(ProductPhotoTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
      $dbMap = Propel::getServiceContainer()->getDatabaseMap(ProductPhotoTableMap::DATABASE_NAME);
      if (!$dbMap->hasTable(ProductPhotoTableMap::TABLE_NAME)) {
        $dbMap->addTableObject(new ProductPhotoTableMap());
      }
    }

    /**
     * Performs a DELETE on the database, given a ProductPhoto or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ProductPhoto object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
     public static function doDelete($values, ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ProductPhotoTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Gekosale\Plugin\Product\Model\ORM\ProductPhoto) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(ProductPhotoTableMap::DATABASE_NAME);
            $criteria->add(ProductPhotoTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = ProductPhotoQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) { ProductPhotoTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) { ProductPhotoTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the product_photo table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return ProductPhotoQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a ProductPhoto or Criteria object.
     *
     * @param mixed               $criteria Criteria or ProductPhoto object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ProductPhotoTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from ProductPhoto object
        }

        if ($criteria->containsKey(ProductPhotoTableMap::COL_ID) && $criteria->keyContainsValue(ProductPhotoTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.ProductPhotoTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = ProductPhotoQuery::create()->mergeWith($criteria);

        try {
            // use transaction because $criteria could contain info
            // for more than one table (I guess, conceivably)
            $con->beginTransaction();
            $pk = $query->doInsert($con);
            $con->commit();
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }

        return $pk;
    }

} // ProductPhotoTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
ProductPhotoTableMap::buildTableMap();