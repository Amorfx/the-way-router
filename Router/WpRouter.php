<?php

namespace TheWay\Router;

class WpRouter implements RouterInterface {
    private $prefixQueryVars;
    private $args;
    private $routes;
    private $queryVars;

    public function __construct(array $args = array()) {
        $this->args = $args;
        $this->routes = array();
        $this->queryVars = array();

        if (array_key_exists('prefix', $this->args)) {
            $this->prefixQueryVars = $this->args['prefix'];
        } else {
            $this->prefixQueryVars = 'theway';
        }
    }

    public function init() {
        $this->addRoutes();
        $this->generateQueryVars();
        //add query vars in wp
        add_filter('query_vars', array($this, 'addQueryVars'));
        add_action('init', array($this, 'addRulesInWp'));
        add_filter('template_include', array($this, 'dispatch'), 99);
    }

    private function addRoutes() {
        foreach ($this->args['routes'] as $route) {
            if ($route instanceof Route) {
                $this->addRoute($route);
            } else if (is_array($route)) {
                if (!array_key_exists('private', $route)) {
                    $route['private'] = false;
                }
                $this->addRoute(new Route($route['name'], $route['regex'], $route['path'], $route['action'], $route['private']));
            }
        }
    }

    /**
     * Get url from a route slug
     *
     * @param $routeSlug
     * @param array $params
     *
     * @return mixed|string|void
     * @throws \Exception
     */
    public function getUrlForRoute($routeSlug, $params = array()) {
        if (array_key_exists($routeSlug, $this->routes)) {
            $route = $this->routes[$routeSlug];
            $path = $this->replaceParamForRoute($route, $params, $routeSlug);

            return get_site_url(null, $path);
        } else {
            throw new \Exception('The route ' . $routeSlug . ' does not exist.');
        }
    }

    /**
     * Replace all params in path of a route
     * Example : /realdash/page/{page} => /realdash/page/1
     *
     * @param Route $route
     * @param $params
     * @param string $routeSlug
     *
     * @return mixed
     * @throws \Exception
     */
    public function replaceParamForRoute(Route $route, $params, $routeSlug = '') {
        //the path which will be pass into the get_site_url function
        $path = $route->getPath();
        $routeParams = $route->getParams();

        if (sizeof($routeParams) > 0) {
            if (sizeof($params) == 0) {
                throw new \Exception('The route ' . $routeSlug . ' must have params and you don\'t pass any params');
            }

            foreach ($params as $paramName => $paramValue) {
                if (isset($routeParams[$paramName])) {
                    $path = str_replace('{' . $paramName . '}', $paramValue, $path);
                }
            }
        }
        return $path;
    }

    /**
     * Add all parameters of routes in array of query vars of WP. And save order of matches for add rewrite rules
     *
     * @param $aVars
     *
     * @return array
     */
    public function addQueryVars($aVars) {
        return array_merge($aVars, $this->queryVars);
    }

    /**
     * Generate the query vars for wordpress before added it with params of path config of a route
     * Also generate order of matches for future query var
     */
    public function generateQueryVars() {
        $this->queryVars[] = $this->prefixQueryVars . '_router';
        $this->queryVars[] = $this->prefixQueryVars . '_action';

        //get all parameters in routes to generate query vars
        foreach ($this->routes as $routeSlug => $aRoute) {
            $matches = array();
            $paramRegex = "#\{([a-zA-Z]*)\}#";
            preg_match_all($paramRegex, $aRoute->getPath(), $matches);

            //if there is params => add query_vars and set params to Route object
            if ($matches !== false && !empty($matches[0])) {
                $sizeMatch = sizeof($matches[1]);
                $allMatches = array();
                for ($i = 0; $i < $sizeMatch; $i++) {
                    $nameVar = $matches[1][$i];
                    //save order of matches
                    $allMatches[$nameVar] = $i + 1;
                    //add query_var
                    $this->queryVars[] = $this->prefixQueryVars . '_' . $nameVar;
                }
                $this->routes[$routeSlug]->setParams($allMatches);
            }
        }
    }

    /**
     * Call add_rewrite_rules in init hook
     */
    public function addRulesInWp() {
        $query = 'index.php?' . $this->prefixQueryVars . '_router=1';
        /** @var Route $aRoute */
        foreach ($this->routes as $aRoute) {
            if (!empty($aRoute->getParams())) {
                foreach ($aRoute->getParams() as $slugParam => $orderMatch) {

                    $query .= '&' . $this->prefixQueryVars . '_' . $slugParam . '=$matches[' . $orderMatch . ']';
                }
            }
            $query .= '&' . $this->prefixQueryVars . '_action=' . urlencode($aRoute->getAction());

            add_rewrite_rule($aRoute->getRegex(), $query, 'top');
        }
    }

    /**
     * Redirect an entire url
     *
     * @param $url
     */
    public function redirectUrl($url) {
        wp_safe_redirect($url);
        exit;
    }

    /**
     * Redirect to an url of a route
     *
     * @param $routeSlug
     * @param array $params
     *
     * @throws \Exception
     */
    public function redirectToRoute($routeSlug, array $params = array()) {
        $url = $this->getUrlForRoute($routeSlug, $params);
        $this->redirectUrl($url);
    }

    /**
     * Get a param from the url
     *
     * @param $param
     *
     * @return mixed
     */
    public function getParam($param) {
        return get_query_var($this->prefixQueryVars . '_' . $param);
    }

    /**
     * Add Route for the Router
     *
     * @param Route $route
     */
    public function addRoute(Route $route) {
        $this->routes[$route->getName()] = $route;
    }

    /**
     * @param $action
     *
     * @return bool|Route
     */
    public function getRouteByActionAndMethod($action) {
        /** @var Route $aRoute */
        foreach ($this->routes as $aRoute) {
            if ($aRoute->getAction() === $action) {
                return $aRoute;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isAuthenticated() {
        return is_user_logged_in();
    }

    /**
     * Get the value of all params of a matched Route
     *
     * @param Route $aRoute
     *
     * @return array
     */
    private function getParamsValueOfRoute(Route $aRoute) {
        $params = array();
        foreach (array_keys($aRoute->getParams()) as $aParam) {
            $params[$aParam] = $this->getParam($aParam);
        }

        return $params;
    }

    /**
     * See if the url match with a route
     *
     * @param $template
     *
     * @return mixed
     */
    public function dispatch($template) {
        global $wp_query;

        // If there is no matches
        if (!array_key_exists($this->prefixQueryVars . '_router', $wp_query->query_vars)) {
            return $template;
        }

        //call controller and action
        $routeMatched = $this->getRouteByActionAndMethod(get_query_var($this->prefixQueryVars . '_action', ''));

        // If private and not authenticated return 404
        if ($routeMatched->isPrivate()) {
            if (!$this->isAuthenticated()) {
                $wp_query->set_404();
                status_header(404);
                return get_404_template();
            }
        }

        // route matched
        if ($routeMatched) {
            $splittingControllerAction = explode('@', $routeMatched->getAction());
            $controller = $splittingControllerAction[0];
            $callFunction = $splittingControllerAction[1];

            if (class_exists($controller)) {
                $ControllerInstance = new $controller($wp_query);
                if (method_exists($ControllerInstance, $callFunction)) {
                    call_user_func_array(array($ControllerInstance, $callFunction), $this->getParamsValueOfRoute($routeMatched));
                }
            } else {
                throw new \RuntimeException('The controller ' . $controller . ' not found.');
            }
        }
    }
}