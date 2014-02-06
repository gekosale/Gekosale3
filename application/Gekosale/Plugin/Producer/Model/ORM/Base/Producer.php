<?php

namespace Gekosale\Plugin\Producer\Model\ORM\Base;

use \DateTime;
use \Exception;
use \PDO;
use Gekosale\Plugin\File\Model\ORM\File as ChildFile;
use Gekosale\Plugin\File\Model\ORM\FileQuery;
use Gekosale\Plugin\Producer\Model\ORM\Producer as ChildProducer;
use Gekosale\Plugin\Producer\Model\ORM\ProducerDeliverer as ChildProducerDeliverer;
use Gekosale\Plugin\Producer\Model\ORM\ProducerDelivererQuery as ChildProducerDelivererQuery;
use Gekosale\Plugin\Producer\Model\ORM\ProducerI18n as ChildProducerI18n;
use Gekosale\Plugin\Producer\Model\ORM\ProducerI18nQuery as ChildProducerI18nQuery;
use Gekosale\Plugin\Producer\Model\ORM\ProducerQuery as ChildProducerQuery;
use Gekosale\Plugin\Producer\Model\ORM\ProducerShop as ChildProducerShop;
use Gekosale\Plugin\Producer\Model\ORM\ProducerShopQuery as ChildProducerShopQuery;
use Gekosale\Plugin\Producer\Model\ORM\Map\ProducerTableMap;
use Gekosale\Plugin\Product\Model\ORM\Product as ChildProduct;
use Gekosale\Plugin\Product\Model\ORM\ProductQuery;
use Gekosale\Plugin\Product\Model\ORM\Base\Product;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;

abstract class Producer implements ActiveRecordInterface 
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Gekosale\\Plugin\\Producer\\Model\\ORM\\Map\\ProducerTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the photo_id field.
     * @var        int
     */
    protected $photo_id;

    /**
     * The value for the created_at field.
     * @var        string
     */
    protected $created_at;

    /**
     * The value for the updated_at field.
     * @var        string
     */
    protected $updated_at;

    /**
     * @var        File
     */
    protected $aFile;

    /**
     * @var        ObjectCollection|ChildProducerDeliverer[] Collection to store aggregation of ChildProducerDeliverer objects.
     */
    protected $collProducerDeliverers;
    protected $collProducerDeliverersPartial;

    /**
     * @var        ObjectCollection|ChildProduct[] Collection to store aggregation of ChildProduct objects.
     */
    protected $collProducts;
    protected $collProductsPartial;

    /**
     * @var        ObjectCollection|ChildProducerShop[] Collection to store aggregation of ChildProducerShop objects.
     */
    protected $collProducerShops;
    protected $collProducerShopsPartial;

    /**
     * @var        ObjectCollection|ChildProducerI18n[] Collection to store aggregation of ChildProducerI18n objects.
     */
    protected $collProducerI18ns;
    protected $collProducerI18nsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // i18n behavior
    
    /**
     * Current locale
     * @var        string
     */
    protected $currentLocale = 'en_US';
    
    /**
     * Current translation objects
     * @var        array[ChildProducerI18n]
     */
    protected $currentTranslations;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $producerDeliverersScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $productsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $producerShopsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $producerI18nsScheduledForDeletion = null;

    /**
     * Initializes internal state of Gekosale\Plugin\Producer\Model\ORM\Base\Producer object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (Boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (Boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>Producer</code> instance.  If
     * <code>obj</code> is an instance of <code>Producer</code>, delegates to
     * <code>equals(Producer)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey()
            || null === $obj->getPrimaryKey())  {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        if (null !== $this->getPrimaryKey()) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return Producer The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return Producer The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);

        return $this;
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [id] column value.
     * 
     * @return   int
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Get the [photo_id] column value.
     * 
     * @return   int
     */
    public function getPhotoId()
    {

        return $this->photo_id;
    }

    /**
     * Get the [optionally formatted] temporal [created_at] column value.
     * 
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->created_at;
        } else {
            return $this->created_at instanceof \DateTime ? $this->created_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [updated_at] column value.
     * 
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getUpdatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->updated_at;
        } else {
            return $this->updated_at instanceof \DateTime ? $this->updated_at->format($format) : null;
        }
    }

    /**
     * Set the value of [id] column.
     * 
     * @param      int $v new value
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[ProducerTableMap::COL_ID] = true;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [photo_id] column.
     * 
     * @param      int $v new value
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function setPhotoId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->photo_id !== $v) {
            $this->photo_id = $v;
            $this->modifiedColumns[ProducerTableMap::COL_PHOTO_ID] = true;
        }

        if ($this->aFile !== null && $this->aFile->getId() !== $v) {
            $this->aFile = null;
        }


        return $this;
    } // setPhotoId()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     * 
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[ProducerTableMap::COL_CREATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [updated_at] column to a normalized version of the date/time value specified.
     * 
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->updated_at !== null || $dt !== null) {
            if ($dt !== $this->updated_at) {
                $this->updated_at = $dt;
                $this->modifiedColumns[ProducerTableMap::COL_UPDATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setUpdatedAt()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : ProducerTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : ProducerTableMap::translateFieldName('PhotoId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->photo_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : ProducerTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : ProducerTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 4; // 4 = ProducerTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \Gekosale\Plugin\Producer\Model\ORM\Producer object", 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
        if ($this->aFile !== null && $this->photo_id !== $this->aFile->getId()) {
            $this->aFile = null;
        }
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ProducerTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildProducerQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aFile = null;
            $this->collProducerDeliverers = null;

            $this->collProducts = null;

            $this->collProducerShops = null;

            $this->collProducerI18ns = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Producer::setDeleted()
     * @see Producer::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(ProducerTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildProducerQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(ProducerTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                if (!$this->isColumnModified(ProducerTableMap::COL_CREATED_AT)) {
                    $this->setCreatedAt(time());
                }
                if (!$this->isColumnModified(ProducerTableMap::COL_UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(ProducerTableMap::COL_UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                ProducerTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aFile !== null) {
                if ($this->aFile->isModified() || $this->aFile->isNew()) {
                    $affectedRows += $this->aFile->save($con);
                }
                $this->setFile($this->aFile);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->producerDeliverersScheduledForDeletion !== null) {
                if (!$this->producerDeliverersScheduledForDeletion->isEmpty()) {
                    \Gekosale\Plugin\Producer\Model\ORM\ProducerDelivererQuery::create()
                        ->filterByPrimaryKeys($this->producerDeliverersScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->producerDeliverersScheduledForDeletion = null;
                }
            }

                if ($this->collProducerDeliverers !== null) {
            foreach ($this->collProducerDeliverers as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->productsScheduledForDeletion !== null) {
                if (!$this->productsScheduledForDeletion->isEmpty()) {
                    foreach ($this->productsScheduledForDeletion as $product) {
                        // need to save related object because we set the relation to null
                        $product->save($con);
                    }
                    $this->productsScheduledForDeletion = null;
                }
            }

                if ($this->collProducts !== null) {
            foreach ($this->collProducts as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->producerShopsScheduledForDeletion !== null) {
                if (!$this->producerShopsScheduledForDeletion->isEmpty()) {
                    \Gekosale\Plugin\Producer\Model\ORM\ProducerShopQuery::create()
                        ->filterByPrimaryKeys($this->producerShopsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->producerShopsScheduledForDeletion = null;
                }
            }

                if ($this->collProducerShops !== null) {
            foreach ($this->collProducerShops as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->producerI18nsScheduledForDeletion !== null) {
                if (!$this->producerI18nsScheduledForDeletion->isEmpty()) {
                    \Gekosale\Plugin\Producer\Model\ORM\ProducerI18nQuery::create()
                        ->filterByPrimaryKeys($this->producerI18nsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->producerI18nsScheduledForDeletion = null;
                }
            }

                if ($this->collProducerI18ns !== null) {
            foreach ($this->collProducerI18ns as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[ProducerTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . ProducerTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(ProducerTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(ProducerTableMap::COL_PHOTO_ID)) {
            $modifiedColumns[':p' . $index++]  = 'PHOTO_ID';
        }
        if ($this->isColumnModified(ProducerTableMap::COL_CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }
        if ($this->isColumnModified(ProducerTableMap::COL_UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'UPDATED_AT';
        }

        $sql = sprintf(
            'INSERT INTO producer (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'ID':                        
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'PHOTO_ID':                        
                        $stmt->bindValue($identifier, $this->photo_id, PDO::PARAM_INT);
                        break;
                    case 'CREATED_AT':                        
                        $stmt->bindValue($identifier, $this->created_at ? $this->created_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'UPDATED_AT':                        
                        $stmt->bindValue($identifier, $this->updated_at ? $this->updated_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = ProducerTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getPhotoId();
                break;
            case 2:
                return $this->getCreatedAt();
                break;
            case 3:
                return $this->getUpdatedAt();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['Producer'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Producer'][$this->getPrimaryKey()] = true;
        $keys = ProducerTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getPhotoId(),
            $keys[2] => $this->getCreatedAt(),
            $keys[3] => $this->getUpdatedAt(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }
        
        if ($includeForeignObjects) {
            if (null !== $this->aFile) {
                $result['File'] = $this->aFile->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collProducerDeliverers) {
                $result['ProducerDeliverers'] = $this->collProducerDeliverers->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collProducts) {
                $result['Products'] = $this->collProducts->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collProducerShops) {
                $result['ProducerShops'] = $this->collProducerShops->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collProducerI18ns) {
                $result['ProducerI18ns'] = $this->collProducerI18ns->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name
     * @param      mixed  $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = ProducerTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setPhotoId($value);
                break;
            case 2:
                $this->setCreatedAt($value);
                break;
            case 3:
                $this->setUpdatedAt($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = ProducerTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setPhotoId($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setCreatedAt($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setUpdatedAt($arr[$keys[3]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(ProducerTableMap::DATABASE_NAME);

        if ($this->isColumnModified(ProducerTableMap::COL_ID)) $criteria->add(ProducerTableMap::COL_ID, $this->id);
        if ($this->isColumnModified(ProducerTableMap::COL_PHOTO_ID)) $criteria->add(ProducerTableMap::COL_PHOTO_ID, $this->photo_id);
        if ($this->isColumnModified(ProducerTableMap::COL_CREATED_AT)) $criteria->add(ProducerTableMap::COL_CREATED_AT, $this->created_at);
        if ($this->isColumnModified(ProducerTableMap::COL_UPDATED_AT)) $criteria->add(ProducerTableMap::COL_UPDATED_AT, $this->updated_at);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @throws LogicException if no primary key is defined
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(ProducerTableMap::DATABASE_NAME);
        $criteria->add(ProducerTableMap::COL_ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return   int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Gekosale\Plugin\Producer\Model\ORM\Producer (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setPhotoId($this->getPhotoId());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getProducerDeliverers() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addProducerDeliverer($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getProducts() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addProduct($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getProducerShops() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addProducerShop($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getProducerI18ns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addProducerI18n($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 \Gekosale\Plugin\Producer\Model\ORM\Producer Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Declares an association between this object and a ChildFile object.
     *
     * @param                  ChildFile $v
     * @return                 \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     * @throws PropelException
     */
    public function setFile(ChildFile $v = null)
    {
        if ($v === null) {
            $this->setPhotoId(NULL);
        } else {
            $this->setPhotoId($v->getId());
        }

        $this->aFile = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildFile object, it will not be re-added.
        if ($v !== null) {
            $v->addProducer($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildFile object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildFile The associated ChildFile object.
     * @throws PropelException
     */
    public function getFile(ConnectionInterface $con = null)
    {
        if ($this->aFile === null && ($this->photo_id !== null)) {
            $this->aFile = FileQuery::create()->findPk($this->photo_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aFile->addProducers($this);
             */
        }

        return $this->aFile;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('ProducerDeliverer' == $relationName) {
            return $this->initProducerDeliverers();
        }
        if ('Product' == $relationName) {
            return $this->initProducts();
        }
        if ('ProducerShop' == $relationName) {
            return $this->initProducerShops();
        }
        if ('ProducerI18n' == $relationName) {
            return $this->initProducerI18ns();
        }
    }

    /**
     * Clears out the collProducerDeliverers collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addProducerDeliverers()
     */
    public function clearProducerDeliverers()
    {
        $this->collProducerDeliverers = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collProducerDeliverers collection loaded partially.
     */
    public function resetPartialProducerDeliverers($v = true)
    {
        $this->collProducerDeliverersPartial = $v;
    }

    /**
     * Initializes the collProducerDeliverers collection.
     *
     * By default this just sets the collProducerDeliverers collection to an empty array (like clearcollProducerDeliverers());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initProducerDeliverers($overrideExisting = true)
    {
        if (null !== $this->collProducerDeliverers && !$overrideExisting) {
            return;
        }
        $this->collProducerDeliverers = new ObjectCollection();
        $this->collProducerDeliverers->setModel('\Gekosale\Plugin\Producer\Model\ORM\ProducerDeliverer');
    }

    /**
     * Gets an array of ChildProducerDeliverer objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildProducer is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildProducerDeliverer[] List of ChildProducerDeliverer objects
     * @throws PropelException
     */
    public function getProducerDeliverers($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collProducerDeliverersPartial && !$this->isNew();
        if (null === $this->collProducerDeliverers || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collProducerDeliverers) {
                // return empty collection
                $this->initProducerDeliverers();
            } else {
                $collProducerDeliverers = ChildProducerDelivererQuery::create(null, $criteria)
                    ->filterByProducer($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collProducerDeliverersPartial && count($collProducerDeliverers)) {
                        $this->initProducerDeliverers(false);

                        foreach ($collProducerDeliverers as $obj) {
                            if (false == $this->collProducerDeliverers->contains($obj)) {
                                $this->collProducerDeliverers->append($obj);
                            }
                        }

                        $this->collProducerDeliverersPartial = true;
                    }

                    reset($collProducerDeliverers);

                    return $collProducerDeliverers;
                }

                if ($partial && $this->collProducerDeliverers) {
                    foreach ($this->collProducerDeliverers as $obj) {
                        if ($obj->isNew()) {
                            $collProducerDeliverers[] = $obj;
                        }
                    }
                }

                $this->collProducerDeliverers = $collProducerDeliverers;
                $this->collProducerDeliverersPartial = false;
            }
        }

        return $this->collProducerDeliverers;
    }

    /**
     * Sets a collection of ProducerDeliverer objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $producerDeliverers A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildProducer The current object (for fluent API support)
     */
    public function setProducerDeliverers(Collection $producerDeliverers, ConnectionInterface $con = null)
    {
        $producerDeliverersToDelete = $this->getProducerDeliverers(new Criteria(), $con)->diff($producerDeliverers);

        
        $this->producerDeliverersScheduledForDeletion = $producerDeliverersToDelete;

        foreach ($producerDeliverersToDelete as $producerDelivererRemoved) {
            $producerDelivererRemoved->setProducer(null);
        }

        $this->collProducerDeliverers = null;
        foreach ($producerDeliverers as $producerDeliverer) {
            $this->addProducerDeliverer($producerDeliverer);
        }

        $this->collProducerDeliverers = $producerDeliverers;
        $this->collProducerDeliverersPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ProducerDeliverer objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ProducerDeliverer objects.
     * @throws PropelException
     */
    public function countProducerDeliverers(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collProducerDeliverersPartial && !$this->isNew();
        if (null === $this->collProducerDeliverers || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collProducerDeliverers) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getProducerDeliverers());
            }

            $query = ChildProducerDelivererQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByProducer($this)
                ->count($con);
        }

        return count($this->collProducerDeliverers);
    }

    /**
     * Method called to associate a ChildProducerDeliverer object to this object
     * through the ChildProducerDeliverer foreign key attribute.
     *
     * @param    ChildProducerDeliverer $l ChildProducerDeliverer
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function addProducerDeliverer(ChildProducerDeliverer $l)
    {
        if ($this->collProducerDeliverers === null) {
            $this->initProducerDeliverers();
            $this->collProducerDeliverersPartial = true;
        }

        if (!in_array($l, $this->collProducerDeliverers->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddProducerDeliverer($l);
        }

        return $this;
    }

    /**
     * @param ProducerDeliverer $producerDeliverer The producerDeliverer object to add.
     */
    protected function doAddProducerDeliverer($producerDeliverer)
    {
        $this->collProducerDeliverers[]= $producerDeliverer;
        $producerDeliverer->setProducer($this);
    }

    /**
     * @param  ProducerDeliverer $producerDeliverer The producerDeliverer object to remove.
     * @return ChildProducer The current object (for fluent API support)
     */
    public function removeProducerDeliverer($producerDeliverer)
    {
        if ($this->getProducerDeliverers()->contains($producerDeliverer)) {
            $this->collProducerDeliverers->remove($this->collProducerDeliverers->search($producerDeliverer));
            if (null === $this->producerDeliverersScheduledForDeletion) {
                $this->producerDeliverersScheduledForDeletion = clone $this->collProducerDeliverers;
                $this->producerDeliverersScheduledForDeletion->clear();
            }
            $this->producerDeliverersScheduledForDeletion[]= clone $producerDeliverer;
            $producerDeliverer->setProducer(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related ProducerDeliverers from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProducerDeliverer[] List of ChildProducerDeliverer objects
     */
    public function getProducerDeliverersJoinDeliverer($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildProducerDelivererQuery::create(null, $criteria);
        $query->joinWith('Deliverer', $joinBehavior);

        return $this->getProducerDeliverers($query, $con);
    }

    /**
     * Clears out the collProducts collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addProducts()
     */
    public function clearProducts()
    {
        $this->collProducts = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collProducts collection loaded partially.
     */
    public function resetPartialProducts($v = true)
    {
        $this->collProductsPartial = $v;
    }

    /**
     * Initializes the collProducts collection.
     *
     * By default this just sets the collProducts collection to an empty array (like clearcollProducts());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initProducts($overrideExisting = true)
    {
        if (null !== $this->collProducts && !$overrideExisting) {
            return;
        }
        $this->collProducts = new ObjectCollection();
        $this->collProducts->setModel('\Gekosale\Plugin\Product\Model\ORM\Product');
    }

    /**
     * Gets an array of ChildProduct objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildProducer is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildProduct[] List of ChildProduct objects
     * @throws PropelException
     */
    public function getProducts($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collProductsPartial && !$this->isNew();
        if (null === $this->collProducts || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collProducts) {
                // return empty collection
                $this->initProducts();
            } else {
                $collProducts = ProductQuery::create(null, $criteria)
                    ->filterByProducer($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collProductsPartial && count($collProducts)) {
                        $this->initProducts(false);

                        foreach ($collProducts as $obj) {
                            if (false == $this->collProducts->contains($obj)) {
                                $this->collProducts->append($obj);
                            }
                        }

                        $this->collProductsPartial = true;
                    }

                    reset($collProducts);

                    return $collProducts;
                }

                if ($partial && $this->collProducts) {
                    foreach ($this->collProducts as $obj) {
                        if ($obj->isNew()) {
                            $collProducts[] = $obj;
                        }
                    }
                }

                $this->collProducts = $collProducts;
                $this->collProductsPartial = false;
            }
        }

        return $this->collProducts;
    }

    /**
     * Sets a collection of Product objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $products A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildProducer The current object (for fluent API support)
     */
    public function setProducts(Collection $products, ConnectionInterface $con = null)
    {
        $productsToDelete = $this->getProducts(new Criteria(), $con)->diff($products);

        
        $this->productsScheduledForDeletion = $productsToDelete;

        foreach ($productsToDelete as $productRemoved) {
            $productRemoved->setProducer(null);
        }

        $this->collProducts = null;
        foreach ($products as $product) {
            $this->addProduct($product);
        }

        $this->collProducts = $products;
        $this->collProductsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Product objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Product objects.
     * @throws PropelException
     */
    public function countProducts(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collProductsPartial && !$this->isNew();
        if (null === $this->collProducts || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collProducts) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getProducts());
            }

            $query = ProductQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByProducer($this)
                ->count($con);
        }

        return count($this->collProducts);
    }

    /**
     * Method called to associate a ChildProduct object to this object
     * through the ChildProduct foreign key attribute.
     *
     * @param    ChildProduct $l ChildProduct
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function addProduct(ChildProduct $l)
    {
        if ($this->collProducts === null) {
            $this->initProducts();
            $this->collProductsPartial = true;
        }

        if (!in_array($l, $this->collProducts->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddProduct($l);
        }

        return $this;
    }

    /**
     * @param Product $product The product object to add.
     */
    protected function doAddProduct($product)
    {
        $this->collProducts[]= $product;
        $product->setProducer($this);
    }

    /**
     * @param  Product $product The product object to remove.
     * @return ChildProducer The current object (for fluent API support)
     */
    public function removeProduct($product)
    {
        if ($this->getProducts()->contains($product)) {
            $this->collProducts->remove($this->collProducts->search($product));
            if (null === $this->productsScheduledForDeletion) {
                $this->productsScheduledForDeletion = clone $this->collProducts;
                $this->productsScheduledForDeletion->clear();
            }
            $this->productsScheduledForDeletion[]= $product;
            $product->setProducer(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related Products from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProduct[] List of ChildProduct objects
     */
    public function getProductsJoinAvailability($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ProductQuery::create(null, $criteria);
        $query->joinWith('Availability', $joinBehavior);

        return $this->getProducts($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related Products from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProduct[] List of ChildProduct objects
     */
    public function getProductsJoinCurrencyRelatedByBuyCurrencyId($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ProductQuery::create(null, $criteria);
        $query->joinWith('CurrencyRelatedByBuyCurrencyId', $joinBehavior);

        return $this->getProducts($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related Products from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProduct[] List of ChildProduct objects
     */
    public function getProductsJoinCurrencyRelatedBySellCurrencyId($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ProductQuery::create(null, $criteria);
        $query->joinWith('CurrencyRelatedBySellCurrencyId', $joinBehavior);

        return $this->getProducts($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related Products from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProduct[] List of ChildProduct objects
     */
    public function getProductsJoinUnitMeasure($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ProductQuery::create(null, $criteria);
        $query->joinWith('UnitMeasure', $joinBehavior);

        return $this->getProducts($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related Products from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProduct[] List of ChildProduct objects
     */
    public function getProductsJoinVat($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ProductQuery::create(null, $criteria);
        $query->joinWith('Vat', $joinBehavior);

        return $this->getProducts($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related Products from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProduct[] List of ChildProduct objects
     */
    public function getProductsJoinTechnicalDataSet($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ProductQuery::create(null, $criteria);
        $query->joinWith('TechnicalDataSet', $joinBehavior);

        return $this->getProducts($query, $con);
    }

    /**
     * Clears out the collProducerShops collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addProducerShops()
     */
    public function clearProducerShops()
    {
        $this->collProducerShops = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collProducerShops collection loaded partially.
     */
    public function resetPartialProducerShops($v = true)
    {
        $this->collProducerShopsPartial = $v;
    }

    /**
     * Initializes the collProducerShops collection.
     *
     * By default this just sets the collProducerShops collection to an empty array (like clearcollProducerShops());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initProducerShops($overrideExisting = true)
    {
        if (null !== $this->collProducerShops && !$overrideExisting) {
            return;
        }
        $this->collProducerShops = new ObjectCollection();
        $this->collProducerShops->setModel('\Gekosale\Plugin\Producer\Model\ORM\ProducerShop');
    }

    /**
     * Gets an array of ChildProducerShop objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildProducer is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildProducerShop[] List of ChildProducerShop objects
     * @throws PropelException
     */
    public function getProducerShops($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collProducerShopsPartial && !$this->isNew();
        if (null === $this->collProducerShops || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collProducerShops) {
                // return empty collection
                $this->initProducerShops();
            } else {
                $collProducerShops = ChildProducerShopQuery::create(null, $criteria)
                    ->filterByProducer($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collProducerShopsPartial && count($collProducerShops)) {
                        $this->initProducerShops(false);

                        foreach ($collProducerShops as $obj) {
                            if (false == $this->collProducerShops->contains($obj)) {
                                $this->collProducerShops->append($obj);
                            }
                        }

                        $this->collProducerShopsPartial = true;
                    }

                    reset($collProducerShops);

                    return $collProducerShops;
                }

                if ($partial && $this->collProducerShops) {
                    foreach ($this->collProducerShops as $obj) {
                        if ($obj->isNew()) {
                            $collProducerShops[] = $obj;
                        }
                    }
                }

                $this->collProducerShops = $collProducerShops;
                $this->collProducerShopsPartial = false;
            }
        }

        return $this->collProducerShops;
    }

    /**
     * Sets a collection of ProducerShop objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $producerShops A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildProducer The current object (for fluent API support)
     */
    public function setProducerShops(Collection $producerShops, ConnectionInterface $con = null)
    {
        $producerShopsToDelete = $this->getProducerShops(new Criteria(), $con)->diff($producerShops);

        
        $this->producerShopsScheduledForDeletion = $producerShopsToDelete;

        foreach ($producerShopsToDelete as $producerShopRemoved) {
            $producerShopRemoved->setProducer(null);
        }

        $this->collProducerShops = null;
        foreach ($producerShops as $producerShop) {
            $this->addProducerShop($producerShop);
        }

        $this->collProducerShops = $producerShops;
        $this->collProducerShopsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ProducerShop objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ProducerShop objects.
     * @throws PropelException
     */
    public function countProducerShops(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collProducerShopsPartial && !$this->isNew();
        if (null === $this->collProducerShops || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collProducerShops) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getProducerShops());
            }

            $query = ChildProducerShopQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByProducer($this)
                ->count($con);
        }

        return count($this->collProducerShops);
    }

    /**
     * Method called to associate a ChildProducerShop object to this object
     * through the ChildProducerShop foreign key attribute.
     *
     * @param    ChildProducerShop $l ChildProducerShop
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function addProducerShop(ChildProducerShop $l)
    {
        if ($this->collProducerShops === null) {
            $this->initProducerShops();
            $this->collProducerShopsPartial = true;
        }

        if (!in_array($l, $this->collProducerShops->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddProducerShop($l);
        }

        return $this;
    }

    /**
     * @param ProducerShop $producerShop The producerShop object to add.
     */
    protected function doAddProducerShop($producerShop)
    {
        $this->collProducerShops[]= $producerShop;
        $producerShop->setProducer($this);
    }

    /**
     * @param  ProducerShop $producerShop The producerShop object to remove.
     * @return ChildProducer The current object (for fluent API support)
     */
    public function removeProducerShop($producerShop)
    {
        if ($this->getProducerShops()->contains($producerShop)) {
            $this->collProducerShops->remove($this->collProducerShops->search($producerShop));
            if (null === $this->producerShopsScheduledForDeletion) {
                $this->producerShopsScheduledForDeletion = clone $this->collProducerShops;
                $this->producerShopsScheduledForDeletion->clear();
            }
            $this->producerShopsScheduledForDeletion[]= clone $producerShop;
            $producerShop->setProducer(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Producer is new, it will return
     * an empty collection; or if this Producer has previously
     * been saved, it will retrieve related ProducerShops from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Producer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProducerShop[] List of ChildProducerShop objects
     */
    public function getProducerShopsJoinShop($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildProducerShopQuery::create(null, $criteria);
        $query->joinWith('Shop', $joinBehavior);

        return $this->getProducerShops($query, $con);
    }

    /**
     * Clears out the collProducerI18ns collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addProducerI18ns()
     */
    public function clearProducerI18ns()
    {
        $this->collProducerI18ns = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collProducerI18ns collection loaded partially.
     */
    public function resetPartialProducerI18ns($v = true)
    {
        $this->collProducerI18nsPartial = $v;
    }

    /**
     * Initializes the collProducerI18ns collection.
     *
     * By default this just sets the collProducerI18ns collection to an empty array (like clearcollProducerI18ns());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initProducerI18ns($overrideExisting = true)
    {
        if (null !== $this->collProducerI18ns && !$overrideExisting) {
            return;
        }
        $this->collProducerI18ns = new ObjectCollection();
        $this->collProducerI18ns->setModel('\Gekosale\Plugin\Producer\Model\ORM\ProducerI18n');
    }

    /**
     * Gets an array of ChildProducerI18n objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildProducer is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildProducerI18n[] List of ChildProducerI18n objects
     * @throws PropelException
     */
    public function getProducerI18ns($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collProducerI18nsPartial && !$this->isNew();
        if (null === $this->collProducerI18ns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collProducerI18ns) {
                // return empty collection
                $this->initProducerI18ns();
            } else {
                $collProducerI18ns = ChildProducerI18nQuery::create(null, $criteria)
                    ->filterByProducer($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collProducerI18nsPartial && count($collProducerI18ns)) {
                        $this->initProducerI18ns(false);

                        foreach ($collProducerI18ns as $obj) {
                            if (false == $this->collProducerI18ns->contains($obj)) {
                                $this->collProducerI18ns->append($obj);
                            }
                        }

                        $this->collProducerI18nsPartial = true;
                    }

                    reset($collProducerI18ns);

                    return $collProducerI18ns;
                }

                if ($partial && $this->collProducerI18ns) {
                    foreach ($this->collProducerI18ns as $obj) {
                        if ($obj->isNew()) {
                            $collProducerI18ns[] = $obj;
                        }
                    }
                }

                $this->collProducerI18ns = $collProducerI18ns;
                $this->collProducerI18nsPartial = false;
            }
        }

        return $this->collProducerI18ns;
    }

    /**
     * Sets a collection of ProducerI18n objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $producerI18ns A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildProducer The current object (for fluent API support)
     */
    public function setProducerI18ns(Collection $producerI18ns, ConnectionInterface $con = null)
    {
        $producerI18nsToDelete = $this->getProducerI18ns(new Criteria(), $con)->diff($producerI18ns);

        
        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->producerI18nsScheduledForDeletion = clone $producerI18nsToDelete;

        foreach ($producerI18nsToDelete as $producerI18nRemoved) {
            $producerI18nRemoved->setProducer(null);
        }

        $this->collProducerI18ns = null;
        foreach ($producerI18ns as $producerI18n) {
            $this->addProducerI18n($producerI18n);
        }

        $this->collProducerI18ns = $producerI18ns;
        $this->collProducerI18nsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ProducerI18n objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ProducerI18n objects.
     * @throws PropelException
     */
    public function countProducerI18ns(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collProducerI18nsPartial && !$this->isNew();
        if (null === $this->collProducerI18ns || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collProducerI18ns) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getProducerI18ns());
            }

            $query = ChildProducerI18nQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByProducer($this)
                ->count($con);
        }

        return count($this->collProducerI18ns);
    }

    /**
     * Method called to associate a ChildProducerI18n object to this object
     * through the ChildProducerI18n foreign key attribute.
     *
     * @param    ChildProducerI18n $l ChildProducerI18n
     * @return   \Gekosale\Plugin\Producer\Model\ORM\Producer The current object (for fluent API support)
     */
    public function addProducerI18n(ChildProducerI18n $l)
    {
        if ($l && $locale = $l->getLocale()) {
            $this->setLocale($locale);
            $this->currentTranslations[$locale] = $l;
        }
        if ($this->collProducerI18ns === null) {
            $this->initProducerI18ns();
            $this->collProducerI18nsPartial = true;
        }

        if (!in_array($l, $this->collProducerI18ns->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddProducerI18n($l);
        }

        return $this;
    }

    /**
     * @param ProducerI18n $producerI18n The producerI18n object to add.
     */
    protected function doAddProducerI18n($producerI18n)
    {
        $this->collProducerI18ns[]= $producerI18n;
        $producerI18n->setProducer($this);
    }

    /**
     * @param  ProducerI18n $producerI18n The producerI18n object to remove.
     * @return ChildProducer The current object (for fluent API support)
     */
    public function removeProducerI18n($producerI18n)
    {
        if ($this->getProducerI18ns()->contains($producerI18n)) {
            $this->collProducerI18ns->remove($this->collProducerI18ns->search($producerI18n));
            if (null === $this->producerI18nsScheduledForDeletion) {
                $this->producerI18nsScheduledForDeletion = clone $this->collProducerI18ns;
                $this->producerI18nsScheduledForDeletion->clear();
            }
            $this->producerI18nsScheduledForDeletion[]= clone $producerI18n;
            $producerI18n->setProducer(null);
        }

        return $this;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->photo_id = null;
        $this->created_at = null;
        $this->updated_at = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collProducerDeliverers) {
                foreach ($this->collProducerDeliverers as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collProducts) {
                foreach ($this->collProducts as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collProducerShops) {
                foreach ($this->collProducerShops as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collProducerI18ns) {
                foreach ($this->collProducerI18ns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        // i18n behavior
        $this->currentLocale = 'en_US';
        $this->currentTranslations = null;

        $this->collProducerDeliverers = null;
        $this->collProducts = null;
        $this->collProducerShops = null;
        $this->collProducerI18ns = null;
        $this->aFile = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(ProducerTableMap::DEFAULT_STRING_FORMAT);
    }

    // i18n behavior
    
    /**
     * Sets the locale for translations
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     *
     * @return    ChildProducer The current object (for fluent API support)
     */
    public function setLocale($locale = 'en_US')
    {
        $this->currentLocale = $locale;
    
        return $this;
    }
    
    /**
     * Gets the locale for translations
     *
     * @return    string $locale Locale to use for the translation, e.g. 'fr_FR'
     */
    public function getLocale()
    {
        return $this->currentLocale;
    }
    
    /**
     * Returns the current translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildProducerI18n */
    public function getTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!isset($this->currentTranslations[$locale])) {
            if (null !== $this->collProducerI18ns) {
                foreach ($this->collProducerI18ns as $translation) {
                    if ($translation->getLocale() == $locale) {
                        $this->currentTranslations[$locale] = $translation;
    
                        return $translation;
                    }
                }
            }
            if ($this->isNew()) {
                $translation = new ChildProducerI18n();
                $translation->setLocale($locale);
            } else {
                $translation = ChildProducerI18nQuery::create()
                    ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                    ->findOneOrCreate($con);
                $this->currentTranslations[$locale] = $translation;
            }
            $this->addProducerI18n($translation);
        }
    
        return $this->currentTranslations[$locale];
    }
    
    /**
     * Remove the translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return    ChildProducer The current object (for fluent API support)
     */
    public function removeTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!$this->isNew()) {
            ChildProducerI18nQuery::create()
                ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                ->delete($con);
        }
        if (isset($this->currentTranslations[$locale])) {
            unset($this->currentTranslations[$locale]);
        }
        foreach ($this->collProducerI18ns as $key => $translation) {
            if ($translation->getLocale() == $locale) {
                unset($this->collProducerI18ns[$key]);
                break;
            }
        }
    
        return $this;
    }
    
    /**
     * Returns the current translation
     *
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildProducerI18n */
    public function getCurrentTranslation(ConnectionInterface $con = null)
    {
        return $this->getTranslation($this->getLocale(), $con);
    }
    
    
        /**
         * Get the [name] column value.
         * 
         * @return   string
         */
        public function getName()
        {
        return $this->getCurrentTranslation()->getName();
    }
    
    
        /**
         * Set the value of [name] column.
         * 
         * @param      string $v new value
         * @return   \Gekosale\Plugin\Producer\Model\ORM\ProducerI18n The current object (for fluent API support)
         */
        public function setName($v)
        {    $this->getCurrentTranslation()->setName($v);
    
        return $this;
    }
    
    
        /**
         * Get the [meta_title] column value.
         * 
         * @return   string
         */
        public function getMetaTitle()
        {
        return $this->getCurrentTranslation()->getMetaTitle();
    }
    
    
        /**
         * Set the value of [meta_title] column.
         * 
         * @param      string $v new value
         * @return   \Gekosale\Plugin\Producer\Model\ORM\ProducerI18n The current object (for fluent API support)
         */
        public function setMetaTitle($v)
        {    $this->getCurrentTranslation()->setMetaTitle($v);
    
        return $this;
    }
    
    
        /**
         * Get the [meta_keyword] column value.
         * 
         * @return   string
         */
        public function getMetaKeyword()
        {
        return $this->getCurrentTranslation()->getMetaKeyword();
    }
    
    
        /**
         * Set the value of [meta_keyword] column.
         * 
         * @param      string $v new value
         * @return   \Gekosale\Plugin\Producer\Model\ORM\ProducerI18n The current object (for fluent API support)
         */
        public function setMetaKeyword($v)
        {    $this->getCurrentTranslation()->setMetaKeyword($v);
    
        return $this;
    }
    
    
        /**
         * Get the [meta_description] column value.
         * 
         * @return   string
         */
        public function getMetaDescription()
        {
        return $this->getCurrentTranslation()->getMetaDescription();
    }
    
    
        /**
         * Set the value of [meta_description] column.
         * 
         * @param      string $v new value
         * @return   \Gekosale\Plugin\Producer\Model\ORM\ProducerI18n The current object (for fluent API support)
         */
        public function setMetaDescription($v)
        {    $this->getCurrentTranslation()->setMetaDescription($v);
    
        return $this;
    }

    // timestampable behavior
    
    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     ChildProducer The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[ProducerTableMap::COL_UPDATED_AT] = true;
    
        return $this;
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}