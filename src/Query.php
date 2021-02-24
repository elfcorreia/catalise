<?php

require_once BASEDIR."/Join.php"; 

class Query {

    private string $name;
    private ?string $phpName = null;
    
    private array $fields = [];
    private ?string $from = null;
    private array $joins = [];
    private array $criterias = [];
    
    public function getName(): string { return $this->name; }
    public function setName($name): void { $this->name = $name; }
    public function getPhpName(): ?string { return $this->phpName; }
    public function setPhpName($phpName): void { $this->phpName = $phpName; }
    
    public function getFields(): array { return $this->fields; }
    public function addField($field): void {$this->fields[] = $field; }
    public function getFrom(): string { return $this->from; }
    public function setFrom(string $from): string { return $this->from = $from; }
    
    public function getJoins(): array { return $this->joins; }
    public function addJoin(Join $join): void { $this->joins[] = $join; }
    public function hasCriterias(): bool { return !empty($this->criterias); }
    public function getCriterias(): array { return $this->criterias; }
    public function addCriteria(string $criteria): void {$this->criterias[] = $criteria; }
    
}