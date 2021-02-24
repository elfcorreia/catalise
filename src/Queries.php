<?php

require_once BASEDIR."/Query.php"; 

class Queries {
    
    private array $queries = [];

    public function getQueries(): array { return $this->queries; }
    public function addQuery(Query $query): void { $this->queries[] = $query; }

    public function getQueryByName(string $name): ?Query {
        foreach ($this->queries as $query) {
            if ($query->getName() === $name) {
                return $query;
            }
        }
        return null;
    }

    public function add(Query $query): void {
        $this->queries[] = $query;
    }
}