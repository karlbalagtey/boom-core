<?php

namespace BoomCMS\Tests\Asset;

use BoomCMS\Core\Asset\Asset;
use BoomCMS\Tests\AbstractTestCase;

class AssetTest extends AbstractTestCase
{
    public function testDirectory()
    {
        $this->assertEquals(storage_path().'/boomcms/assets', Asset::directory());
    }

    public function testGetFilename()
    {
        $asset = $this->getMockBuilder('BoomCMS\Core\Asset\Asset')
            ->setMethods(['getType'])
            ->setConstructorArgs([['version:id' => 1]])
            ->getMock();

        $this->assertEquals(Asset::directory().'/1', $asset->getFilename());
    }
}