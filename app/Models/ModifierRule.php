<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModifierRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'modifier_id',
        'rule_type',      // 'stacking', 'exclusion', 'dependency', 'limit'
        'target_type',    // 'modifier', 'category', 'subscription_plan'
        'target_id',      // ID of the target modifier, category, or plan
        'condition',      // JSON encoded conditions
        'priority',       // Rule priority (higher numbers take precedence)
        'action',         // 'allow', 'deny', 'require'
        'metadata'
    ];

    protected $casts = [
        'condition' => 'array',
        'metadata' => 'array'
    ];

    public function modifier()
    {
        return $this->belongsTo(SubscriptionModifier::class);
    }

    /**
     * Check if the rule allows the operation
     */
    public function evaluate(array $context): bool
    {
        if (!$this->condition) {
            return $this->action === 'allow';
        }

        $result = $this->evaluateCondition($this->condition, $context);
        return $this->action === 'allow' ? $result : !$result;
    }

    /**
     * Evaluate a complex condition
     */
    protected function evaluateCondition(array $condition, array $context): bool
    {
        if (isset($condition['operator'])) {
            switch ($condition['operator']) {
                case 'and':
                    foreach ($condition['conditions'] as $subCondition) {
                        if (!$this->evaluateCondition($subCondition, $context)) {
                            return false;
                        }
                    }
                    return true;

                case 'or':
                    foreach ($condition['conditions'] as $subCondition) {
                        if ($this->evaluateCondition($subCondition, $context)) {
                            return true;
                        }
                    }
                    return false;

                case 'not':
                    return !$this->evaluateCondition($condition['condition'], $context);
            }
        }

        return $this->evaluateSimpleCondition($condition, $context);
    }

    /**
     * Evaluate a simple condition
     */
    protected function evaluateSimpleCondition(array $condition, array $context): bool
    {
        $value = data_get($context, $condition['field']);
        $targetValue = $condition['value'];

        switch ($condition['comparison'] ?? '=') {
            case '=':
                return $value == $targetValue;
            case '!=':
                return $value != $targetValue;
            case '>':
                return $value > $targetValue;
            case '>=':
                return $value >= $targetValue;
            case '<':
                return $value < $targetValue;
            case '<=':
                return $value <= $targetValue;
            case 'in':
                return in_array($value, (array)$targetValue);
            case 'not_in':
                return !in_array($value, (array)$targetValue);
            case 'contains':
                return str_contains($value, $targetValue);
            case 'starts_with':
                return str_starts_with($value, $targetValue);
            case 'ends_with':
                return str_ends_with($value, $targetValue);
            default:
                return false;
        }
    }
}
