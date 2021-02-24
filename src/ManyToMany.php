<?php

#[\Attribute]
class ManyToMany {

    private ?string $referenceClass = null;

    public function __construct(?string $referenceClass) {
        $this->referenceClass = $referenceClass;
    }

    public function getReferenceClass(): ?string {
        return $this->referenceClass; 
    }
}