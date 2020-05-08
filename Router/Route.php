<?php

namespace TheWay\Router;

/**
 * Class Route used in Way Router
 *
 * @package TheWay\Router
 */
class Route {
    private $name;
    private $regex;
    private $path;
    private $action;
    private $params;
    private $private;

    public function __construct($name, $regex, $path, $action, $private) {
        $this->name = $name;
        $this->regex = $regex;
        $this->path = $path;
        $this->action = $action;
        $this->private = $private;
        $this->params = array();
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getRegex() {
        return $this->regex;
    }

    /**
     * @param string $regex
     */
    public function setRegex($regex) {
        $this->regex = $regex;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action) {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * @return array
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params) {
        $this->params = $params;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool {
        return $this->private;
    }
}