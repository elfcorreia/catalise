<?php

require_once BASEDIR."/Schema.php"; 
require_once BASEDIR."/Query.php"; 
require_once BASEDIR."/Queries.php";

class SQLOutput {    
    
    private Schema $schema;
    private string $driver;

    const data = [
        'sqlite' => [
            'string' => 'TEXT',
            'int' => 'INTEGER',
            'float' => 'REAL',
            'bool' => 'BOOLEAN',
            'DateTime' => 'TEXT',
        ],
        'pgsql' => [
            'string' => 'VARCHAR(255)',
            'int' => 'integer',
            'float' => 'real',
            'DateTime' => 'TIMESTAMP'
        ]
    ];

    public function __construct(string $driver) {
        $this->driver = $driver;        
    }

    public function __invoke(Schema $schema = null, Queries $queries = null) {
        $r = [];
        if ($schema !== null) {
            $this->schema = $schema;
            $this->compileCommonTypes();
            $this->compileCreateSQL($r);
        }
        if ($queries !== null) {
            foreach ($queries->getQueries() as $query) {
                $this->compileQueries($query, $r);
            }
        }
        return $r;
    }

    private function compileCommonTypes() {        
        if (!isset(self::data[$this->driver])) {
            throw new \Exception('No data available for SQL types of '.$this->driver);
        }
        $sql_types = self::data[$this->driver];
        foreach ($this->schema->getTables() as $table) {
            foreach ($table->getFields() as $field) {                        
                if (!isset($sql_types[$field->getType()])) {
                    throw new \Exception(sprintf('No SQL type "%s" found for field "%s" at table "%s" using "%s" driver', 
                        $field->getType(),
                        $field->getName(),
                        $table->getName(),
                        $this->driver
                    ));
                }                
                $field->setType($sql_types[$field->getType()]);
            }
        }
    }

    private function compileCreateSQL(array &$result): void {
        foreach ($this->schema->getTables() as $table) {
            ob_start();
            print("CREATE TABLE ");
            print($table->getName());
            print(" (\n\n");            
            $aux = [];
            foreach ($table->getFields() as $field) {            
                $s = "  ".$field->getName()." ".$field->getType();                
                if (!$field->isNullable()) {
                    $s .= " NOT NULL";
                }
                if ($field->getDefault() !== null) {
                    $s .= " DEFAULT ".$field->getDefault();
                }
                if ($field->isForeignKey()) {
                    $s .= ' REFERENCES '.$field->getForeignTable()->getName().'('.$field->getForeignField()->getName().')';
                }
                $aux[] = $s;
            }
            print(implode(",\n", $aux));            
            print("\n\n");
            $pks = [];
            foreach ($table->getFields() as $field) {
                if ($field->isPrimaryKey()) {
                    $pks[] = $field->getName();
                }
            }
            print("  PRIMARY KEY (");
            print(implode(', ', $pks));
            print(")\n");
            print(");\n\n");
            $s = ob_get_contents();
            ob_end_clean();
            $result['createSchema']['kind'] = 'exec';
            $result['createSchema']['sql'][] = $s;
        }
    }

    public function compileSelectAllSql(array &$result): void {
        foreach ($this->schema->getTables() as $table) {
            ob_start();
            print("SELECT * FROM ");
            print($table->getName());            
            $s = ob_get_contents();
            ob_end_clean();
            $result[$table->getPhpGroup()]['all'][] = $s;
        }
    }

    public function compileSelectOneSql(array &$result): void {
        foreach ($this->schema->getTables() as $table) {
            ob_start();
            print("SELECT * FROM ");
            print($table->getName());
            print(" WHERE ");
            $pks = [];
            foreach ($table->getPrimaryKeys() as $field) {
                $pks[] = sprintf("%s = :%s", $field->getName(), $field->getName());
            }
            print(implode(' AND ', $pks));
            $s = ob_get_contents();
            ob_end_clean();
            $result[$table->getPhpGroup()]['one'][] = $s;
        }
    }

    public function compileSelectByForeignKey(array &$result): void {
        foreach ($this->schema->getTables() as $table) {
            foreach ($table->getFields() as $field) {
                if (!$field->isForeignKey()) continue;

                ob_start();
                print("SELECT * FROM ");
                print($table->getName());
                print(" WHERE ");
                print($field->getName());
                print(" = :");
                print($field->getName());
                $s = ob_get_contents();
                ob_end_clean();
                $result[$table->getPhpGroup()]['by'.$field->getName()] = $s;
            }
        }
    }

    public function compileQueries(Query $query, array &$result): void {
        ob_start();
        print("SELECT ");
        print(implode(", ", $query->getFields()));
        print(" FROM ");
        print($query->getFrom());
        foreach ($query->getJoins() as $j) {
            print(" INNER JOIN ");
            print($j->getReferenceTable()->getName());
            print(" ON ");
            print($query->getFrom());
            print(".");
            print($j->getField()->getName());
            print(" = ");
            print($j->getReferenceTable()->getName());
            print(".");
            print($j->getReferenceField()->getName());
        }
        if ($query->hasCriterias()) {
            print(" WHERE ");
            print(implode(" OR ", $query->getCriterias()));
        }
        $result[$query->getName()]['kind'] = 'query';        
        $result[$query->getName()]['sql'] = ob_get_contents();
        ob_end_clean();
    }

    public function compileInsertSQL(&$result) {

    }


}