<?php


namespace Verse\Telegram\Run\RequestRouter;


use Verse\Run\Interfaces\RequestRouterInterface;
use Verse\Run\RunRequest;

class TelegramRouterByMessageType implements RequestRouterInterface
{
    const DEFAULT_MODULE = 'Landing';
    const DEFAULT_CONTROLLER = 'Landing';

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
        } else {
            $controllerName = self::DEFAULT_CONTROLLER;
            $moduleName = self::DEFAULT_MODULE;
        }

        return '\\' . $moduleName . '\\Controller\\' . $controllerName;
    }
}