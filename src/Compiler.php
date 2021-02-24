<?php

require_once BASEDIR."/Schema.php";
require_once BASEDIR."/Queries.php";
require_once BASEDIR."/Query.php";
require_once BASEDIR."/Table.php";
require_once BASEDIR."/Field.php";
require_once BASEDIR."/Join.php";

class Compiler {
    
    private Schema $schema;
    private Queries $queries;

    public function __construct() {
        $this->schema = new Schema();
        $this->queries = new Queries();
    }

    private function camelCaseToSnakeCase($s) {
        return strtolower(preg_replace_callback('|[a-z0-9][A-Z]|', fn ($c) => strtolower($c[0][0].'_'.$c[0][1]), $s));
    }

    private function plural($s) {
        return $s.'s';
    }

    private function getOrCreateTableFor(string $klass): Table {
        $table = $this->schema->getTableByPhpName($klass);
        if ($table === null) {
            $table = new Table();
            $table->setPhpName($klass);
            $this->schema->addTable($table);
        }
        return $table;
    }

    public function compile($klass) {
        $r = new \ReflectionClass($klass);
    
        $table = null;
        $entity_attributes = $r->getAttributes(Entity::class);
        if (!empty($entity_attributes)) { # Entity
            $table = $this->getOrCreateTableFor($r->getName());
            $name = $entity_attributes[0]->newInstance()->getTable();
            if ($name === null) {
                $name = $this->camelCaseToSnakeCase(lcfirst($r->getShortName()));
            }
            $table->setName($name);
            $table->setPhpGroup($table->getPhpName());
            
            $q = new Query();
            $q->setName('get'.$this->plural($r->getShortName()));
            $q->setPhpName($table->getPhpGroup());
            $q->addField('*');
            $q->setFrom($table->getName());
            $this->queries->add($q);       
        } else {
            $table = $this->getOrCreateTableFor($r->getName());
            $associative_entity_attrs = $r->getAttributes(AssociativeEntity::class);
            if (!empty($associative_entity_attrs)) {
                $aux = $associative_entity_attrs[0]->newInstance();
                $name = $aux->getTable();
                if ($name === null) {
                    $names = [];
                    foreach($associative_entity_attrs[0]->newInstance()->getReferences() as $ref) {
                        $ref_klass = new \ReflectionClass($ref);
                        $names[] = $this->camelCaseToSnakeCase(lcfirst($ref_klass->getShortName()));
                    }
                    $name = implode('_', $names);
                }
                $table->setName($name);
                $table->setPhpGroup($associative_entity_attrs[0]->newInstance()->getReferences()[0]);

                // fetch using other fields

            }
        }
        if ($table === null) {
            throw new \Exception('Missing Entity or AssociativeEntity class attribute at '.$klass);
        }        
        $properties = $r->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        foreach ($properties as $p) {
            if (!empty($p->getAttributes(OneToMany::class))) continue;
            if (!empty($p->getAttributes(ManyToMany::class))) continue;
            if (!empty($p->getAttributes(OneToOne::class))) continue;
            $f = new Field();
            $f->setName($this->camelCaseToSnakeCase(lcfirst($p->getName())));
            $f->setPrimaryKey(!empty($p->getAttributes(Id::class)));
            $f->setType($p->getType()->getName());
            $f->setNullable($p->getType()->allowsNull());
            if ($p->hasDefaultValue()) {
                $f->setDefault($p->getDefaultValue());
            }
            $table->addField($f);
        }
    }

    public function output(callable $visitor) {
        $this->compileForeignKeys();
        $this->compileRelationships();        
        return $visitor($this->schema, $this->queries);
    }

    private function compileForeignKeys() {
        foreach ($this->schema->getTables() as $table) {
            foreach ($table->getFields() as $field) {   
                if (!class_exists($field->getType())) continue;
               
                $rt = new \ReflectionClass($field->getType());
                if ($rt->isInternal()) continue;

                $fk_table = $this->schema->getTableByPhpName($field->getType());
                if ($fk_table === null) {
                    throw new \Exception('Missing #[Entity] attribute for '.$field->getType().'?');
                }                    
                $fk_pks = $fk_table->getPrimaryKeys();
                if (empty($fk_pks)) {
                    throw new \Exception('Missing #[Id] attribute for '.$fk_table->getPhpName().' at '.$table->getPhpName().'?');
                }
                $n = $field->getName() === $fk_table->getName() ? $fk_table->getName().'_'.$fk_pks[0]->getName() : $field->getName();
                $field->setName($n);
                $field->setType($fk_pks[0]->getType());
                $field->setForeignTable($fk_table);
                $field->setForeignField($fk_pks[0]);

                $tr = new \ReflectionClass($table->getPhpName());
                $q = new Query();
                $q->setName('find'.$this->plural($tr->getShortName()).'By'.ucfirst($field->getName()));
                $q->setPhpName($table->getPhpGroup());
                $q->addField('*');
                $q->setFrom($table->getName());                
                $q->addCriteria($field->getName().' = :'.$field->getName());                
                $this->queries->add($q);
            }
        }
    }

    private function compileRelationships() {
        foreach ($this->schema->getTables() as $table) {
            $r = new \ReflectionClass($table->getPhpName());

            $associative_entity_attrs = $r->getAttributes(AssociativeEntity::class);
            if (!empty($associative_entity_attrs)) {
                $associative_entity = $associative_entity_attrs[0]->newInstance();
                $refs = $associative_entity->getReferences();                
                foreach ($refs as $ref) {
                    $ref_table = $this->schema->getTableByPhpName($ref);
                    $ref_pks = $ref_table->getPrimaryKeys();
                    foreach ($ref_pks as $ref_pk) {
                        $ref_field = new Field();
                        $ref_field->setName($ref_table->getName().'_'.$ref_pk->getName());
                        $ref_field->setType($ref_pk->getType());
                        $ref_field->setPrimaryKey(true);
                        $ref_field->setForeignTable($ref_table);
                        $ref_field->setForeignField($ref_pk);
                        $table->addField($ref_field);
                    }
                }
            }

            $properties = $r->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
            foreach ($properties as $p) {
                $one_to_many_attrs = $p->getAttributes(OneToMany::class);
                if (!empty($one_to_many_attrs)) {
                    $otm = $one_to_many_attrs[0]->newInstance();
                    $ref_table = $this->schema->getTableByPhpName($otm->getReferenceClass());
                    $pk = $table->getPrimaryKeys()[0];
                    $ref_field = new Field();
                    $ref_field->setName($table->getName().'_'.$pk->getName());
                    $ref_field->setType($pk->getType());
                    $ref_field->setForeignTable($table);
                    $ref_field->setForeignField($pk);
                    $ref_table->addField($ref_field);
                    continue;
                }
                $many_to_many_attrs = $p->getAttributes(ManyToMany::class);
                if (!empty($many_to_many_attrs)) {
                    $mtm = $many_to_many_attrs[0]->newInstance();                        
                    $new_table = new Table();
                    $ref_table = $this->schema->getTableByPhpName($mtm->getReferenceClass());                        
                    if ($ref_table === null) {
                        throw new \Exception('Missing Entity annotation for '.$mtm->getReferenceClass().'?');
                    }
                    $new_table->setName($table->getName().'_'.$ref_table->getName());
                    $new_table->setPhpGroup($table->getPhpGroup());

                    $pks = $table->getPrimaryKeys();
                    if (empty($pks)) {
                        throw new \Exception('Missing Id annotation for '.$table->getPhpName().'?');
                    }
                    $pk = $pks[array_key_first($pks)];
                    $f1 = new Field();
                    $f1->setName($table->getName().'_'.$pk->getName());
                    $f1->setType($pk->getType());
                    $f1->setForeignTable($table);
                    $f1->setForeignField($pk);     
                    $f1->setPrimaryKey(true);
                    $new_table->addField($f1);

                    
                    $ref_pk = $ref_table->getPrimaryKeys()[0];
                    $f2 = new Field();
                    $f2->setName($ref_table->getName().'_'.$ref_pk->getName());
                    $f2->setType($pk->getType());
                    $f2->setForeignTable($ref_table);
                    $f2->setForeignField($ref_pk);
                    $f2->setPrimaryKey(true);
                    $new_table->addField($f2);
                    
                    $target_r = new \ReflectionClass($mtm->getReferenceClass());
                    $q1 = new Query();
                    $q1->setName('get'.$this->plural($r->getShortName()).'By'.$target_r->getShortName());
                    $q1->addField($table->getName().'.*');
                    $q1->setFrom($new_table->getName());
                    $q1->addJoin(new Join($f1, $table, $ref_pk));
                    $this->queries->add($q1);
                    
                    $q2 = new Query();
                    $q2->setName('get'.$this->plural($target_r->getShortName()).'By'.$r->getShortName());
                    $q2->addField($ref_table->getName().'.*');
                    $q2->setFrom($new_table->getName());                    
                    $q2->addJoin(new Join($f2, $ref_table, $pk, $f1));
                    $this->queries->add($q2);

                    $this->schema->addTable($new_table);
                    continue;
                }
                $one_to_one_attrs = $p->getAttributes(OneToOne::class);
                if (!empty($one_to_one_attrs)) {
                    $ref_table = $this->schema->getTableByPhpName($p->getType()->getName());
                    if ($ref_table === null) {
                        throw new \Exception('Missing #[Entity] for '. $p->getType()->getName().'?');
                    }
                    $pks = $table->getPrimaryKeys();
                    if (empty($pks)) {
                        throw new \Exception('Missing #[Id] for '.$table->getPhpName().'?');
                    }
                    $pk = $pks[array_key_first($pks)];
                    $ref_pks = $ref_table->getPrimaryKeys();
                    if (empty($ref_pks)) {                        
                        throw new \Exception('Missing #[Id] for '.$ref_table->getPhpName().'?');
                    }
                    $ref_pk = $ref_pks[array_key_first($ref_pks)];                    
                    $ref_pk->setType($pk->getType());
                    $ref_pk->setForeignTable($table);
                    $ref_pk->setForeignField($pk);
                    continue;
                }
            }
        }
    }
}