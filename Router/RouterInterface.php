<?php

namespace TheWay\Router;

interface RouterInterface {
    public function addRoute(Route $route);
    public function getParam($param);
    public function redirectToRoute($routeSlug, array $params = array());
}