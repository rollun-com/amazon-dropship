<?php

namespace rollun\amazonDropship\Megaplan;


interface MegaplanFieldProviderInterface
{
    /**
     * Returns a name of a field on the Megaplan by its Amazon name
     *
     * @param string $fieldName
     * @return string|null
     */
    public function getMappedField($fieldName);
}