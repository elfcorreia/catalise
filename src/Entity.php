<?php

#[\Attribute]
class Entity {
    
    private ?string $table = null;

    public function __construct(?string $table = null) {
        $this->table = $table;
    }

    public function getTable(): ?string {
        return $this->table;
    }

}