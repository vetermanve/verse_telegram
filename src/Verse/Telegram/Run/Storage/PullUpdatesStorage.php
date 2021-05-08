<?php


namespace Verse\Telegram\Run\Storage;


use Verse\Storage\Data\JBaseDataAdapter;
use Verse\Storage\Example\ExampleStorage;
use Verse\Storage\SimpleStorage;
use Verse\Storage\StorageContext;
use Verse\Storage\StorageDependency;

class PullUpdatesStorage extends SimpleStorage
{
    private $dataDir = '';

    public function loadConfig()
    {
        $this->dataDir = getcwd().'/data/storage';
    }

    public function customizeDi(StorageDependency $container, StorageContext $context)
    {
        $adapter = new JBaseDataAdapter();
        // set data location
        $adapter->setDataRoot($this->dataDir);
        // set database (folder) name
        $adapter->setDatabase('telegram-run');
        // set table (folder) name
        $adapter->setResource('updates-tracking');

        $container->setModule(StorageDependency::DATA_ADAPTER, $adapter);
    }
}