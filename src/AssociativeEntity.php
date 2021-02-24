<?php

#[\Attribute]
class AssociativeEntity extends Entity {
    
    private array $references;

    public function __construct(string $ref1, string $ref2, string ...$others) {
        $this->references = array_merge([$ref1, $ref2], $others);
    }

    public function getReferences() { 
        return $this->references; 
    }
}