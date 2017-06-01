<?php

namespace BoomCMS\Tests\Repositories;

use BoomCMS\Database\Models\Asset;
use BoomCMS\Database\Models\AssetVersion;
use BoomCMS\Repositories\Asset as AssetRepository;
use BoomCMS\Repositories\AssetVersion as AssetVersionRepository;
use BoomCMS\Support\Facades\Album as AlbumFacade;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery as m;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AssetTest extends BaseRepositoryTest
{
    /**
     * @var Asset
     */
    protected $asset;

    /**
     * @var Filesystem
     */
    protected $fileystem;

    /**
     * @var string
     */
    protected $modelClass = Asset::class;

    /**
     * @var Asset
     */
    protected $model;

    /**
     * @var AssetRepository
     */
    protected $repository;

    /**
     * @var AssetVersionRepository
     */
    protected $version;

    /**
     * @var AssetVersionRepository
     */
    protected $versionRepository;

    public function setUp()
    {
        parent::setUp();

        $this->filesystem = m::mock(Filesystem::class);
        $this->version = new AssetVersion();
        $this->asset = m::mock(Asset::class);
        $this->versionRepository = new AssetVersionRepository($this->version);

        $this->asset
            ->shouldReceive('getLatestVersionId')
            ->andReturn(1);

        $this->repository = new AssetRepository($this->model, $this->versionRepository, $this->filesystem);
    }

    public function testExists()
    {
        foreach ([true, false] as $value) {
            $this->filesystem
                ->shouldReceive('exists')
                ->once()
                ->with($this->asset->getLatestVersionId())
                ->andReturn($value);

            $this->repository->exists($this->asset);
        }
    }

    public function testExtensions()
    {
        $extensions = collect(['gif', 'jpeg']);

        $this->model
            ->shouldReceive('withLatestVersion')
            ->once()
            ->andReturnSelf();

        $this->model
            ->shouldReceive('select')
            ->once()
            ->with('version.'.AssetVersion::ATTR_EXTENSION.' as e')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('having')
            ->once()
            ->with('e', '!=', '')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('orderBy')
            ->once()
            ->with('e')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('distinct')
            ->once()
            ->andReturnSelf();

        $this->model
            ->shouldReceive('pluck')
            ->once()
            ->with('e')
            ->andReturn($extensions);

        $this->assertEquals($extensions, $this->repository->extensions());
    }

    public function testDeleteWithArrayOfIds()
    {
        AlbumFacade::shouldReceive('removeAssetsFromCounts')
            ->once()
            ->with([1, 2, 3]);

        parent::testDeleteWithArrayOfIds();
    }

    public function testDeleteWithModel()
    {
        $this->model
            ->shouldReceive('getId')
            ->andReturn(1);

        AlbumFacade::shouldReceive('removeAssetsFromCounts')
            ->once()
            ->with([$this->model->getId()]);

        parent::testDeleteWithModel();
    }

    public function testFile()
    {
        $file = 'test file contents';

        $this->filesystem
            ->shouldReceive('get')
            ->once()
            ->with($this->asset->getLatestVersionId())
            ->andReturn($file);

        $this->assertEquals($file, $this->repository->file($this->asset));
    }

    public function testSaveFileUsesVersionIdAsFileName()
    {
        $file = m::mock(UploadedFile::class);

        $this->filesystem
            ->shouldReceive('putFileAs')
            ->once()
            ->with(null, $file, $this->asset->getLatestVersionId());

        $this->repository->saveFile($this->asset, $file);
    }

    public function testStream()
    {
        $this->filesystem
            ->shouldReceive('readStream')
            ->once()
            ->with($this->asset->getLatestVersionId());

        $this->repository->stream($this->asset);
    }
}
