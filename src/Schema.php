<?php

require_once BASEDIR."/Table.php"; 

final class Schema {
    
    private array $tables = [];

    public function getTables(): array { return $this->tables; }
    public function addTable(Table $table): void { $this->tables[] = $table; }

    public function getTableByName(string $name): ?Table {
        foreach ($this->tables as $table) {
            if ($table->getName() === $name) {
                return $table;
            }
        }
        return null;
    }

    public function getTableByPhpName(string $name): ?Table {
        foreach ($this->tables as $table) {
            if ($table->getPhpName() === $name) {
                return $table;
            }
        }
        return null;
    }

}