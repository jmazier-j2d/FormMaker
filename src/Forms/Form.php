<?php

namespace Grafite\FormMaker\Forms;

use Grafite\FormMaker\Builders\FieldBuilder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Arr;

class Form
{
    /**
     * Laravel session
     *
     * @var Session
     */
    public $session;

    /**
     * The model to be bound
     *
     * @var mixed
     */
    public $model;

    /**
     * Html string for output
     *
     * @var string
     */
    protected $html;

    /**
     * Message for delete confirmation
     *
     * @var string
     */
    public $confirmMessage;

    /**
     * The reserved form open attributes.
     *
     * @var array
     */
    protected $reserved = [
        'method',
        'url',
        'route',
        'action',
        'files'
    ];

    /**
     * The form methods that should be spoofed, in uppercase.
     *
     * @var array
     */
    protected $spoofedMethods = [
        'DELETE',
        'PATCH',
        'PUT'
    ];

    /**
     * Create a new form builder instance.
     *
     * @param  \Illuminate\Contracts\Routing\UrlGenerator $url
     * @param  \Illuminate\Contracts\View\Factory $view
     * @param  \\Grafite\FormMaker\Builders\FieldBuilder $fieldBuilder
     */
    public function __construct(
        UrlGenerator $url,
        Factory $view,
        FieldBuilder $fieldBuilder
    ) {
        $this->url = $url;
        $this->view = $view;
        $this->field = $fieldBuilder;
        $this->session = session();

    }

    /**
     * Generate a button form based on method and route
     *
     * @param string $method
     * @param string $route
     * @param string $button
     * @return void
     */
    public function action($method, $route, $button = 'Send', $options = [])
    {
        $this->html = $this->open([
            'route' => $route,
            'method' => $method,
            'class' => 'form d-inline'
        ]);

        $options = array_merge([
            'class' => 'btn btn-primary'
        ], $options);

        if (!empty($this->confirmMessage)) {
            $options = array_merge($options, [
                'onclick' => "return confirm('{$this->confirmMessage}')"
            ]);
        }

        $this->html .= $this->field->submit($button, $options);

        $this->html .= $this->close();

        return $this;
    }

    /**
     * Set the confirmation message for delete forms
     *
     * @param string $message
     *
     * @return \Grafite\FormMaker\Forms\ModelForm
     */
    public function confirm($message)
    {
        $this->confirmMessage = $message;

        return $this;
    }

    /**
     * Open up a new HTML form.
     *
     * cloned from LaravelCollective/html
     *
     * @param  array $options
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function open($options)
    {
        $method = Arr::get($options, 'method', 'post');

        $attributes['method'] = $this->getMethod($method);
        $attributes['action'] = $this->getAction($options);
        $attributes['accept-charset'] = 'UTF-8';

        $append = $this->getAppendage($method);

        if (isset($options['files']) && $options['files']) {
            $options['enctype'] = 'multipart/form-data';
        }

        $attributes = array_merge($attributes, Arr::except($options, $this->reserved));

        $attributes = $this->field->attributes($attributes);

        return $this->toHtmlString('<form' . $attributes . '>' . $append);
    }

    /**
     * Generate a close form tag
     *
     * cloned from LaravelCollective/html
     *
     * @return string
     */
    public function close()
    {
        return $this->toHtmlString('</form>');
    }

    /**
     * Generate a hidden field with the current CSRF token.
     *
     * cloned from LaravelCollective/html
     *
     * @return string
     */
    public function token()
    {
        return $this->field->makeInput('hidden', '_token', $this->session->token());
    }

    /**
     * Get the form appendage for the given method.
     *
     * cloned from LaravelCollective/html
     *
     * @param  string $method
     *
     * @return string
     */
    protected function getAppendage($method)
    {
        list($method, $appendage) = [strtoupper($method), ''];

        if (in_array($method, $this->spoofedMethods)) {
            $appendage .= $this->field->makeInput('hidden', '_method', $method);
        }

        if ($method !== 'GET') {
            $appendage .= $this->token();
        }

        return $appendage;
    }

    /**
     * Get the action for a "url" option.
     *
     * cloned from LaravelCollective/html
     *
     * @param  array|string $options
     *
     * @return string
     */
    protected function getUrlAction($options)
    {
        if (is_array($options)) {
            return $this->url->to($options[0], array_slice($options, 1));
        }

        return $this->url->to($options);
    }

    /**
     * Get the action for a "route" option.
     *
     * cloned from LaravelCollective/html
     *
     * @param  array|string $options
     *
     * @return string
     */
    protected function getRouteAction($options)
    {
        if (is_array($options)) {
            return $this->url->route($options[0], array_slice($options, 1));
        }

        return $this->url->route($options);
    }

    /**
     * Get the form action from the options.
     *
     * cloned from LaravelCollective/html
     *
     * @param  array $options
     *
     * @return string
     */
    protected function getAction(array $options)
    {
        if (isset($options['url'])) {
            return $this->getUrlAction($options['url']);
        }

        if (isset($options['route'])) {
            return $this->getRouteAction($options['route']);
        }

        elseif (isset($options['action'])) {
            return $this->getControllerAction($options['action']);
        }

        return $this->url->current();
    }

    /**
     * Create a new model based form builder.
     *
     * cloned from LaravelCollective/html
     *
     * @param  mixed $model
     * @param  array $options
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function model($model, array $options = [])
    {
        $this->model = $model;

        return $this->open($options);
    }

    /**
     * Set the model instance on the form builder.
     *
     * cloned from LaravelCollective/html
     *
     * @param  mixed $model
     *
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Get the current model instance on the form builder.
     *
     * cloned from LaravelCollective/html
     *
     * @return mixed $model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Parse the form action method.
     *
     * cloned from LaravelCollective/html
     *
     * @param  string $method
     *
     * @return string
     */
    protected function getMethod($method)
    {
        $method = strtoupper($method);

        return $method !== 'GET' ? 'POST' : $method;
    }

    /**
     * Transform the string to an Html serializable object
     *
     * cloned from LaravelCollective/html
     *
     * @param $html
     *
     * @return \Illuminate\Support\HtmlString
     */
    protected function toHtmlString($html)
    {
        return new HtmlString($html);
    }

    /**
     * Output html as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->html;
    }
}
