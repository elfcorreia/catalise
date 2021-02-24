<?php

require_once BASEDIR."/Table.php";

final class Field {

    private string $name;
    private ?string $type = null;
    private bool $nullable = true;
    private ?string $default = null;
    private array $constraints = [];
    private bool $primaryKey = false;
    private ?Table $foreignTable = null;
    private ?Field $foreignKey = null;

    public function getName(): string { return $this->name; }
    public function setName($name): void { $this->name = $name; }
    public function getType(): ?string { return $this->type; }
    public function setType($type): void { $this->type = $type; }
    public function isNullable(): bool { return $this->nullable; }
    public function setNullable($nullable): void { $this->nullable = $nullable; }
    public function getDefault(): ?string { return $this->default; }
    public function setDefault($default): void { $this->default = $default; }
    public function getConstraints(): array { return $this->constraints; }
    public function addConstraint($constraint): void { $this->constraints[] = $constraint; }
    public function isPrimaryKey(): bool { return $this->primaryKey; }
    public function setPrimaryKey($primaryKey): void { $this->primaryKey = $primaryKey; }
    public function isForeignKey(): bool { return $this->foreignTable !== null && $this->foreignField !== null; }
    public function getForeignTable(): ?Table { return $this->foreignTable; }
    public function setForeignTable(?Table $table): void { $this->foreignTable = $table; }
    public function setForeignField(?Field $field): void { $this->foreignField = $field; }
    public function getForeignField(): ?Field { return $this->foreignField; }

}