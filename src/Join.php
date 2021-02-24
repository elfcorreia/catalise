<?php

final class Join {

    private Field $field;
    private Table $referenceTable;
    private Field $referenceField;
    private array $criterias;

    public function __construct(Field $field, Table $referenceTable, Field $referenceField, Field ...$criterias) {
        $this->field = $field;
        $this->referenceTable = $referenceTable;
        $this->referenceField = $referenceField;
        $this->$criterias = $criterias;
    }
    
    public function getField(): Field { return $this->field; }
    public function getReferenceTable(): Table { return $this->referenceTable; }
    public function getReferenceField(): Field { return $this->referenceField; }
    public function getCriterias(): array { return $this->criterias; }

}