<?php
namespace App\Core;

class Validator {
    private array $errors = [];
    private array $data = [];
    private array $rules = [];

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function validate(array $rules): bool {
        $this->errors = [];
        $this->rules = $rules;

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule): void {
        $params = [];
        if (str_contains($rule, ':')) {
            [$ruleName, $paramString] = explode(':', $rule);
            $params = explode(',', $paramString);
            $rule = $ruleName;
        }

        $displayName = $this->getDisplayName($field);
        
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "{$displayName} is required.");
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$displayName} must be a valid email address.");
                }
                break;

            case 'min':
                $min = (int) $params[0];
                if (is_string($value) && strlen($value) < $min) {
                    $this->addError($field, "{$displayName} must be at least {$min} characters.");
                }
                if (is_numeric($value) && $value < $min) {
                    $this->addError($field, "{$displayName} must be at least {$min}.");
                }
                break;

            case 'max':
                $max = (int) $params[0];
                if (is_string($value) && strlen($value) > $max) {
                    $this->addError($field, "{$displayName} must not exceed {$max} characters.");
                }
                if (is_numeric($value) && $value > $max) {
                    $this->addError($field, "{$displayName} must not exceed {$max}.");
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "{$displayName} must be a number.");
                }
                break;

            case 'alpha':
                if ($value && !ctype_alpha($value)) {
                    $this->addError($field, "{$displayName} must contain only letters.");
                }
                break;

            case 'alphanumeric':
                if ($value && !ctype_alnum($value)) {
                    $this->addError($field, "{$displayName} must contain only letters and numbers.");
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($this->data[$confirmField] ?? null)) {
                    $this->addError($field, "{$displayName} does not match.");
                }
                break;

            case 'unique':
                $table = $params[0];
                $column = $params[1] ?? $field;
                $exceptId = $params[2] ?? null;
                if ($value && !$this->isUnique($table, $column, $value, $exceptId)) {
                    $this->addError($field, "{$displayName} has already been taken.");
                }
                break;

            case 'exists':
                $table = $params[0];
                $column = $params[1] ?? $field;
                if ($value && !$this->exists($table, $column, $value)) {
                    $this->addError($field, "{$displayName} does not exist.");
                }
                break;

            case 'in':
                $allowed = $params;
                if ($value && !in_array($value, $allowed)) {
                    $this->addError($field, "{$displayName} must be one of: " . implode(', ', $allowed));
                }
                break;

            case 'match':
                $matchField = $params[0];
                if ($value !== ($this->data[$matchField] ?? null)) {
                    $matchDisplayName = $this->getDisplayName($matchField);
                    $this->addError($field, "{$displayName} must match {$matchDisplayName}.");
                }
                break;

            case 'file':
                if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                    break;
                }
                if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                    $this->addError($field, "{$displayName} upload failed.");
                }
                break;

            case 'mimes':
                $allowedMimes = $params;
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    if (!in_array($extension, $allowedMimes)) {
                        $this->addError($field, "{$displayName} must be a file of type: " . implode(', ', $allowedMimes));
                    }
                }
                break;

            case 'image':
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($_FILES[$field]['type'], $allowedTypes)) {
                        $this->addError($field, "{$displayName} must be an image.");
                    }
                }
                break;
        }
    }

    private function getDisplayName(string $field): string {
        $fieldNames = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'password' => 'Password',
            'username' => 'Username',
            'voter_id' => 'Voter ID',
            'student_id' => 'Student ID'
        ];
        return $fieldNames[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    private function isUnique(string $table, string $column, string $value, ?string $exceptId = null): bool {
        $db = Database::getInstance();
        $sql = "SELECT id FROM {$table} WHERE {$column} = :value";
        $params = ['value' => $value];
        
        if ($exceptId) {
            $sql .= " AND id != :except_id";
            $params['except_id'] = $exceptId;
        }
        
        $result = $db->selectOne($sql, $params);
        return $result === null;
    }

    private function exists(string $table, string $column, string $value): bool {
        $db = Database::getInstance();
        $sql = "SELECT id FROM {$table} WHERE {$column} = :value LIMIT 1";
        $result = $db->selectOne($sql, ['value' => $value]);
        return $result !== null;
    }

    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(string $field): ?string {
        return $this->errors[$field][0] ?? null;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    public function hasError(string $field): bool {
        return isset($this->errors[$field]);
    }

    public function getData(): array {
        return $this->data;
    }
}
