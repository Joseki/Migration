<?php

namespace Joseki\Migration\Generator;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
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

    /** @var IMapper */
    private $mapper;

    private $defaultConfig = [
        'autoincrement' => 'auto',
        'collate' => 'utf8_unicode_ci',
        'cascading' => true,
    ];

    /** @var array */
    private $options;



    /**
     * LeanMapperSchemaGenerator constructor.
     * @param array $options
     * @param IMapper $mapper
     */
    public function __construct(array $options, IMapper $mapper)
    {
        $this->mapper = $mapper;
        $this->options = $options;
    }



    public function createSchema(array $entities)
    {
        $config = array_merge($this->defaultConfig, $this->options);

        $schema = new Schema();
        Type::addType(LongTextType::LONG_TEXT, '\Joseki\Migration\Generator\DBAL\Types\LongTextType');
        Type::addType(TimestampType::TIMESTAMP, '\Joseki\Migration\Generator\DBAL\Types\TimestampType');

        $createdTables = [];
        /** @var \LeanMapper\Entity $entity */
        foreach ($entities as $entity) {
            $reflection = $entity->getReflection($this->mapper);
            $properties = $reflection->getEntityProperties();
            $onEnd = [];

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
                    if (!$property->isWritable()) {
                        continue;
                    }

                    $type = $this->getType($property);

                    if ($type === null) {
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

                            $cascade = $config['cascading'] ? 'CASCADE' : 'NO ACTION';

                            $columnReferencingSourceTable = $relationship->getColumnReferencingSourceTable();
                            $sourceTableType = $this->getRelationshipColumnType($columnReferencingSourceTable);
                            $sourceColumn = $relationshipTable->addColumn($columnReferencingSourceTable, $sourceTableType);
                            $primaryKey1 = $this->mapper->getPrimaryKey($tableName);

                            $columnReferencingTargetTable = $relationship->getColumnReferencingTargetTable();
                            $targetTableType = $this->getRelationshipColumnType($columnReferencingTargetTable);
                            $targetColumn = $relationshipTable->addColumn($columnReferencingTargetTable, $targetTableType);
                            $primaryKey2 = $this->mapper->getPrimaryKey($relationship->getTargetTable());
                            
                            $relationshipTable->addForeignKeyConstraint(
                                $table,
                                [$columnReferencingSourceTable],
                                [$primaryKey1],
                                ['onDelete' => $cascade, 'onUpdate' => $cascade]
                            );

                            $relationshipTable->addForeignKeyConstraint(
                                $relationship->getTargetTable(),
                                [$columnReferencingTargetTable],
                                [$primaryKey2],
                                ['onDelete' => $cascade, 'onUpdate' => $cascade]
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
                            $onDeleteCascade = $config['cascading'] ? ($property->isNullable() ? 'SET NULL' : 'CASCADE') : 'NO ACTION';
                            $onUpdateCascade = $config['cascading'] ? ($property->isNullable() ? 'SET NULL' : 'CASCADE') : 'NO ACTION';
                            $table->addForeignKeyConstraint(
                                $relationship->getTargetTable(),
                                [$column->getName()],
                                [$this->mapper->getPrimaryKey($relationship->getTargetTable())],
                                ['onDelete' => $onDeleteCascade, 'onUpdate' => $onUpdateCascade]
                            );
                        }
                    }
                }

                if ($property->hasCustomFlag('unique')) {
                    $indexColumns = $this->parseColumns($property->getCustomFlagValue('unique'), [$column->getName()]);
                    $onEnd[] = $this->createIndexClosure($table, $indexColumns, true);
                }

                if ($property->hasCustomFlag('index')) {
                    $indexColumns = $this->parseColumns($property->getCustomFlagValue('index'), [$column->getName()]);
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
     * @throws \Exception
     */
    private function getRelationshipColumnProperty($table)
    {
        $class = $this->mapper->getEntityClass($table);
        if (!class_exists($class)) {
            throw new \Exception;
        }
        /** @var Entity $entity */
        $entity = new $class;
        $primaryKey = $this->mapper->getPrimaryKey($table);
        $primryKeyField = $this->mapper->getEntityField($table, $primaryKey);
        return $entity->getReflection($this->mapper)->getEntityProperty($primryKeyField);
    }



    private function isIgnored(Property $property)
    {
        return $property->hasCustomFlag('baked') || $property->hasCustomFlag('ignore') || $property->hasCustomFlag('ignored');
    }

}
