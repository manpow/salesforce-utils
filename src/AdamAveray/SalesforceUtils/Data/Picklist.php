<?php
namespace AdamAveray\SalesforceUtils\Data;

class Picklist {
    public const SEPARATOR = ';';

    /** @var array $values */
    private $values;

    /**
     * @param array|null $values The initial values for the picklist
     * @see ::fromString
     */
    public function __construct(array $values = null) {
        $this->values = (array)$values;
    }

    /**
     * @return array All values in the picklist
     */
    public function getValues(): array {
        return $this->values;
    }

    /**
     * @param mixed $value The value to locate
     * @return int|null The index for $value, or null if not present
     */
    private function find($value): ?int {
        $index = array_search($value, $this->values, true);
        return ($index === false) ? null : $index;
    }

    /**
     * Adds a value to the picklist if not already present
     *
     * @param mixed $value The value to add
     * @return $this
     */
    public function add($value): self {
        if ($this->find($value) === null) {
            $this->values[] = $value;
        }
        return $this;
    }

    /**
     * Removes a value from the picklist if present
     *
     * @param mixed $value The value to remove
     * @return $this
     */
    public function remove($value): self {
        $index = $this->find($value);
        if ($index !== null) {
            unset($this->values[$index]);
        }
        return $this;
    }

    /**
     * @return string The serialised string for the picklist
     */
    public function __toString(): string {
        return implode(self::SEPARATOR, $this->values);
    }

    /**
     * @param string $string The raw picklist string
     *
     * @return Picklist
     */
    public static function fromString(string $string) {
        return new self(explode(self::SEPARATOR, $string));
    }
}
