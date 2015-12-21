<?php

namespace Joseki\Migration\Generator;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Joseki\Migration\Generator\DBAL\Types\LongTextType;
use Joseki\Migration\Generator\DBAL\Types\TimestampType;
use Joseki\Migration\InvalidArgumentException;
use LeanMapper\Entity;
use LeanMapper\Exception;
use LeanMapper\IMapper;
use LeanMapper\Reflection\Property;
use LeanMapper\Relationship\HasMany;
use LeanMapper\Relationship\HasOne;

class LeanMapperSchemaGenerator
{

    private $mapper;

    private $defaultConfig = array(
        'autoincrement' => 'auto',
        'collate' => 'utf8_unicode_ci',
    );



    public function __construct(IMapper $mapper)
    {
        $this->mapper = $mapper;
    }



    public function createSchema(array $entities, array $config = array())
    {
        $config = array_merge($this->defaultConfig, $config);

        $schema = new Schema();
        Type::addType(LongTextType::LONG_TEXT, '\Joseki\Migration\Generator\DBAL\Types\LongTextType');
        Type::addType(TimestampType::TIMESTAMP, '\Joseki\Migration\Generator\DBAL\Types\TimestampType');

        $createdTables = array();
        /** @var \LeanMapper\Entity $entity */
        foreach ($entities as $entity) {
            $reflection = $entity->getReflection($this->mapper);
            $properties = $reflection->getEntityProperties();
            $onEnd = array();

            if (count($properties) === 0) {
                continue;
            }

            $tableName = $this->mapper->getTable(get_class($entity));
            $table = $schema->createTable($tableName);
            $table->addOption('collate', $config['collate']);
            $primaryKey = $this->mapper->getPrimaryKey($tableName);

            foreach ($properties as $property) {
                /** @var Property $property */

                if ($this->isIgnored($property)) {
                    continue;
                }

                if (!$property->hasRelationship()) {
                    $type = $this->getType($property);

                    if ($type === null) {
                        if (!$property->isWritable()) {
                            continue;
                        }
                        throw new \Exception('Unknown type');
                    }

                    /** @var Column $column */
                    $column = $table->addColumn($property->getColumn(), $type);

                    if ($property->getName() == $primaryKey) {
                        $table->setPrimaryKey([$property->getColumn()]);
                        if ($property->hasCustomFlag('unique')) {
                            throw new Exception\InvalidAnnotationException(
                                "Entity {$reflection->name}:{$property->getName()} - m:unique can not be used together with m:pk."
                            );
                        }
                        if ($config['autoincrement'] == 'auto' && $type === 'integer') {
                            $column->setAutoincrement(true);
                        }
                    }

                    if ($property->hasCustomFlag('autoincrement')) {
                        $column->setAutoincrement(true);
                    }

                    if ($type == 'string' && $property->hasCustomFlag('size')) {
                        $column->setLength($property->getCustomFlagValue('size'));
                    }

                    if ($type == TimestampType::TIMESTAMP) {
                        $flagArgs = explode(':', strtolower($property->getCustomFlagValue('type')));
                        if (count($flagArgs) > 1 && $flagArgs[1] == 'true') {
                            $column->setColumnDefinition('TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP');
                        } else {
                            $column->setColumnDefinition('TIMESTAMP NOT NULL');
                        }
                    }
                } else {
                    $relationship = $property->getRelationship();

                    if ($relationship instanceof HasMany) {
                        $relationshipTableName = $relationship->getRelationshipTable();
                        if (!in_array($relationshipTableName, $createdTables)) {
                            $createdTables[] = $relationshipTableName;
                            $relationshipTable = $schema->createTable($relationship->getRelationshipTable());

                            $sourceTableType = $this->getRelationshipColumnType($relationship->getColumnReferencingSourceTable());
                            $targetTableType = $this->getRelationshipColumnType($relationship->getColumnReferencingTargetTable());
                            $sourceColumn = $relationshipTable->addColumn($relationship->getColumnReferencingSourceTable(), $sourceTableType);
                            $targetColumn = $relationshipTable->addColumn($relationship->getColumnReferencingTargetTable(), $targetTableType);
                            $relationshipTable->addForeignKeyConstraint(
                                $table,
                                [$relationship->getColumnReferencingSourceTable()],
                                [$this->mapper->getPrimaryKey($relationship->getRelationshipTable())],
                                array('onDelete' => 'CASCADE')
                            );

                            $relationshipTable->addForeignKeyConstraint(
                                $relationship->getTargetTable(),
                                [$relationship->getColumnReferencingTargetTable()],
                                [$this->mapper->getPrimaryKey($relationship->getRelationshipTable())],
                                array('onDelete' => 'CASCADE')
                            );

                            $sourceColumnProperty = $this->getRelationshipColumnProperty($tableName);
                            if ($this->getType($sourceColumnProperty) === 'string' && $sourceColumnProperty->hasCustomFlag('size')) {
                                $sourceColumn->setLength($sourceColumnProperty->getCustomFlagValue('size'));
                            }

                            $targetColumnProperty = $this->getRelationshipColumnProperty($relationship->getTargetTable());
                            if ($this->getType($targetColumnProperty) === 'string' && $targetColumnProperty->hasCustomFlag('size')) {
                                $targetColumn->setLength($targetColumnProperty->getCustomFlagValue('size'));
                            }
                        }
                    } elseif ($relationship instanceof HasOne) {
                        $targetEntityClass = $property->getType();
                        $targetTable = $this->mapper->getTable($targetEntityClass);
                        $targetType = $this->getRelationshipColumnType($targetTable);

                        $column = $table->addColumn($relationship->getColumnReferencingTargetTable(), $targetType);

                        $targetColumnProperty = $this->getRelationshipColumnProperty($targetTable);
                        if ($this->getType($targetColumnProperty) === 'string' && $targetColumnProperty->hasCustomFlag('size')) {
                            $column->setLength($targetColumnProperty->getCustomFlagValue('size'));
                        }

                        if (!$property->hasCustomFlag('nofk')) {
                            $cascade = $property->isNullable() ? 'SET NULL' : 'CASCADE';
                            $table->addForeignKeyConstraint(
                                $relationship->getTargetTable(),
                                [$column->getName()],
                                [$this->mapper->getPrimaryKey($relationship->getTargetTable())],
                                array('onDelete' => $cascade)
                            );
                        }
                    }
                }

                if ($property->hasCustomFlag('unique')) {
                    $indexColumns = $this->parseColumns($property->getCustomFlagValue('unique'), array($column->getName()));
                    $onEnd[] = $this->createIndexClosure($table, $indexColumns, true);
                }

                if ($property->hasCustomFlag('index')) {
                    $indexColumns = $this->parseColumns($property->getCustomFlagValue('index'), array($column->getName()));
                    $onEnd[] = $this->createIndexClosure($table, $indexColumns, false);
                }

                if ($property->hasCustomFlag('comment')) {
                    $column->setComment($property->getCustomFlagValue('comment'));
                }

                if (isset($column)) {
                    if ($property->isNullable()) {
                        $column->setNotnull(false);
                    }

                    if ($property->hasDefaultValue()) {
                        $column->setDefault($property->getDefaultValue());
                    }
                }
            }
            foreach ($onEnd as $cb) {
                $cb();
            }
        }

        return $schema;
    }



    private function createIndexClosure($table, $columns, $unique)
    {
        return function () use ($table, $columns, $unique) {
            if ($unique) {
                $table->addUniqueIndex($columns);
            } else {
                $table->addIndex($columns);
            }
        };
    }



    private function parseColumns($flag, $columns)
    {
        foreach (explode(',', $flag) as $c) {
            $c = trim($c);
            if (!empty($c)) {
                $columns[] = $c;
            }
        }
        return $columns;
    }



    private function getType(Property $property)
    {
        $type = null;

        if ($property->isBasicType()) {
            $type = $property->getType();

            if ($type == 'string') {
                if ($property->hasCustomFlag('type')) {
                    $type = $property->getCustomFlagValue('type');
                } else if (!$property->hasCustomFlag('size') || $property->getCustomFlagValue('size') >= 65536) {
                    $type = 'text';
                }
            }

        } else {
            // Objects
            $reflectionClass = new \ReflectionClass($property->getType());
            $object = $reflectionClass->newInstance();

            if ($object instanceof \DateTime) {
                if ($property->hasCustomFlag('type')) {
                    $types = explode(':', strtolower($property->getCustomFlagValue('type')));
                    switch ($types[0]) {
                        case 'date':
                        case 'datetime':
                        case 'timestamp':
                            $type = $types[0];
                            break;
                        default:
                            throw new InvalidArgumentException(sprintf('DateTime property does not accept custom flag m:type(%s)', $types[0]));
                            break;
                    }
                } else {
                    $type = 'datetime';
                }
            }
        }

        return $type;
    }



    private function getRelationshipColumnType($table)
    {
        $property = $this->getRelationshipColumnProperty($table);
        return $this->getType($property);
    }



    /**
     * @param $table
     * @return Property
     */
    private function getRelationshipColumnProperty($table)
    {
        $class = $this->mapper->getEntityClass($table);
        /** @var Entity $entity */
        $entity = new $class;
        $primaryKey = $this->mapper->getPrimaryKey($table);
        return $entity->getReflection($this->mapper)->getEntityProperty($primaryKey);
    }



    private function isIgnored(Property $property)
    {
        return $property->hasCustomFlag('baked') || $property->hasCustomFlag('ignore') || $property->hasCustomFlag('ignored');
    }

}
