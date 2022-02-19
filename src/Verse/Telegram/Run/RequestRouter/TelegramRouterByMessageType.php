<?php


namespace Verse\Telegram\Run\RequestRouter;


use Verse\Run\Interfaces\RequestRouterInterface;
use Verse\Run\RunRequest;

class TelegramRouterByMessageType implements RequestRouterInterface
{
    public const DEFAULT_ROOT_NAMESPACE = 'App';
    public const DEFAULT_MODULE = 'Landing';
    public const DEFAULT_CONTROLLER = 'Landing';

    protected string $_rootNamespace = self::DEFAULT_ROOT_NAMESPACE;

    protected string $_defaultModuleName = self::DEFAULT_MODULE;

    protected string $_defaultControllerName = self::DEFAULT_CONTROLLER;


    public function getClassByRequest(RunRequest $request)
    {
        $module = $request->getResourcePart(0);
        if ($module) {
            $moduleParts = explode('_', $module);
            $moduleName = ucfirst(array_shift($moduleParts));
            if ($moduleParts) {
                array_walk($moduleParts, function (&$val) {
                    $val = ucfirst($val);
                });
                $controllerName = implode('', $moduleParts);
            } else {
                $controllerName = $moduleName;
            }

            $controllerClass = $this->buildClassName($moduleName, $controllerName);

            if (class_exists($controllerClass)) {
                return $controllerClass;
            }
        }

        return $this->buildClassName($this->_defaultModuleName, $this->_defaultControllerName);
    }

    protected function buildClassName($moduleName, $controllerName)
    {
        return '\\' . $this->_rootNamespace. '\\' . $moduleName . '\\Controller\\' . $controllerName;
    }

    /**
     * @return string
     */
    public function getRootNamespace(): string
    {
        return $this->_rootNamespace;
    }

    /**
     * @param string $rootNamespace
     */
    public function setRootNamespace(string $rootNamespace): void
    {
        $this->_rootNamespace = $rootNamespace;
    }

    /**
     * @return string
     */
    public function getDefaultModuleName(): string
    {
        return $this->_defaultModuleName;
    }

    /**
     * @param string $defaultModuleName
     */
    public function setDefaultModuleName(string $defaultModuleName): void
    {
        $this->_defaultModuleName = $defaultModuleName;
    }

    /**
     * @return string
     */
    public function getDefaultControllerName(): string
    {
        return $this->_defaultControllerName;
    }

    /**
     * @param string $defaultControllerName
     */
    public function setDefaultControllerName(string $defaultControllerName): void
    {
        $this->_defaultControllerName = $defaultControllerName;
    }
}