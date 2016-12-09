<?php
// +----------------------------------------------------------------------
// | IOC容器
// +----------------------------------------------------------------------
// | url:https://github.com/itlessons/php-ioc
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications;

use Closure;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;

class Container
{
    private $parameters = [];
    private $bindings = [];
    private $instances = [];
    private $aliases = [];
    private $extenders = [];

    /**
     * 在容器中注册一个共享绑定类型
     *
     * 单例模式
     *
     * @param string $name
     * @param Closure|string|null $callback
     * @see bind
     */
    public function singleton($name, $callback = null)
    {
        return $this->bind($name, $callback, true);
    }

    /**
     * 在容器上绑定一个类型
     *
     * @param string $name
     * @param Closure|string|null $callback
     * @param bool $shared
     * @throws \InvalidArgumentException
     */
    public function bind($name, $callback = null, $shared = false)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(sprintf('类型名必须为字符串'));
        }

        if (null === $callback) {
            $callback = $name;
        } else if (is_string($callback)) {
            $this->aliases[$callback] = $name;
        }

        $this->bindings[$name] = compact('callback', 'shared');

    }

    /**
     * 检查容器是否绑定某个类型
     *
     * @param string $name
     * @return bool
     */
    public function exists($name)
    {
        return
            array_key_exists($name, $this->bindings) ||
            array_key_exists($name, $this->aliases) ||
            array_key_exists($name, $this->instances);
    }

    /**
     * 构造一个容器注册过的类型
     *
     * @param $name
     * @param array $parameters
     * @return object
     */
    public function make($name, $parameters = [])
    {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $callback = $name;

        if (isset($this->bindings[$name])) {
            $callback = $this->bindings[$name]['callback'];
        }

        $object = $this->build($callback, $parameters);

        foreach ($this->getExtenders($name) as $extender) {
            $object = $extender($object, $this);
        }

        // 注册的类允许共享则直接加入实例集合属性
        if ($this->isShared($name)) {
            $this->instances[$name] = $object;
        }

        return $object;
    }

    /**
     * 实例化一个指定类型
     *
     * @param Closure|string $callback
     * @param array $parameters
     * @return object
     * @throws \InvalidArgumentException
     */
    public function build($callback, $parameters = [])
    {
        if ($callback instanceof Closure) {
            return $callback($this, $parameters);
        }

        $reflector = new ReflectionClass($callback);

        if (!$reflector->isInstantiable()) {
            throw new InvalidArgumentException(sprintf('类型 [%s] 无法实例化', $callback));
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $callback;
        }

        return $reflector->newInstanceArgs(
            $this->getDependencies($constructor->getParameters(), $parameters)
        );
    }

    /**
     * 解决所有反射参数依赖.
     *
     * @param ReflectionParameter[] $parameters
     * @param [] $primitives
     * @return array
     * @throws LogicException
     */
    protected function getDependencies($parameters, $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {

            $class = $parameter->getClass();
            // make时传入的参数数组有回调函数的某个参数
            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            // 参数属于容器本身实例
            } elseif ($this->isInstanceOfContainer($parameter)) {
                $dependencies[] = $this;
            // 参数是一个类或者接口声明，常用于绑定实现到接口
            } elseif (!is_null($class)) {
                $dependencies[] = $this->make($class->name);
            // 参数是否在容器里设置过
            } elseif (($p = $this->getParameter($parameter->name))) {
                $dependencies[] = $p;
            // 参数是否声明了默认值
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new LogicException(sprintf('参数依赖错误 [%s].', $parameter));
            }
        }

        return $dependencies;
    }

    /**
     * 是否共享实例
     *
     * @param  string  $name 绑定名
     * @return mixed
     */
    private function isShared($name)
    {
        if (isset($this->bindings[$name]['shared'])) {
            return $this->bindings[$name]['shared'];
        }

        return false;
    }

    /**
     * 检测类是否可以实例化，基于反射
     *
     * @param  ReflectionParameter $parameter 反射参数实例
     * @return boolean
     */
    private function isInstanceOfContainer(ReflectionParameter $parameter)
    {
        return $parameter->getClass() &&
        ($parameter->getClass()->isSubclassOf(Container::class) ||
            $parameter->getClass()->getName() == Container::class);
    }

    /**
     * 注册一个实例到容器
     *
     * @param string|array $name
     * @param $instance
     */
    public function instance($name, $instance)
    {
        if (is_array($name)) {
            $names = $name;
            $name = $names[0];

            $this->instances[$name] = $instance;

            foreach ($names as $n) {
                $this->aliases[$n] = $name;
            }

            return;
        }
        $this->instances[$name] = $instance;
    }

    /**
     * 在容器中扩展一个抽象类型
     *
     * @param string $abstract
     * @param \Closure $closure
     *
     */
    public function extend($abstract, Closure $closure)
    {
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);
        } else {
            $this->extenders[$abstract][] = $closure;
        }
    }

    /**
     * 获取容器扩展集合
     *
     * @param  string $abstract 扩展集合名
     * @return array
     */
    protected function getExtenders($abstract)
    {
        if (isset($this->extenders[$abstract])) {
            return $this->extenders[$abstract];
        }
        return [];
    }

    /**
     * 调用指定的闭包或类的方法
     *
     * @param callable|string $callback
     * @param array $parameters
     * @return mixed
     */
    public function call($callback, array $parameters = [])
    {
        if (is_string($callback) && substr_count($callback, ':') == 1) {
            list($cls, $method) = explode(':', $callback, 2);
            $callback = [$this->make($cls), $method];
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException(sprintf('回调 "%s" 不是一个回调函数类型', $callback));
        }

        $dependencies = $this->getMethodDependencies($callback, $parameters);
        return call_user_func_array($callback, $dependencies);
    }

    /**
     * 获取方法参数依赖
     *
     * @param  mixed $callback   函数
     * @param  array $parameters 参数
     * @return array
     */
    protected function getMethodDependencies($callback, $parameters = [])
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }

        if (is_array($callback)) {
            $r = new ReflectionMethod($callback[0], $callback[1]);
        } else {
            $r = new ReflectionFunction($callback);
        }

        $dependencies = [];

        foreach ($r->getParameters() as $key => $parameter) {
            if (array_key_exists($parameter->name, $parameters)) {
                $dependencies[] = $parameters[$parameter->name];
                unset($parameters[$parameter->name]);
            } elseif ($parameter->getClass()) {
                $dependencies[] = $this->make($parameter->getClass()->name);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            }
        }

        return array_merge($dependencies, $parameters);
    }

    /**
     * 根据参数名获取在容器中注册的参数
     *
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        $array = $this->parameters;

        foreach (explode('.', $name) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            // 迭代自身
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * 获取在容器中注册的参数数组
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * 检测参数数组中是否存在某个key
     *
     * @param  string  $key 参数名
     * @return boolean
     */
    public function hasParameter($key)
    {
        return $this->getParameter($key) !== null;
    }

    /**
     * 设置容器参数
     *
     * 可以使用类似name.name的格式来存储不同命名空间的参数
     *
     * @param string $name  参数名
     * @param mixed $value 参数值
     */
    public function setParameter($name, $value)
    {
        $array = &$this->parameters;

        $keys = explode('.', $name);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) or !is_array($array[$key])) {
                $array[$key] = [];
            }

            // 迭代自身
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }
}
