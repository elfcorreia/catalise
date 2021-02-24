<?php

require_once BASEDIR."/Field.php"; 

final class Table {
    
    private ?string $phpName = null;
    private ?string $phpGroup = null;    
    private ?string $name = null;
    private array $fields = [];
    
    public function getPhpName(): ?string { return $this->phpName; }
    public function setPhpName($phpName): void { $this->phpName = $phpName; }
    public function getPhpGroup(): ?string { return $this->phpGroup; }
    public function setPhpGroup($phpGroup): void { $this->phpGroup = $phpGroup; }
    public function getName(): ?string { return $this->name; }
    public function setName($name): void { $this->name = $name; }
    public function getFields(): array { return $this->fields; }
    public function addField(Field $field): void { $this->fields[] = $field; }

    public function getFieldByName($name): ?Field {
        foreach ($this->fields as $f) {
            if ($f->getName() === $name) {
                return $f;                
            }
        }
        return null;
    }

    public function getPrimaryKeys(): array {
        return array_filter($this->fields, fn ($f) => $f->isPrimaryKey());
    }

    public function getForeignKeys(): array {
        return array_filter($this->fields, fn ($f) => $f->isForeignKey());
    }

}