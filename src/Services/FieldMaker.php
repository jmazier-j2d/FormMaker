<?php

namespace Grafite\FormMaker\Services;

use Exception;
use Illuminate\Support\Str;
use Grafite\FormMaker\Builders\FieldBuilder;

class FieldMaker
{
    protected $builder;

    public $orientation;

    protected $standard = [
        'hidden',
        'text',
        'number',
        'color',
        'email',
        'date',
        'datetime-local',
        'month',
        'range',
        'search',
        'tel',
        'time',
        'url',
        'week',
        'password',
        'time',
        'image',
        'file',
    ];

    protected $special = [
        'select',
        'custom-file',
        'textarea',
        'relationship',
    ];

    protected $specialSelect = [
        'checkbox',
        'radio',
        'checkbox-inline',
        'radio-inline',
    ];

    public function __construct(FieldBuilder $fieldBuilder)
    {
        $this->builder = $fieldBuilder;
    }

    public function make(string $column, array $columnConfig, $object = null)
    {
        $field = null;
        $withErrors = false;
        $fieldGroup = config('form-maker.form.group-class', 'form-group');

        if ($this->orientation === 'horizontal') {
            $fieldGroup = $fieldGroup.' row';
        }

        $value = $this->getOldValue($column);

        if (!is_null($object)) {
            $value = $this->getObjectValue($object, $column);
        }

        $errors = $this->getFieldErrors($column, $object);

        if (!empty($errors)) {
            $withErrors = true;
        }

        $label = $this->label($column, $columnConfig, null, $withErrors);

        if (in_array($columnConfig['type'], $this->standard)) {
            $field = $this->builder->makeInput(
                $columnConfig['type'],
                $column,
                $value,
                $this->parseOptions($column, $columnConfig)['attributes']
            );
        }

        if (in_array($columnConfig['type'], $this->special)) {
            $method = 'make'.ucfirst(Str::camel($columnConfig['type']));
            $field = $this->builder->$method(
                $column,
                $value,
                $this->parseOptions($column, $columnConfig)
            );
        }

        if (isset($columnConfig['view'])) {
            $options = $this->parseOptions($column, $columnConfig);

            return view($columnConfig['view'], compact(
                'label',
                'field',
                'errors',
                'options'))->render();
        }

        if (in_array($columnConfig['type'], $this->specialSelect)) {
            $label = '';

            $field = $this->builder->makeCheckInput(
                $column,
                $value,
                $this->parseOptions($column, $columnConfig)
            );
        }

        if (is_null($field)) {
            throw new Exception("Unknown field type.", 1);
        }

        $before = $this->before($columnConfig);
        $after = $this->after($columnConfig);

        $fieldString = $before.$field.$after;

        if ($this->orientation === 'horizontal') {
            $labelColumn = config('form-maker.form.label-column', 'col-md-2 col-form-label');
            $inputColumn = config('form-maker.form.input-column', 'col-md-10');

            $label = $this->label($column, $columnConfig, $labelColumn, $withErrors);

            if (in_array($columnConfig['type'], $this->specialSelect)) {
                $legend = $columnConfig['legend'] ?? $columnConfig['label'];
                $label = "<legend class=\"{$labelColumn} pt-0\">{$legend}</legend>";
            }

            $fieldString = "<div class=\"{$inputColumn}\">{$fieldString}</div>";
        }

        return $this->wrapField($fieldGroup, $label, $fieldString, $errors);
    }

    public function label($column, $columnConfig, $class = null, $withErrors = false)
    {
        $label = ucfirst($column);

        if (is_null($class)) {
            $class = config('form-maker.form.label-class', 'control-label');
        }

        if (isset($columnConfig['label'])) {
            $label = $columnConfig['label'];
        }

        if ($withErrors) {
            $class = $class.' '.config('form-maker.form.error-class', 'has-error');
        }

        $id = $columnConfig['attributes']['id'] ?? $this->stripArrayHandles($column);

        return "<label class=\"{$class}\" for=\"{$id}\">{$label}</label>";
    }

    public function wrapField($fieldGroup, $label, $fieldString, $errors)
    {
        return "<div class=\"{$fieldGroup}\">{$label}{$fieldString}</div>{$errors}";
    }

    public function getObjectValue($object, $name)
    {
        if (is_object($object) && isset($object->$name)) {
            return $object->$name;
        }

        // If its a nested value like meta[user[phone]]
        if (strpos($name, '[') > 0) {
            $nested = explode('[', str_replace(']', '', $name));
            $final = $object;

            foreach ($nested as $property) {
                if (!empty($property) && isset($final->{$property})) {
                    $final = $final->{$property};
                } elseif (is_object($final) && is_null($final->{$property})) {
                    $final = '';
                }
            }

            return $final;
        }

        return '';
    }

    public function getFieldErrors($column)
    {
        $errors = [];

        if (session()->isStarted()) {
            $errors = session('errors');
        }

        if (!is_null($errors) && count($errors) > 0) {
            $message = implode(' ', $errors->get($column));
            return "<div><p class=\"text-danger\">{$message}</p></div>";
        }

        return '';
    }

    public function before($columnConfig)
    {
        $prefix = '';

        if (isset($columnConfig['before']) || isset($columnConfig['after'])) {
            $class = config('form-maker.form.before_after_input_wrapper', 'input-group');
            $prefix = '<div class="'.$class.'">'.$columnConfig['before'];
        }

        return $prefix;
    }

    public function after($columnConfig)
    {
        $postfix = '';

        if (isset($columnConfig['before']) || isset($columnConfig['after'])) {
            $postfix = $columnConfig['after'].'</div>';
        }

        return $postfix;
    }

    private function getOldValue($column)
    {
        if (session()->isStarted()) {
            return request()->old($column);
        }

        return null;
    }

    private function parseOptions($name, $options)
    {
        $default = [
            'class' => 'form-control',
            'id' => ucwords(str_replace('_', ' ', $name)),
        ];

        $options['attributes'] = array_merge($default, $options['attributes'] ?? []);

        return $options;
    }

    private function stripArrayHandles($column)
    {
        return str_replace('[]', '', ucfirst($column));
    }
}
